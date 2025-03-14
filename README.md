# Site web de MyCLAP

Développement réalisé par Jean-Baptiste Caplan entre fin décembre/début janvier 2020. 
([jnbptstcpln@gmail.com](mailto:jnbptstcpln@gmail.com))

## Description rapide

Ce site permet à travers différentes sections de :
- Publier des vidéos
- Classer les vidéos par catégorie
- Construire des playlists
- Permettre aux centraliens de s'authentifier avec leur compte CLA
- Définir la politique d'accès des vidéos et playlist
    - Publique : n'importe qui peut y accéder
    - Non répertoriée : seules les personnes disposant du lien peuvent y accéder
    - Centraliens : tous les centraliens connectés via CLA peuvent y accéder
    - Privée : seuls les membres du CLAP autorisé peuvent y accéder.
    
Après avoir récupéré le code depuis le dépôt GitHub il faut installer les dépendances en utilisant 
[Composer](https://getcomposer.org) :
```
    composer install
    composer dumpautoload
```

Vérifier bien que que votre serveur web pointe sur le dossier `public` et que le fichier `public/.htaccess` est bien 
pris en compte.

Vérifier aussi que l'utilisateur du serveur web ait les permissions en écriture sur tout le dossier de l'application.

Avant de procéder au premier lancement, il faut aussi faire un tour dans le dossier `config` (le créer à la racine de 
l'application si il n'existe pas) et mettre en place les fichiers suivants :

Fichier `config/environment.yaml` :

```yaml
# Valeurs possibles : 
# - `dev` : permet d'afficher le stacktrace des exceptions 
# - `prod` : se contente d'une affichage lambda lors des exceptions
env: dev

# Défini le nom d'hôte à travers lequel les utilisateurs ont accès au site
host: 'http://my.le-clap.fr'

# Défini les paramètres du service d'authentification de CLA
cla-auth-host: "https://centralelilleassos.fr"
cla-auth-identifier: "myclap"
```

Fichier `config/databases.yaml` :  
```yaml
# Configuration de la base de données principale
# À ajuster selon votre environnement
myclap:
  type: 'mysql'
  host: 'localhost'
  port: '3306'
  database: 'myclap'
  username: 'username'
  password: 'password'
```