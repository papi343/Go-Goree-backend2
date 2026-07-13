# Go Gorée — Workflows métier

Ce document décrit les parcours fonctionnels de l'application et leur état
d'implémentation. **Tout est couvert par des tests automatisés (169 tests).**

Voir aussi : [`GUIDE_API.md`](GUIDE_API.md) (guide pas-à-pas + curl) et la
collection Postman dans [`postman/`](postman/).

## Acteurs

| Rôle | Description |
|---|---|
| **Client** | S'inscrit, achète/génère des billets, peut devenir résident |
| **Résident** | Client dont la résidence à Gorée est validée ; peut s'abonner |
| **Agent (contrôleur)** | Ouvre les embarcations et scanne les billets |
| **Admin** | Vue globale : voyages, chaloupes, trajets, tarifs, plans, comptes, fraude |

---

## 1. Inscription & connexion du client

- **`POST /api/v1/register`** — inscription publique (rôle Client, mot de passe haché, throttle 6/min).
- **`POST /api/v1/login`** → jeton Bearer ; **`GET /me`**, **`POST /logout`**.
- Mot de passe oublié : **`POST /password/forgot`** → email avec jeton → **`POST /password/reset`**.

🧪 `RegisterTest`, `AuthTest`, `PasswordResetTest`

---

## 2. Voyages visibles + génération automatique (cron)

- **`GET /api/v1/voyages`** (tout utilisateur authentifié), avec filtres :
  - `?periode=today` — aujourd'hui · `?periode=semaine` — 7 prochains jours
  - `?date=YYYY-MM-DD` — date précise · `?disponibles=true` — places restantes > 0
  - défaut : voyages à venir, triés par date.
- **Cron quotidien** `GenererVoyagesSemaineJob` : garantit en permanence les voyages
  des **7 prochains jours** (pour chaque trajet, aux jours correspondants, avec une
  chaloupe active). Idempotent. Planifié dans `routes/console.php`.

🧪 `VoyageFilterTest`, `GenererVoyagesSemaineJobTest`, `VoyageTest`

---

## 3. Achat de billet (client non résident)

1. Le client choisit un voyage.
2. **`POST /api/v1/billets`** `{voyage_id, payment_mode}` :
   - `PORTEFEUILLE` → débit immédiat, billet `PAYE`.
   - `PAYDUNYA` (ou Wave/Orange…) → `redirect_url` (lien de paiement) ; le billet
     passe `PAYE` à la **confirmation du webhook**.
- **Anti-doublon** : un seul billet actif par (client, voyage). Une 2ᵉ tentative est
  refusée **et génère une alerte de fraude**.

🧪 `BilletPurchaseTest`, `PayDunyaRechargeTest`

---

## 4. Résidence : demande → notification → validation

1. **`POST /api/v1/demandes-residence`** `{carte_identite, residence, photo}` (client).
2. Les **admins sont notifiés** :
   - **en temps réel** (Reverb) sur leur canal privé,
   - **par email détaillé** (`NouvelleDemandeResidenceMail`).
3. **`POST /demandes-residence/{id}/valider`** (admin) → active le statut résident.
   La validation **n'attribue plus** d'abonnement gratuit (voir §5).
   Refus : **`/refuser`** `{motif_refus}`.

🧪 `DemandeResidenceTest`, `NotificationTempsReelTest`

---

## 5. Abonnement (souscription payante)

- **Plans** (durée + prix) : `GET /api/v1/plans` (public), CRUD admin (`/plans`).
  Seed par défaut : 1 mois / 6 mois / 12 mois.
- **`POST /api/v1/abonnements/souscrire`** `{plan_id, payment_mode}` (résident) :
  - `PORTEFEUILLE` → débit + **activation immédiate**,
  - `PAYDUNYA` → `redirect_url` puis **activation au webhook**.
  - **Prolongation** : si déjà abonné, la période s'ajoute à la fin en cours.

🧪 `AbonnementSouscriptionTest`

---

## 6. Génération de billet du résident

Priorité **abonnement**, sinon **tarif réduit** :

| Situation du résident | Résultat |
|---|---|
| Abonnement **actif** | Billet **généré gratuitement** (montant 0, `PAYE`, sans paiement) |
| **Sans** abonnement | Achat au **tarif réduit RESIDENT** |

L'anti-doublon (§3) et l'alerte de fraude s'appliquent aussi ici.

🧪 `BilletPurchaseTest` (résident abonné = gratuit ; résident non abonné = tarif réduit)

---

## 7. Admin : données de référence

