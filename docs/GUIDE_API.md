# Guide API Go Gorée — workflows de bout en bout

Ce guide déroule **tous les workflows** de l'API : authentification, création de comptes contrôleurs par l'admin avec activation par email, demandes de résidence et validation, achat de billets (portefeuille **et** PayDunya avec lien de paiement), scan à l'embarquement, recharge de portefeuille, et le reste.

Une **collection Postman** prête à l'emploi est fournie dans [`docs/postman/`](postman/) :
- `Go-Goree.postman_collection.json`
- `Go-Goree.postman_environment.json`

> Les exemples utilisent `curl` ; chaque requête existe aussi dans la collection Postman (les tokens/ids y sont capturés automatiquement).

---

## 0. Préparation

### 0.1 Configuration `.env`
```dotenv
APP_URL=http://localhost:8000
PASSWORD_RESET_URL="${APP_URL}/reset-password"

# PayDunya : appels RÉELS vers le sandbox avec vos clés de test
PAYDUNYA_DRIVER=http
PAYDUNYA_ENVIRONMENT=test
PAYDUNYA_MASTER_KEY=...      # vos clés de test PayDunya
PAYDUNYA_PRIVATE_KEY=test_private_...
PAYDUNYA_PUBLIC_KEY=test_public_...
PAYDUNYA_TOKEN=...

# Emails : smtp (envoi réel) ou log (écrit dans storage/logs/laravel.log)
MAIL_MAILER=smtp
MAIL_HOST=...                # votre serveur SMTP
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="no-reply@go-goree.sn"
MAIL_FROM_NAME="Go Gorée"
```

> **Les emails sont mis en file d'attente** (non bloquants + réessais automatiques). Un **worker** doit tourner pour qu'ils partent réellement :
> ```bash
> php artisan queue:work --queue=notifications,fraude,rapports,default
> ```
> Pour un envoi **immédiat** en dev (sans worker), mettez `QUEUE_CONNECTION=sync`.

> Pour développer **sans réseau** (tests/CI), repassez sur `PAYDUNYA_DRIVER=fake` : jetons simulés, webhook signé avec `PAYDUNYA_FAKE_SECRET`.

### 0.2 Base de données + données de démo
> ⚠️ `migrate:fresh` **efface** toutes les données. À faire sur une base de dev uniquement.
> (Nécessaire ici : la base locale était en retard sur les migrations `softDeletes`.)

```bash
php artisan migrate:fresh --seed
```

Le seeder crée :

| Compte | Email | Mot de passe | Rôle |
|---|---|---|---|
| Admin | `admin@goree.sn` | `Admin@1234` | Admin |
| Client | `client@goree.sn` | `Client@1234` | Client |

Plus : la grille tarifaire (Enfant/Adulte/Résident/Étranger), les **plans d'abonnement** (1 / 6 / 12 mois), les **deux chaloupes** (Beer & Coumba Castel), des trajets récurrents, les **voyages des 7 prochains jours générés à tour de rôle** (comme le cron), et un portefeuille client crédité de 10 000 FCFA.

### 0.3 Lancer le serveur + les jobs
```bash
php artisan serve            # http://localhost:8000
php artisan queue:work       # events/notifications (facultatif en QUEUE_CONNECTION=sync)
```

### 0.4 Importer dans Postman
Importer les deux fichiers `docs/postman/*.json`, sélectionner l'environnement **« Go Gorée - Local »**, puis exécuter les requêtes dans l'ordre des dossiers. Les tokens sont stockés automatiquement.

---

## 1. Authentification

