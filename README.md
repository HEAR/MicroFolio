# MicroFolio CMS - Micro CMS de portfolio

Un micro CMS simple et léger pour gérer votre portfolio.

## Caractéristiques

- **PHP pur** - Pas de framework, pas de base de données
- **Flatfile** - Données stockées en JSON (`data/`)
- **Gestion des pages** - Création, édition, suppression, réorganisation
- **Médias** - Upload images/PDF, masquage image, choix thumbnail, tri des images
- **Backoffice complet** - Dashboard, paramètres, profil, personnalisation
- **Maintenance et sécurité** - CSRF, protection brute-force, headers HTTP, `.htaccess`
- **Personnalisation front** - CSS/JS personnalisés + librairies externes

## Installation

1. Placez les fichiers sur un serveur web avec PHP 7.4+
2. Vérifiez les droits d'écriture sur `data/`, `assets/images/`, `assets/images/thumbs/`, `assets/docs/`
3. Activez `mod_rewrite` (Apache) pour les URLs propres
4. Ouvrez `/admin/` pour créer le premier compte administrateur

### Configuration MAMP (local)

- Placez le projet dans `htdocs`
- Vérifiez que le `.htaccess` de la racine est actif
- Vérifiez les droits d'écriture des dossiers ci-dessus

## Utilisation du CMS

### 1) Compte administrateur

Lors de la première visite sur `/admin/`, le CMS propose la création du compte admin.

### 2) Gestion des pages

Depuis `Pages` (`admin/rubriques.php`) :

- créer, modifier, supprimer des pages,
- réorganiser les pages par glisser-déposer,
- définir la page d'accueil,
- choisir la position de la galerie (avant/après le contenu),
- gérer les images (upload, URL externe, tri, masquage, tailles, thumbnail),
- gérer les PDF (upload, copie, insertion Markdown, suppression).

### 3) Front office

- `index.php` :
  - affiche la page d'accueil si définie,
  - sinon affiche une grille de pages,
  - la vignette de carte utilise l'image thumbnail choisie (sinon première image visible).
- `rubrique.php` :
  - affiche une page individuelle avec galerie + contenu Markdown.

### 4) Paramètres et personnalisation

- `Paramètres` (`admin/settings.php`) :
  - titre du site, footer,
  - mode maintenance,
  - affichage menu sans page d'accueil,
  - activation/désactivation de la détection d'échecs de mot de passe,
  - régénération manuelle des `.htaccess` de sécurité.
- `Personnalisation` (`admin/custom.php`) :
  - CSS personnalisé,
  - JavaScript personnalisé,
  - URLs CSS/JS externes,
  - onglet "Structure HTML".

## Structure

```
/
├── admin/
│   ├── index.php
│   ├── dashboard.php
│   ├── rubriques.php
│   ├── custom.php
│   ├── settings.php
│   ├── profile.php
│   ├── upload.php
│   ├── upload-document.php
│   ├── delete-image.php
│   ├── delete-document.php
│   ├── save-images.php
│   └── generate-thumbnails.php
├── assets/
│   ├── .htaccess
│   ├── images/
│   │   ├── .htaccess
│   │   └── thumbs/
│   │       └── .htaccess
│   └── docs/
│       └── .htaccess
├── data/
│   ├── .htaccess
│   ├── rubriques.json
│   ├── users.json
│   ├── config.json
│   ├── login_attempts.json
│   ├── custom.css
│   └── custom.js
├── includes/
│   ├── config.php
│   ├── functions.php
│   └── auth.php
├── index.php
├── rubrique.php
└── .htaccess
```

## Sécurité

- Protection CSRF sur les actions sensibles
- Sessions sécurisées (`HttpOnly`, `SameSite`)
- Protection anti brute-force configurable
- Headers HTTP de sécurité (CSP, X-Frame-Options, etc.)
- Protection `.htaccess` des dossiers sensibles et upload

## Support

MicroFolio a été vibecodé pour les étudiant·es de la la Haute école des arts du Rhin.

