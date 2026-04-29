# MicroFolio CMS - Micro CMS de gestion de portfolio

Un micro CMS simple et léger pour gérer votre portfolio.

## Caractéristiques

- **PHP pur** - Aucune dépendance externe
- **Flatfile** - Pas de base de données, tout est stocké dans des fichiers JSON
- **Gestion de rubriques** - Créez et organisez vos projets
- **Backoffice complet** - Interface d'administration avec Bootstrap
- **Personnalisation** - Éditez le CSS et JavaScript du front office
- **Librairies externes** - Ajoutez des librairies CSS/JS en ligne
- **Création automatique du compte admin** - Premier compte créé automatiquement

## Installation

1. Placez les fichiers sur votre serveur web avec PHP 7.4+
2. Assurez-vous que PHP a les permissions d'écriture sur les dossiers `data/` et `assets/images/`
3. Activez le module `mod_rewrite` d'Apache pour les URLs propres
4. Accédez à `/admin/` pour créer votre premier compte administrateur

### Configuration MAMP (local)

Si vous utilisez MAMP, assurez-vous que :
- Le dossier du projet est dans le répertoire `htdocs` de MAMP
- Le `RewriteBase` dans `.htaccess` correspond au nom de votre dossier projet
- Les permissions d'écriture sont correctes sur `data/` et `assets/images/`

## Structure

```
/
├── admin/                      # Backoffice
│   ├── index.php              # Page de connexion/création de compte
│   ├── dashboard.php           # Tableau de bord
│   ├── rubriques.php          # Gestion des rubriques
│   ├── custom.php             # Personnalisation CSS/JS
│   ├── settings.php           # Paramètres du site (titre, footer)
│   ├── profile.php            # Gestion du profil utilisateur
│   ├── upload.php             # Upload d'images
│   ├── delete-image.php       # Suppression d'images
│   ├── generate-thumbnails.php # Régénération des thumbnails
│   └── fix-images.php         # Correction des URLs d'images
├── assets/                     # Ressources statiques
│   └── images/                # Images uploadées
│       └── thumbs/             # Thumbnails générés automatiquement
│                               # (4 tailles par image : small, medium, large, full)
├── data/                      # Données (fichiers plats)
│   ├── rubriques.json         # Rubriques du portfolio
│   ├── users.json             # Comptes utilisateurs
│   ├── config.json            # Configuration (librairies externes, titre, footer)
│   ├── custom.css             # CSS personnalisé
│   └── custom.js              # JavaScript personnalisé
├── includes/                  # Fichiers PHP réutilisables
│   ├── config.php             # Configuration et chemins
│   ├── functions.php          # Fonctions utilitaires
│   └── auth.php               # Authentification
├── index.php                  # Front office - Page d'accueil
│                              # Affiche la liste de toutes les rubriques
├── rubrique.php               # Front office - Page d'une rubrique individuelle
│                              # Affiche le contenu d'une rubrique avec ses images
│                              # Accessible via URL propre : /nom-de-la-rubrique
└── .htaccess                  # Règles de réécriture d'URL pour les URLs propres
```

## Utilisation

### Création du compte admin

Lors de la première visite sur `/admin/`, vous serez invité à créer le compte administrateur.

### Gestion des rubriques

1. Connectez-vous au backoffice
2. Allez dans "Rubriques"
3. Créez, modifiez ou supprimez des rubriques
4. Réorganisez-les par glisser-déposer

### Front office

Le front office est composé de deux pages principales :

- **`index.php`** : Page d'accueil qui affiche toutes les rubriques sous forme de grille. Chaque rubrique est représentée par sa première image et son titre. Cliquez sur une rubrique pour voir son contenu complet.

- **`rubrique.php`** : Page d'affichage d'une rubrique individuelle. Cette page :
  - Affiche le titre de la rubrique
  - Affiche toutes les images de la rubrique avec leurs légendes et dimensions
  - Affiche le contenu Markdown de la rubrique
  - Est accessible via une URL propre : `/nom-de-la-rubrique` (grâce au `.htaccess`)

### Dossier assets

Le dossier `assets/images/` contient :
- **Images originales** : Toutes les images uploadées via le backoffice sont stockées ici
- **Dossier `thumbs/`** : Contient les thumbnails générés automatiquement
  - Chaque image génère 4 thumbnails selon sa dimension : `thumb_small_`, `thumb_medium_`, `thumb_large_`, `thumb_full_`
  - Les thumbnails sont utilisés automatiquement selon la dimension choisie pour l'affichage
  - Ils sont optimisés pour améliorer les performances du site

### Personnalisation

Dans la section "Personnalisation" du backoffice :
- Éditez le CSS personnalisé avec un éditeur de code
- Éditez le JavaScript personnalisé
- Ajoutez des librairies CSS/JS externes (CDN)

## Sécurité

- Les fichiers dans `data/` sont protégés par `.htaccess`
- Les mots de passe sont hashés avec `password_hash()`
- Les sessions PHP sont utilisées pour l'authentification

## Personnalisation du design

Le front office utilise un design minimaliste par défaut. Vous pouvez le personnaliser complètement via :
- Le CSS personnalisé dans le backoffice
- Les librairies externes (Bootstrap, Tailwind, etc.)
- Le JavaScript personnalisé

## Support

Ce CMS est conçu pour être simple et extensible. N'hésitez pas à modifier le code selon vos besoins.

