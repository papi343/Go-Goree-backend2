# Go Gorée API — Architecture générée

Ce document décrit l'arborescence **exacte** produite par `php run_all.php` (ou par les 12 scripts exécutés un par un) sur un projet Laravel 12 fraîchement scaffoldé avec Sanctum. Il sert de référence pour naviguer dans le code une fois généré, et de plan de travail pour savoir quoi implémenter ensuite.

## Légende

- `[OK]` **Implémenté** — fonctionne réellement (validé par migration + tinker + appel de service réel)
- `[TODO]` **Stub `// TODO`** — signature/squelette correct, corps à écrire

---

## Arborescence complète

```
app/
├── Enums/                                          (15 fichiers, tous [OK] — ce sont des enums PHP natifs, pas de logique à écrire)
│   ├── DemandeResidenceEnum.php                     EN_COURS · ACCEPTEE · REFUSEE · ANNULEE
│   ├── ModePayementEnum.php                         WAVE · ORANGE_MONEY · YAS · CARTE_BANCAIRE · PORTEFEUILLE
│   ├── StatutPayementEnum.php                       EN_COURS · ACCEPTE · REFUSE · SUSPECT
│   ├── CanalEnum.php                                SMS · IN_APP · MAIL
│   ├── NotificationEnum.php                         PAYEMENT · ALERTE
│   ├── StatutBilletEnum.php                         EN_ATTENTE_PAIEMENT · PAYE · UTILISE · EXPIRE · ANNULE
│   ├── CategorieEnum.php                            ENFANT · ADULTE · RESIDENT · ETRANGER
│   ├── StatutChaloupeEnum.php                       ACTIVE · EN_MAINTENANCE · PANNE
│   ├── ResultatScanEnum.php                         VALIDE · DEJA_SCANNE · EXPIRE · NON_EMBARQUE
│   ├── JourEnum.php                                 LUNDI … DIMANCHE
│   ├── MouvementPortefeuilleEnum.php                RECHARGE · DEBIT
│   ├── TypeTransactionPayDunyaEnum.php               ACHAT_BILLET · RECHARGE_PORTEFEUILLE      (extension spec)
│   ├── NiveauAlerteFraudeEnum.php                   INFO · SUSPECT · CRITIQUE                 (extension spec)
│   ├── StatutMouvementEnum.php                      EN_ATTENTE · VALIDE · REJETE               (extension spec)
│   └── StatutAlerteFraudeEnum.php                   EN_ATTENTE · CONFIRMEE · FAUX_POSITIF       (extension spec)
│
├── Models/                                         (16 fichiers)
│   ├── Role.php                                     [OK]  hasMany User
│   ├── User.php                                     [OK]  HasApiTokens+HasUuids, getAuthPassword() → mot_de_passe
│   ├── Resident.php                                 [OK]  belongsTo User, hasMany Abonnement
│   ├── Abonnement.php                               [OK]  belongsTo Resident, helper estActif()
│   ├── DemandeResidence.php                         [OK]  belongsTo User (+ validateur via valide_par)
│   ├── Trajet.php                                   [OK]  hasMany Voyage
│   ├── Chaloupe.php                                 [OK]  hasMany Voyage
│   ├── Voyage.php                                   [OK]  belongsTo Trajet/Chaloupe, hasMany Billet
│   ├── Tarif.php                                    [OK]  hasMany Billet
│   ├── Billet.php                                   [OK]  belongsTo Voyage/Tarif/User, hasMany Scan/Payement
│   ├── Payement.php                                 [OK]  belongsTo Billet/User, hasMany MouvementPortefeuille/AlerteFraude
│   ├── Portefeuille.php                             [OK]  belongsTo User, hasMany MouvementPortefeuille
│   ├── MouvementPortefeuille.php                    [OK]  belongsTo Portefeuille/Payement
│   ├── Scan.php                                     [OK]  belongsTo Billet (PAS de agent_id)
│   ├── Notification.php                             [OK]  belongsTo User
│   └── AlerteFraude.php                             [OK]  belongsTo Payement, admin (User via traite_par)
│
├── Repositories/
│   ├── Contracts/
│   │   ├── BilletRepositoryInterface.php            find/create/paginate [OK]
│   │   ├── VoyageRepositoryInterface.php             + decrementPlacesRestantes() [OK] (lock+transaction)
│   │   ├── PayementRepositoryInterface.php            + getSalesReports() [TODO]
│   │   ├── PortefeuilleRepositoryInterface.php        + lockForUpdateAndCredit/Debit() [TODO]
│   │   └── AbonnementRepositoryInterface.php          + activeForResident() [OK]
│   └── Eloquent/
│       ├── BilletRepository.php                     [OK]
│       ├── VoyageRepository.php                      [OK]
│       ├── PayementRepository.php                    [OK] (sauf getSalesReports [TODO])
│       ├── PortefeuilleRepository.php                 [OK] (sauf les 2 méthodes lock [TODO])
│       └── AbonnementRepository.php                  [OK]
│
├── Services/
│   ├── Billetterie/
│   │   ├── BilletPurchaseService.php                [OK]  orchestration complète achat billet
│   │   └── SubServices/
│   │       ├── TarifResolverService.php              [OK]
│   │       ├── ResidentAbonnementCheckerService.php   [OK]  (basé sur date_fin, pas de colonne statut)
│   │       ├── BilletQrTokenGeneratorService.php      [OK]
│   │       ├── PlaceReservationService.php            [OK]
│   │       └── PaymentInitiationService.php           [TODO]  doit déléguer à PaymentOrchestratorService
│   ├── Paiements/
│   │   ├── PaymentOrchestratorService.php            [OK]  crée le Payement + appelle PayDunya
│   │   ├── PayDunya/
│   │   │   ├── PayDunyaClientInterface.php            [OK]
│   │   │   ├── FakePayDunyaClient.php                 [OK]  sandbox, fonctionne sans réseau
│   │   │   ├── PayDunyaClient.php                     [TODO]  HTTP réel, best-effort non vérifié en sandbox réelle
│   │   │   └── PayDunyaWebhookVerifierService.php     [OK]  hash_equals HMAC (laisse passer si secret vide = mode fake)
│   │   └── SubServices/
│   │       ├── FraudDetectionService.php              [TODO]  ne détecte rien encore, renvoie toujours null
│   │       ├── PaymentIntentFactoryService.php         [OK]
│   │       └── PaymentWebhookProcessorService.php      [TODO]  doit dispatcher ConfirmerBilletPaye/CrediterPortefeuille
│   ├── Portefeuille/
│   │   ├── PortefeuilleService.php                    debiter() [OK] / recharger() [TODO] (attend le webhook)
│   │   └── SubServices/
│   │       ├── MouvementPortefeuilleFactoryService.php [OK]
│   │       └── SoldeUpdaterService.php                 [OK]  (lockForUpdate + transaction)
│   ├── Notifications/
│   │   └── NotificationDispatchService.php             [OK] (persiste), [TODO] envoi réel SMS/mail/push
│   ├── Rapports/
│   │   ├── RapportJournalierService.php                [TODO]  retourne une structure vide mais sûre
│   │   └── SubServices/
│   │       ├── RapportVentesService.php                [TODO]
│   │       ├── RapportGainsService.php                 [TODO]
│   │       └── RapportFraudeService.php                [TODO]
│   └── Residents/
│       ├── DemandeResidenceValidationService.php       [OK]  valider()/refuser() écrivent les vraies colonnes
│       └── SubServices/
│           ├── ResidentActivationService.php           [OK]
│           └── AbonnementCreationService.php            [OK]
│
├── Events/                                          (14 fichiers, tous [OK] — value objects typés, Dispatchable)
│   DemandeResidenceSoumise · DemandeResidenceAcceptee · DemandeResidenceRefusee · AbonnementExpireBientot
│   BilletAchete · BilletScanne · PaiementInitie · PaiementWebhookRecu · PaiementAccepte · PaiementRefuse
│   PortefeuilleRecharge · PortefeuilleDebite · FraudeDetectee · RapportJournalierGenere
│
├── Listeners/                                       (21 fichiers, tous [TODO] — câblés au bon Event, handle() vide à écrire)
│   NotifierAgentNouvelleDemande · ActiverResidentEtAbonnement · NotifierResidentDemandeAcceptee
│   NotifierResidentDemandeRefusee · NotifierRenouvellementAbonnement · GenererQrCodeBillet · EnvoyerRecuAchat
│   EnregistrerHistoriqueScan · NotifierEmbarquement · EnregistrerTentativePaiement · TraiterWebhookPayDunya
│   ConfirmerBilletPaye · NotifierPaiementReussi · NotifierPaiementEchoue · RejeterMouvementPortefeuille
│   CrediterPortefeuille · NotifierRechargeReussie · NotifierDebitPortefeuille · BloquerTransactionSuspecte
│   AlerterAdminFraude · EnvoyerRapportJournalierAuxAdmins
│
├── Mail/
│   ├── AlerteFraudeMail.php                          [OK] + vue emails/alertes/fraude.blade.php
│   └── RapportJournalierMail.php                     [OK] + vue emails/rapports/journalier.blade.php (accès défensif ?? [])
│
├── Http/
│   ├── Requests/Api/V1/
│   │   ├── Billetterie/StoreBilletRequest.php         [OK] utilisée par BilletController::store
│   │   ├── Portefeuille/InitierRechargeRequest.php     [OK] utilisée par RechargeController::store
│   │   └── Residents/ValiderDemandeResidenceRequest.php [OK] utilisée par valider()/refuser()
│   ├── Resources/Api/V1/
│   │   ├── BilletResource.php                         [OK] utilisée par BilletController
│   │   ├── VoyageResource.php                          [OK] utilisée par VoyageController
│   │   ├── PortefeuilleResource.php                    [OK] utilisée par PortefeuilleController/RechargeController
│   │   └── DemandeResidenceResource.php                [OK] utilisée par DemandeResidenceController
│   └── Controllers/Api/
│       ├── V1/Auth/AuthController.php                  [OK]  login · logout · me
│       ├── V1/Users/UserController.php                 [OK]  CRUD complet
│       ├── V1/Residents/
│       │   ├── ResidentController.php                  [OK]  CRUD complet
│       │   ├── DemandeResidenceController.php           [OK]  CRUD + valider()/refuser()
│       │   └── AbonnementController.php                 [OK]  CRUD complet
│       ├── V1/Billetterie/
│       │   ├── BilletController.php                    [OK]  CRUD, store() délègue à BilletPurchaseService
│       │   ├── ScanController.php                       [OK]  CRUD, store() = logique VALIDE/DEJA_SCANNE/NON_EMBARQUE
│       │   └── PayementController.php                   [OK]  CRUD complet
│       ├── V1/Voyages/
│       │   ├── VoyageController.php · TrajetController.php · ChaloupeController.php · TarifController.php  [OK] CRUD complets
│       ├── V1/Portefeuille/
│       │   ├── PortefeuilleController.php               [OK]  show() seul
│       │   └── RechargeController.php                   [OK]  store() seul → PortefeuilleService::recharger() ([TODO] partiel)
│       ├── V1/Fraude/AlerteFraudeController.php         [OK]  index/show/update (pas de store/destroy, volontaire)
│       ├── V1/Notifications/NotificationController.php  [OK]  index/show/update/destroy (pas de store, volontaire)
│       └── Webhooks/PayDunyaWebhookController.php        [OK]  vérif. signature + idempotence, hors groupe api/
│
└── Policies/                                        (5 fichiers, tous [OK] — règles simples basées sur user->role->nom)
    AbonnementPolicy · DemandeResidencePolicy · BilletPolicy · ScanPolicy · PayementPolicy

database/
├── migrations/                                      (18 fichiers, tous [OK] — validés par migrate:fresh)
│   0001_01_01_000000_create_users_table.php          (patché : colonnes users réelles)
│   0001_01_01_000001_create_cache_table.php          (par défaut Laravel, intact)
│   0001_01_01_000002_create_jobs_table.php           (par défaut Laravel, intact)
│   2024_01_01_000001_create_roles_table.php
│   2024_01_01_000003_create_residents_table.php
│   2024_01_01_000004_create_abonnements_table.php
│   2024_01_01_000005_create_demande_residences_table.php
│   2024_01_01_000006_create_trajets_table.php
│   2024_01_01_000007_create_chaloupes_table.php
│   2024_01_01_000008_create_voyages_table.php
│   2024_01_01_000009_create_tarifs_table.php
│   2024_01_01_000010_create_billets_table.php
│   2024_01_01_000011_create_payements_table.php
│   2024_01_01_000012_create_portefeuilles_table.php
│   2024_01_01_000013_create_mouvement_portefeuilles_table.php
│   2024_01_01_000014_create_scans_table.php
│   2024_01_01_000015_create_notifications_table.php
│   2024_01_01_000016_create_alerte_fraudes_table.php
│   2024_01_01_000017_create_personal_access_tokens_table.php (uuidMorphs, pas morphs)
│
├── factories/
│   └── UserFactory.php                               [OK]  colonnes réelles (nom/prenom/mot_de_passe/…)
│
└── seeders/
    ├── DatabaseSeeder.php                             [OK]  appelle les 4 seeders ci-dessous
    ├── RoleSeeder.php                                  [OK]  Admin / Agent / Client
    ├── TarifSeeder.php                                 [OK]  1 tarif par CategorieEnum
    ├── ChaloupeSeeder.php                               [OK]  2-3 chaloupes
    └── TrajetSeeder.php                                 [OK]  quelques trajets

routes/
├── api.php                                           [OK]  Route::prefix('v1')->group(...) qui require les 8 fichiers ci-dessous
├── api/v1/
│   ├── auth.php            → login (public), logout/me (auth:sanctum)
│   ├── users.php            → apiResource users (auth:sanctum)
│   ├── residents.php        → apiResource residents, demandes-residence (+valider/refuser), abonnements
│   ├── billetterie.php      → apiResource billets, scans, payements
│   ├── voyages.php          → apiResource voyages, trajets, chaloupes, tarifs
│   ├── portefeuille.php     → GET portefeuille, POST portefeuille/recharge
│   ├── fraude.php           → GET/PUT alertes-fraude (pas de store/destroy)
│   └── notifications.php    → GET/PUT/DELETE notifications (pas de store)
├── webhooks/
│   └── paydunya.php         → POST /webhooks/paydunya (enregistrée hors /api via then: dans bootstrap/app.php)
├── console.php              (patché : Schedule::call(...) rapport journalier 23h55)
└── web.php                  (par défaut Laravel, intact)

config/
└── services.php                                      (patché : clés 'paydunya' et 'fraude' ajoutées)

app/Providers/
├── AppServiceProvider.php                            (patché : binding PayDunyaClientInterface réel/fake)
└── RepositoryServiceProvider.php                     (nouveau : bindings Contracts → Eloquent)

bootstrap/
├── app.php                                           (patché : api: routes/api.php, then: webhook, CSRF except)
└── providers.php                                     (patché : RepositoryServiceProvider enregistré)

.env / .env.example                                   (patchés : clés PayDunya, anti-fraude, queues, rapports)
```

