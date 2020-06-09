
# Plexus v1.0

## Présentation

Plexus est un micro-framework PHP conçu pour faciliter la création de service Web.

Plexus possède les composants suivants : 
- un routeur pour avoir de belles URL paramétrables
- une organisation par module et controlleur pour rendre plus lisible la structure de votre application
- un système de modèle pour vous concentrer sur le traitement et l'utilisation de vos données plutot que la gestion des 
requêtes SQL 
- une intégration de différents moteurs de templates : PHPView ou 
- un système de gestion de formulaire pour facilement traiter et valider les données rentrées par l'utilisateur
- de nombreuses classes d'aide pour vous faire gagner du temps dans le développement de votre application
- la possibilité d'ajouter des services pour augmenter ses capacités

## Installation rapide

Pour récupérer toutes les dépendances nécessaires, le plus simple est d'utiliser `composer`. Exécuter la commande suivante 
dans le dossier racine de votre projet :

``` 
composer require beardedbyte/plexus
```

Commençons par créer un dossier nommé `public` dans le dossier de votre projet. C'est sur ce dossier que devra pointer 
votre serveur web. Au sein de ce dossier, créez tout d'abord un fichier `.htaccess` avec le contenu suivant qui va 
permettre de rediriger les requêtes vers le fichier `index.php` :
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . /index.php [L]
``` 
Créez ensuite le fichier `index.php` avec le contenu suivant :
```php
<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new \Acme\Application();
$app->run();
```


Il faut ensuite créer un autre dossier à la racine de votre projet qui contiendra toute la logique de votre application.
Pour la suite nous nommerons ce dossier `src`.
 
Dans ce dernier, créez un fichier `Application.php` avec le contenu suivant :

```php
<?php

namespace Acme;


class Application extends \Plexus\Application {
    public function __construct() {
        parent::__construct(__DIR__.'/../');
    }
}
```

Avant de poursuivre, enregistrons le namespace `Acme` auprès de composer en ajoutant les lignes suivant au fichier 
`composer.json` :

```json
{
  "require": {
    ...
  },
  "autoload": {
      "psr-4": {"Acme\\": "src/"}
  }
}

```

Pensez à regénérer les fichiers de chargement de composer en utilisant la commande suivante dans le dossier de 
votre projet :
```
composer dump-autoload
```


Vous pouvez maintenant accéder au site web pour permettre à l'application créer les les dossiers du projet pour vous.
Vous devriez voir apparaitre un dossier `src/Modules`

Dans ce dossier, créez le répertoire `HelloModule` et dans ce répertoire créer le fichier `HelloModule.php` avec le 
contenu suivant :
```php
<?php

namespace Acme\Modules\HelloModule;


class HelloModule extends \Plexus\Module {
    
}
```

Une fois de plus, chargé le site web pour que l'application créer l'arborescence du module `HelloModule`.
Dans le dossier `src/Modules/HelloModule/Controlers`, créez le fichier `Hello.php` avec le contenu suivant :
```php
<?php

namespace Acme\Modules\HelloModule\Controlers;


class Hello extends \Plexus\Controler {
    
    public function hello($name) {
        echo "Hello $name !";
    }
    
}
```

Dernière étape avant de pouvoir enfin tester votre première application : enregistrer la route en ajoutant ces lignes 
au fichier `src/Modules/HelloModule/config/routes.yaml` :
```yaml
hello-name:
    method: 'get'
    path: '/hello/[*:name]'
    action: 'HelloModule:Hello:hello'
```

Vous pouvez maintenant accéder à l'url `/hello/world` pour voir le résultat !

Cette installation rapide est maintenant terminée, je vous invite donc à poursuivre votre lecture pour découvrir les 
nombreux composants qui vont vous faciliter la vie ! 


## Présentation générale des composants

### Généraux 

* **Application** : Classe représentant le point de lancement du service web
* **Container** : Classe liée à `Application` permettant d'accéder à tous les composants de l'application
* **Configuration** : Classe faisant le lien avec un fichier de configuration `.yaml` en permettant notament de récupérer
* **Service** : Classe permettant d'intégrer de nouvelles capacités à l'application 
son contenu

### Routeur

* **Routeur** : Classe chargées de controle des routes
* **Route** : Classe associant une requête HTTP à une action
* **Request** : Classe représentant une requête et faciliant l'accès à ses propriétés
* **Response** : Classe représentant la réponse de l'application

### Modules et Controlleur

* **Module** : Classe représentant un module et chargée d'instancier ses controlleurs associés ainsi que ses templates
* **Controler** : Classe regroupant des actions qui seront liés à des routes

### Modèle et base de données

* **ModelManager** : Classe effectuant le lien entre les modèles de données utilisés par l'application et la base de 
données
* **Model** : Représentation des données sous forme d'entité
* **QueryBuilder** : Classe permettant de composer des requêtes SQL automatiquement

### Gestion des formulaires

* **Form** : Classe représentant un formulaire et permettant la conception, le traitement et la validation des données 
associées
* **Field** : Classe représentant un champ au sein d'un formulaire
* **Validator** : Classe permettant de valider les données d'un champ

### Différents utilitaires

* **Logger** : Classe permettant d'enregistrer facilement messages et exception dans des journaux
* **Path** : Classe permettant de générer facilement des chemins d'accès
* **Randomizer** : Classe permettant de générer des valeurs aléatoires selon différents critères
* **RegExp** : Classe faciliant l'utilisation des expressions régulières
* **Text** : Classe permettant d'effectuer de nombreuses opérations sur les chaines de caractères

## Pour aller plus loin

Pour en apprendre plus sur les composants de Plexus et leur utilisation, je vous invite à consulter le wiki du dépôt !

## License