### 1.1 Connexion (récupérer un token)
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"admin@goree.sn","mot_de_passe":"Admin@1234"}'
```
Réponse :
```json
{ "data": { "access_token": "1|xxxxxxxx", "token_type": "Bearer", "user": { "id": "...", "email": "admin@goree.sn", "role": { "nom": "Admin" } } } }
```
Le token se place ensuite dans l'en-tête : `Authorization: Bearer <access_token>`.

### 1.2 Profil / Déconnexion
```bash
curl http://localhost:8000/api/v1/me      -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
curl -X POST http://localhost:8000/api/v1/logout -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
```

**Erreurs** : mauvais mot de passe → `401` ; compte désactivé → `403` ; champs manquants → `422`.

---

## 2. Comptes contrôleurs (créés par l'admin) + activation par email

Workflow : **l'admin crée le compte → `password_reset_at = null` → un email avec un jeton est envoyé → le contrôleur définit son mot de passe → il peut se connecter.**

### 2.1 L'admin crée un contrôleur
```bash
curl -X POST http://localhost:8000/api/v1/controleurs \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"prenom":"Modou","nom":"Fall","email":"modou.controleur@goree.sn","telephone":"770000000"}'
```
Réponse `201` :
```json
{ "data": { "id":"...", "email":"modou.controleur@goree.sn", "role": { "nom":"Agent" } }, "message":"Compte contrôleur créé. Un email d'activation a été envoyé." }
```
- Le compte est **actif** mais son mot de passe est aléatoire/inutilisable ; `password_reset_at` est **null**.
- Seul un **admin** peut appeler cette route (sinon `403`).

### 2.2 Récupérer le jeton d'activation
L'email d'activation contient un lien `PASSWORD_RESET_URL?token=...&email=...` **et** le jeton en clair (bloc « Jeton (pour tests/API) »).

- **En `MAIL_MAILER=smtp`** : l'email arrive dans la boîte du contrôleur (le worker `queue:work` doit tourner). En production, le lien ouvre la page front qui appellera l'endpoint `password/reset`.
- **En `MAIL_MAILER=log`** : l'email est écrit dans `storage/logs/laravel.log` :
```bash
# Windows PowerShell
Select-String -Path storage/logs/laravel.log -Pattern "token=" | Select-Object -Last 1
```

### 2.3 Le contrôleur définit son mot de passe
```bash
curl -X POST http://localhost:8000/api/v1/password/reset \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"modou.controleur@goree.sn","token":"<TOKEN>","mot_de_passe":"Controleur@1234","mot_de_passe_confirmation":"Controleur@1234"}'
```
Réponse `200`. Désormais `password_reset_at` est renseigné et le jeton est consommé (usage unique, expiration 60 min).

### 2.4 Le contrôleur se connecte
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"modou.controleur@goree.sn","mot_de_passe":"Controleur@1234"}'
```

### 2.5 Mot de passe oublié (tout utilisateur)
Même mécanique via `POST /api/v1/password/forgot` (`{"email":"..."}`) puis `POST /api/v1/password/reset`. La réponse de `forgot` est **identique** que le compte existe ou non (anti-énumération).

---

## 3. Demandes de résidence + validation par l'admin

### 3.1 Le client soumet une demande
```bash
curl -X POST http://localhost:8000/api/v1/demandes-residence \
  -H "Authorization: Bearer $CLIENT_TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"carte_identite":"CNI123456789","residence":"Gorée Centre","photo":"photo.png"}'
```
Réponse `201` avec `demande.id` (statut `EN_COURS`). Un client ne voit que **ses** demandes ; l'admin les voit toutes.

### 3.2 L'admin valide (→ active le résident + crée l'abonnement 12 mois)
```bash
curl -X POST http://localhost:8000/api/v1/demandes-residence/<DEMANDE_ID>/valider \
  -H "Authorization: Bearer $ADMIN_TOKEN" -H "Accept: application/json"
```
Effets automatiques (via événement) : `est_resident = true`, création du profil `Resident` actif et d'un **abonnement de 12 mois**. Le client bénéficiera alors du **tarif résident** à l'achat.

### 3.3 L'admin refuse (avec motif)
```bash
curl -X POST http://localhost:8000/api/v1/demandes-residence/<DEMANDE_ID>/refuser \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"motif_refus":"Justificatifs illisibles."}'
```
> Validation/refus réservés à l'**Admin** (`403` sinon). Le motif est obligatoire au refus.