---

## Les 4 grandes couches (rappel de l'architecture)

```
Requête HTTP
   │
   ▼
Controller  ──valide via──▶  FormRequest
   │
   ▼
Service (+ SubServices)  ──dépend de──▶  Repository (Contract)
   │                                          │
   │                                          ▼
   │                                   Repository (Eloquent) ──▶ Model ──▶ DB
   ▼
Event ──▶ Listener (queue dédiée : notifications/paiements/billets/scans/fraude/rapports)
```

- **Controller** : validation d'entrée (FormRequest), appelle un Service, retourne une Resource.
- **Service** : orchestre la logique métier, ne touche jamais Eloquent directement pour les entités qui ont un Repository dédié (Billet, Voyage, Payement, Portefeuille, Abonnement).
- **Repository** : abstraction d'accès aux données (permet de changer l'implémentation sans toucher aux Services).
- **Events/Listeners** : découplent les effets de bord (notifications, mails, crédit portefeuille, fraude) de l'action principale, sur des queues séparées pour scaler indépendamment.

## Modèle de données (relations)

```
Role 1───* User 1───0..1 Resident 1───* Abonnement
                  │
                  ├──* DemandeResidence  (+ validateur via valide_par)
                  ├──1 Portefeuille 1───* MouvementPortefeuille ──* Payement
                  ├──* Payement ──* Billet 1───* Scan
                  ├──* Billet ──1 Voyage ──1 Trajet
                  │            └──1 Voyage ──1 Chaloupe
                  │            └──1 Tarif
                  ├──* Notification
                  └──* AlerteFraude (traite_par)     AlerteFraude ──1 Payement
```

## Ce qu'il reste à faire après génération (priorité suggérée)

1. **`FraudDetectionService::analyser()`** — sans lui, aucune fraude n'est jamais détectée ni bloquée.
2. **`PaymentWebhookProcessorService::traiter()`** — sans lui, un paiement confirmé par PayDunya ne fait jamais passer le billet en `PAYE` ni ne crédite le portefeuille.
3. **`PortefeuilleService::recharger()`** (partie post-confirmation) — brancher le crédit une fois le webhook traité.
4. **Listeners de notification** (`NotifierPaiementReussi`, `NotifierEmbarquement`, etc.) — envoi réel SMS/mail/push.
5. **Rapports** (`RapportVentesService`/`RapportGainsService`/`RapportFraudeService`) — agrégations réelles.
6. **QR code image + reçu d'achat** (`GenererQrCodeBillet`, `EnvoyerRecuAchat`).
7. **Tests Feature** — aucun n'est généré ; docs/api.md en demande explicitement (achat avec abonnement, paiement simulé, webhook idempotent, fraude, rapport journalier).