- CRUD **voyages / chaloupes / trajets / tarifs / plans** (écriture réservée Admin, lecture ouverte).
- Création de **comptes contrôleurs** : **`POST /api/v1/controleurs`** → compte rôle
  Agent, `password_reset_at = null`, **email d'activation** avec jeton → le contrôleur
  définit son mot de passe via `POST /password/reset`.

🧪 `VoyageTest`, `AuthorizationTest`, `ControleurAccountTest`

---

## 8. Contrôleur : embarquement & scan

1. **`POST /api/v1/embarquements/ouvrir`** `{voyage_id}` — ouvre (ou récupère) la
   session d'embarquement du voyage. **Idempotente/partagée** : plusieurs contrôleurs
   travaillent sur la **même** session. Fermeture : `/{id}/fermer`.
2. **`POST /api/v1/scans`** `{qr_token, embarquement_id}` — résultats :

| Résultat | Cas | HTTP |
|---|---|---|
| `VALIDE` | billet payé du bon voyage → passe `UTILISE` | 200 |
| `MAUVAIS_VOYAGE` | billet d'un autre voyage (confusion) | 422 |
| `DEJA_SCANNE` | billet déjà utilisé → **alerte de fraude** | 422 |
| `EXPIRE` | billet expiré | 422 |
| `NON_EMBARQUE` | billet non payé / QR inconnu | 422 / 404 |

- **Performance & concurrence** : validation par **UPDATE atomique conditionnel**
  (`WHERE qr_token=? AND statut=PAYE`) — une requête indexée, sans verrou applicatif ;
  parmi N contrôleurs scannant le même billet simultanément, **un seul** réussit.
- Traçabilité : chaque scan enregistre `embarquement_id` et `scanne_par`.

🧪 `EmbarquementTest`, `ScanTest`

---

## 9. Expiration automatique des billets

- `ExpireTicketsJob` (planifié chaque minute) : les billets **non utilisés** (`PAYE`)
  passent `EXPIRE` **1 heure** après l'heure de départ du voyage. Les billets déjà
  scannés (`UTILISE`) ne sont pas affectés.

🧪 `ExpireTicketsJobTest`

---

## 10. Détection & signalement de fraude

`FraudeDetectee` est déclenché (et les écouteurs `AlerterAdminFraude` /
`BloquerTransactionSuspecte` s'activent) dans deux cas :

- **Double billet** pour un même voyage (règle `double_billet_voyage`).
- **Double scan** d'un billet déjà utilisé (règle `double_scan_billet`).

Consultation/traitement : `GET/PUT /api/v1/alertes-fraude` (Admin).

🧪 `BilletPurchaseTest`, `ScanTest`

---

## 11. Portefeuille

- **`GET /api/v1/portefeuille`** — solde.
- **`POST /api/v1/portefeuille/recharge`** `{montant, payment_mode}` — recharge via
  PayDunya ; solde crédité **à la confirmation du webhook** (signé, idempotent).

🧪 `PortefeuilleTest`, `PortefeuilleRepositoryTest`, `PayDunyaRechargeTest`

---

## 12. Notifications temps réel (Reverb)

- Événement **`NotificationCreee`** (`ShouldBroadcast`) diffusé sur le canal privé
  `App.Models.User.{id}`, événement `notification.creee`.
- Le front React s'abonne via **Laravel Echo** (voir snippet dans `GUIDE_API.md` /
  variables `VITE_REVERB_*`). Serveur : `php artisan reverb:start`.

🧪 `NotificationTempsReelTest`

---

## Récapitulatif — couverture des exigences

| # Exigence | Implémenté | Testé |
|---|---|---|
| 1. Inscription client | ✅ | 🧪 |
| 2. Voyages visibles + cron 7 jours | ✅ | 🧪 |
| 3. Notifications temps réel (React) | ✅ | 🧪 |
| 4. Notifications par email détaillé | ✅ | 🧪 |
| 5. Souscription d'abonnement payante | ✅ | 🧪 |
| 6. Résident sans abonnement = tarif réduit | ✅ | 🧪 |
| 7. Anti-doublon de billet + fraude | ✅ | 🧪 |
| 8. Génération gratuite si abonnement actif (table plans) | ✅ | 🧪 |
| 9. Génération des voyages par cron | ✅ | 🧪 |
| 10. Ouverture d'embarcation + scan par voyage | ✅ | 🧪 |
| 11. Scan atomique/rapide + multi-contrôleurs | ✅ | 🧪 |
| 12. Scheduler d'expiration (1h) | ✅ | 🧪 |
| 13. Signalement de fraude | ✅ | 🧪 |