---

## 4. Achat de billet, lien de paiement, validation & scan

### 4.1 Récupérer un voyage
```bash
curl http://localhost:8000/api/v1/voyages -H "Authorization: Bearer $CLIENT_TOKEN" -H "Accept: application/json"
```
Notez un `data[].id` → `voyage_id`. Le **tarif** est résolu automatiquement (résident si abonnement actif, sinon adulte/étranger).

### 4.2 Achat payé par le portefeuille (immédiat)
```bash
curl -X POST http://localhost:8000/api/v1/billets \
  -H "Authorization: Bearer $CLIENT_TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"voyage_id":"<VOYAGE_ID>","payment_mode":"PORTEFEUILLE"}'
```
Réponse `201` : billet **PAYE**, portefeuille débité, place décrémentée, `billet.qr_token` renvoyé.
Erreurs : solde insuffisant / voyage complet → `400`.

### 4.3 Achat payé par PayDunya (lien de paiement)
```bash
curl -X POST http://localhost:8000/api/v1/billets \
  -H "Authorization: Bearer $CLIENT_TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"voyage_id":"<VOYAGE_ID>","payment_mode":"PAYDUNYA"}'
```
Réponse `201` :
```json
{ "message":"Billet réservé avec succès.", "billet": { "id":"...", "statut":"EN_ATTENTE_PAIEMENT", "qr_token":"..." }, "redirect_url":"https://paydunya.com/checkout/invoice/xxxx" }
```
1. Le client ouvre `redirect_url` (page de paiement PayDunya) et paie avec un **compte fictif PayDunya** (créé depuis votre dashboard sandbox).
2. PayDunya appelle votre **webhook** → le paiement passe `ACCEPTE` et le **billet passe `PAYE`** (voir §7).

### 4.4 Scan à l'embarquement (contrôleur)
```bash
curl -X POST http://localhost:8000/api/v1/scans \
  -H "Authorization: Bearer $CONTROLEUR_TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"qr_token":"<QR_TOKEN>"}'
```
| Cas | Résultat | HTTP |
|---|---|---|
| Billet PAYE | `VALIDE` → billet passe `UTILISE` | 200 |
| Billet déjà utilisé | `DEJA_SCANNE` | 422 |
| Billet expiré | `EXPIRE` | 422 |
| Billet non payé | `NON_EMBARQUE` | 422 |
| QR inconnu | `NON_EMBARQUE` | 404 |

---

## 5. Portefeuille & recharge PayDunya

### 5.1 Consulter le solde
```bash
curl http://localhost:8000/api/v1/portefeuille -H "Authorization: Bearer $CLIENT_TOKEN" -H "Accept: application/json"
```

### 5.2 Initier une recharge (lien de paiement)
```bash
curl -X POST http://localhost:8000/api/v1/portefeuille/recharge \
  -H "Authorization: Bearer $CLIENT_TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"montant":5000,"payment_mode":"PAYDUNYA"}'
```
Réponse `201` : `{ "redirect_url": "https://paydunya.com/checkout/..." }`.
Le solde n'est crédité qu'**après confirmation par le webhook** (jamais directement — sécurité). Le jeton PayDunya n'est **pas** exposé au client. `payment_mode` = `WAVE`, `ORANGE_MONEY`, `YAS`, `CARTE_BANCAIRE` ou `PAYDUNYA` (pas `PORTEFEUILLE`).

---

## 6. Le reste des workflows

