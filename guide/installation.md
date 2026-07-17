# Guide d'Installation de Go Gorée

Suivez ces étapes pour installer et exécuter le projet dans votre environnement de développement.

### Prérequis
*   PHP >= 8.3
*   Composer
*   PostgreSQL
*   Docker (recommandé pour Redis) ou WSL (sur Windows)

### 1. Clonage du dépôt et navigation
Clonez le dépôt Git et placez-vous dans le répertoire du projet :
```bash
git clone <URL_DU_DEPOT>
cd Go-Goree-backend
```

### 2. Installation des dépendances PHP
Installez les bibliothèques requises par le projet à l'aide de Composer :
```bash
composer install
```

### 3. Configuration du fichier d'environnement (.env)
Copiez le fichier d'exemple pour créer votre fichier de configuration local :

*   **Sur Windows (PowerShell)** :
    ```powershell
    Copy-Item .env.example .env
    ```
*   **Sur Linux / macOS / Git Bash** :
    ```bash
    cp .env.example .env
    ```

### 4. Génération de la clé d'application
Générez la clé de sécurité unique de Laravel :
```bash
php artisan key:generate
```

### 5. Configuration de la base de données PostgreSQL
1.  Créez une base de données PostgreSQL nommée `go_goree` sur votre serveur local.
2.  Ouvrez votre fichier `.env` nouvellement créé et configurez les identifiants de connexion :
    ```dotenv
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=go_goree
    DB_USERNAME=postgres
    DB_PASSWORD=votre_mot_de_passe
    ```
3.  Exécutez les migrations de base de données et chargez les données de démo initiales (seeders) :
    ```bash
    php artisan migrate:fresh --seed
    ```

### 6. Configuration et démarrage de Redis
Le projet utilise Redis pour la gestion du cache et des verrous de concurrence.

#### Option A : Via Docker (Recommandé)
Lancez une instance Redis en arrière-plan à l'aide du conteneur officiel :
```bash
docker run -d --name go-goree-redis -p 6379:6379 redis:alpine
```

#### Option B : Via WSL (Windows Subsystem for Linux)
Si vous utilisez Windows sans Docker, vous pouvez installer Redis sur votre distribution WSL (ex. Ubuntu) :
1.  Ouvrez votre terminal WSL.
2.  Exécutez les commandes suivantes :
    ```bash
    sudo apt update
    sudo apt install redis-server -y
    ```
3.  Démarrez le service Redis :
    ```bash
    sudo service redis-server start
    ```
4.  Vérifiez que Redis fonctionne en exécutant :
    ```bash
    redis-cli ping
    # Réponse attendue : PONG
    ```

Dans votre fichier `.env`, assurez-vous que Redis est correctement configuré :
```dotenv
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

### 7. Démarrage des 3 services requis

Pour que toutes les fonctionnalités de l'application (traitement asynchrone, tâches planifiées, WebSockets) fonctionnent, vous devez exécuter les trois processus suivants dans des terminaux séparés :

#### 1er Serveur : Le Queue Worker (Files d'attente)
Il traite l'envoi des e-mails transactionnels, les notifications en arrière-plan et la détection de fraudes.
```bash
php artisan queue:work --queue=notifications,fraude,rapports,default
```

#### 2ème Serveur : Le Task Scheduler (Planificateur)
Il gère l'exécution des tâches planifiées, comme l'expiration automatique des billets ou la génération des rapports financiers quotidiens à 23h55.
```bash
php artisan schedule:work
```

#### 3ème Serveur : Laravel Reverb (WebSockets)
Il gère la communication bidirectionnelle en temps réel pour envoyer les notifications instantanées au frontend.
```bash
php artisan reverb:start
```

*(Note : Pour lancer le serveur web principal de l'API, ouvrez un 4ème terminal et tapez : `php artisan serve`)*

---

## 8. Guide d'importation de la Collection Postman

Pour tester les différents endpoints de l'API (authentification, achat de billets, scans, etc.), une collection et un environnement Postman préconfigurés sont disponibles dans le projet.

### 8.1. Localisation des fichiers
Les fichiers se trouvent dans le répertoire suivant :
*   Collection : [`docs/postman/Go-Goree.postman_collection.json`](../docs/postman/Go-Goree.postman_collection.json)
*   Environnement : [`docs/postman/Go-Goree.postman_environment.json`](../docs/postman/Go-Goree.postman_environment.json)

### 8.2. Importation dans Postman
1.  Lancez l'application **Postman**.
2.  Cliquez sur le bouton **Import** situé en haut à gauche.
3.  Glissez-déposez ou sélectionnez les deux fichiers JSON ci-dessus.
4.  Une fois l'importation réussie, sélectionnez l'environnement **« Go Gorée - Local »** dans la liste déroulante des environnements (située en haut à droite de l'écran Postman).

### 8.3. Test et Authentification automatique
1.  Le processus de seed de l'étape 5 a pré-généré deux comptes de démo :
    *   **Admin** : `admin@goree.sn` (mot de passe : `Admin@1234`)
    *   **Client** : `client@goree.sn` (mot de passe : `Client@1234`)
2.  Déroulez la collection importée, ouvrez le dossier **0. Authentification**, puis sélectionnez la requête **Login (Client)** ou **Login (Admin)**.
3.  Cliquez sur **Send**.
4.  La collection Postman contient un script de test automatique qui récupère le token d'accès (`access_token`) dans la réponse HTTP et met à jour automatiquement la variable d'environnement `token`.
5.  Les requêtes suivantes utiliseront automatiquement ce token pour s'authentifier.
