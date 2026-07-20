# Présentation du Projet Go Gorée

Ce document présente une vue d'ensemble du projet **Go Gorée**, décrit son architecture logicielle et détaille ses fonctionnalités principales.

---

## 1. Introduction au Projet

**Go Gorée** est le système backend d'une plateforme de billetterie et de gestion de transport pour les chaloupes reliant Dakar à l'île de Gorée. L'application est conçue pour gérer :
*   **Les Utilisateurs & Rôles** : Clients (qui peuvent s'enregistrer et acheter des billets), Agents/Contrôleurs (qui scannent les billets à l'embarquement), et Administrateurs (gestion globale).
*   **La Résidence** : Soumission de demandes de statut résident par les clients et validation par les administrateurs pour bénéficier de tarifs préférentiels.
*   **Les Abonnements** : Souscriptions payantes (mensuelles, semestrielles ou annuelles) pour les résidents, leur permettant d'obtenir des billets gratuits.
*   **La Billetterie** : Achat de billets via un portefeuille virtuel interne ou via la passerelle de paiement **PayDunya**.
*   **La Sécurité & Anti-fraude** : Détection des doubles billets pour un même voyage et des scans multiples d'un même billet.
*   **Le Temps Réel & Notifications** : Envoi de courriels/SMS et diffusion en temps réel (via WebSockets) d'événements (nouvelles demandes, alertes fraude).

---

## 2. Architecture & Arborescence du Projet

Le projet suit l'architecture standard d'une application Laravel moderne enrichie d'une couche Repository pour découpler la persistance et les services métiers.

### Structure des répertoires principaux

*   **[`app/`](../app/)** : Contient le cœur de la logique applicative.
    *   **[`app/Enums/`](../app/Enums/)** : Enums PHP natifs définissant les différents statuts et modes de paiement (ex. `StatutBilletEnum`, `ModePayementEnum`).
    *   **[`app/Models/`](../app/Models/)** : Modèles Eloquent décrivant les tables de la base de données et leurs relations.
    *   **[`app/Repositories/`](../app/Repositories/)** : Abstraction de l'accès aux données. Divisé en `Contracts` (interfaces) et `Eloquent` (implémentations).
    *   **[`app/Services/`](../app/Services/)** : Services métiers orchestrant les règles de gestion (achat de billets, détection de fraude, gestion du portefeuille).
    *   **[`app/Events/`](../app/Events/)** : Événements déclenchés par l'application (ex. `BilletAchete`, `FraudeDetectee`).
    *   **[`app/Listeners/`](../app/Listeners/)** : Écouteurs d'événements exécutés de manière asynchrone via des files d'attente (queues).
    *   **[`app/Mail/`](../app/Mail/)** : Classes d'envoi d'emails (reçus d'achat, alertes de fraude, rapports journaliers).
    *   **[`app/Http/`](../app/Http/)** :
        *   `Controllers/` : Contrôleurs de l'API (V1) et des webhooks.
        *   `Requests/` : FormRequests validant et typant les données entrantes.
        *   `Resources/` : Transformateurs de modèles en réponses JSON normalisées.
    *   **[`app/Policies/`](../app/Policies/)** : Règles d'autorisation basées sur les rôles des utilisateurs.
*   **[`database/`](../database/)** :
    *   `migrations/` : Fichiers de structure de la base de données.
    *   `seeders/` : Données de test et de référence (tarifs, rôles, chaloupes, trajets).
*   **[`routes/`](../routes/)** :
    *   `api.php` et `api/v1/` : Endpoints de l'API REST.
    *   `webhooks/` : Endpoints de réception des notifications de paiement PayDunya.
    *   `console.php` : Tâches planifiées de la console.

### Modèle de données & Relations clés

```
Role (Admin/Agent/Client) 1 ─── * User
                                  ├── 0..1 Resident ── * Abonnement
                                  ├── * DemandeResidence (statut de résident)
                                  ├── 1 Portefeuille ── * MouvementPortefeuille ── * Payement
                                  ├── * Payement ── * Billet ── * Scan
                                  └── * Notification
```

Pour consulter les instructions de configuration et d'installation, voir le **[Guide d'Installation](./installation.md)**.