| Domaine | Endpoint | Notes |
|---|---|---|
| Notifications | `GET /notifications`, `PUT /notifications/{id}`, `DELETE /notifications/{id}` | l'utilisateur n'accède qu'aux siennes (404 sinon) |
| Alertes fraude | `GET /alertes-fraude`, `GET/PUT /alertes-fraude/{id}` | **Admin uniquement** |
| Voyages | lecture `GET` (tous) · écriture `POST/PUT/DELETE` (**Admin**) | date `after_or_equal:today` |
| Trajets | lecture `GET` (tous) · écriture (**Admin**) | `jour`, `heure_depart` (H:i), `duree` |
| Chaloupes | lecture `GET` (tous) · écriture (**Admin**) | `imatriculation` (requise), `nom`, `capacite` |
| Tarifs | lecture `GET` (tous) · écriture (**Admin**) | `categorie`, `prix` |
| Utilisateurs | `GET/POST/PUT/DELETE /users` | **Admin uniquement** |
| Résidents / Abonnements | `/residents`, `/abonnements` | **Admin uniquement** |

---

## 7. Le webhook PayDunya en détail

`POST /webhooks/paydunya` — **hors** `/api`, **sans** authentification, mais **signé**.

**Sécurité (3 couches)** : 1) vérification de la signature `SHA-512` (`hash_equals`) ; 2) reconfirmation serveur-à-serveur du statut auprès de PayDunya ; 3) mise à jour **idempotente** sous verrou (pas de double crédit).

### 7.1 En mode réel (`http`)
PayDunya appelle votre `callback_url`. En local, PayDunya ne peut pas joindre `localhost` → exposez le serveur (ex. `ngrok http 8000`) et réglez :
```dotenv
PAYDUNYA_WEBHOOK_URL="https://<votre-sous-domaine>.ngrok.io/webhooks/paydunya"
```
La signature est le `SHA-512` de votre **master key** (envoyée par PayDunya dans `data[hash]`).

### 7.2 En mode simulation (`fake`)
Vous forgez la notification vous-même. La signature attendue est le `SHA-512` du secret fake :
```bash
php -r "echo hash('sha512', 'paydunya-fake-secret-change-me');"
```
```bash
curl -X POST http://localhost:8000/webhooks/paydunya \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"data":{"hash":"<SHA512_DU_SECRET>","status":"completed","invoice":{"token":"<PAYDUNYA_TOKEN>"}}}'
```
Réponses : `200` (`accepte` / `deja_traite`), `401` (signature invalide), `404` (jeton inconnu).

---

## 8. Récapitulatif des endpoints

| Méthode | URL | Auth | Rôle |
|---|---|---|---|
| POST | `/api/v1/login` *(throttle 6/min)* | — | — |
| POST | `/api/v1/password/forgot` · `/password/reset` *(throttle)* | — | — |
| GET | `/api/v1/me` · POST `/logout` | ✅ | tous |
| GET/POST | `/api/v1/controleurs` | ✅ | **Admin** |
| CRUD | `/api/v1/users` | ✅ | **Admin** |
| GET/POST | `/api/v1/demandes-residence` | ✅ | tous (lecture filtrée) |
| POST | `/api/v1/demandes-residence/{id}/valider` · `/refuser` | ✅ | **Admin** |
| GET | `/api/v1/voyages` `/trajets` `/chaloupes` `/tarifs` | ✅ | tous |
| POST/PUT/DELETE | `/api/v1/voyages` `/trajets` `/chaloupes` `/tarifs` | ✅ | **Admin** |
| POST/GET | `/api/v1/billets` | ✅ | tous (liste filtrée) |
| POST | `/api/v1/scans` | ✅ | **Agent / Admin** |
| GET | `/api/v1/portefeuille` · POST `/portefeuille/recharge` | ✅ | tous |
| GET | `/api/v1/notifications` | ✅ | tous (les siennes) |
| CRUD | `/api/v1/alertes-fraude` · `/residents` · `/abonnements` · `/payements` | ✅ | **Admin** |
| POST | `/webhooks/paydunya` | signé | PayDunya |

> **Sécurité** : le contrôle d'accès par rôle est appliqué via le middleware `role:` (ex. `role:Admin`, `role:Admin,Agent`). Un rôle insuffisant renvoie `403`. Les routes non authentifiées renvoient `401`.
