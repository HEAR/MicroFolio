<?php
/**
 * Configuration du micro CMS Portfolio
 */

// Configuration de base
define('SITE_NAME', 'Mon Portfolio');
define('ADMIN_PATH', 'admin');

// Détecter le chemin de base automatiquement
function getBasePath() {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Si on est dans un sous-dossier, extraire le chemin
    $scriptDir = dirname($scriptName);
    
    // Si le script est dans admin/, remonter d'un niveau
    if (basename($scriptDir) === 'admin') {
        $scriptDir = dirname($scriptDir);
    }
    
    // Si le script est dans includes/, remonter d'un niveau
    if (basename($scriptDir) === 'includes') {
        $scriptDir = dirname($scriptDir);
    }
    
    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        return '';
    }
    
    // Pour MAMP et autres serveurs locaux
    $path = rtrim($scriptDir, '/');
    return $path;
}

define('BASE_PATH', getBasePath());
define('SITE_URL', BASE_PATH);
define('IMAGES_URL', BASE_PATH . '/assets/images');

// Chemins
define('DATA_DIR', __DIR__ . '/../data');
define('RUBRIQUES_FILE', DATA_DIR . '/rubriques.json');
define('USERS_FILE', DATA_DIR . '/users.json');
define('CUSTOM_CSS_FILE', DATA_DIR . '/custom.css');
define('CUSTOM_JS_FILE', DATA_DIR . '/custom.js');
define('CONFIG_FILE', DATA_DIR . '/config.json');
define('IMAGES_DIR', __DIR__ . '/../assets/images');
define('THUMBNAILS_DIR', __DIR__ . '/../assets/images/thumbs');
// IMAGES_URL est déjà défini plus haut avec BASE_PATH
define('THUMBNAILS_URL', BASE_PATH . '/assets/images/thumbs');

// Créer le dossier data s'il n'existe pas
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Créer le dossier images s'il n'existe pas
if (!file_exists(IMAGES_DIR)) {
    mkdir(IMAGES_DIR, 0755, true);
}

// Créer le dossier thumbnails s'il n'existe pas
if (!file_exists(THUMBNAILS_DIR)) {
    mkdir(THUMBNAILS_DIR, 0755, true);
}

// Initialiser les fichiers s'ils n'existent pas
if (!file_exists(RUBRIQUES_FILE)) {
    file_put_contents(RUBRIQUES_FILE, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if (!file_exists(CUSTOM_CSS_FILE)) {
    $defaultCSS = '/* CSS personnalisé - Design Noir et Blanc */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Helvetica, Arial, sans-serif;
    line-height: 1.6;
    color: #000;
    background: #fff;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Header */
.site-header {
    padding: 40px 0;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 100;
}

.site-title {
    font-size: 24px;
    font-weight: 400;
    margin-bottom: 20px;
    text-align: center;
}

.site-nav {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 30px;
}

.nav-link {
    color: #000;
    text-decoration: none;
    font-size: 14px;
    text-transform: uppercase;
    transition: opacity 0.3s;
}

.nav-link:hover {
    opacity: 0.6;
}

/* Main content */
.site-main {
    padding: 80px 0;
}

.rubrique-section {
    margin-bottom: 120px;
    scroll-margin-top: 100px;
}

.rubrique-section:last-child {
    margin-bottom: 0;
}

.rubrique-title {
    font-size: 32px;
    font-weight: 300;
    margin-bottom: 40px;
    text-align: center;
}

.rubrique-images {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

.rubrique-image-wrapper {
    margin: 0;
    display: flex;
    flex-direction: column;
}

.rubrique-image-wrapper.image-size-small {
    grid-column: span 4;
}

.rubrique-image-wrapper.image-size-medium {
    grid-column: span 6;
}

.rubrique-image-wrapper.image-size-large {
    grid-column: span 8;
}

.rubrique-image-wrapper.image-size-full {
    grid-column: span 12;
}

.rubrique-image {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
}

.rubrique-image-caption {
    margin-top: 10px;
    font-size: 14px;
    color: #000;
    font-style: italic;
    text-align: center;
}

.rubrique-content {
    max-width: 700px;
    margin: 0 auto;
    font-size: 16px;
    line-height: 1.8;
    color: #000;
    text-align: left;
}

/* Styles Markdown */
.rubrique-content h1,
.rubrique-content h2,
.rubrique-content h3,
.rubrique-content h4,
.rubrique-content h5,
.rubrique-content h6 {
    margin-top: 1.5em;
    margin-bottom: 0.5em;
    font-weight: 600;
    line-height: 1.2;
}

.rubrique-content h1 { font-size: 2em; }
.rubrique-content h2 { font-size: 1.75em; }
.rubrique-content h3 { font-size: 1.5em; }
.rubrique-content h4 { font-size: 1.25em; }
.rubrique-content h5 { font-size: 1.1em; }
.rubrique-content h6 { font-size: 1em; }

.rubrique-content p {
    margin-bottom: 1em;
}

.rubrique-content ul,
.rubrique-content ol {
    margin: 1em 0;
    padding-left: 2em;
}

.rubrique-content li {
    margin-bottom: 0.5em;
}

.rubrique-content blockquote {
    border-left: 4px solid #000;
    padding-left: 1em;
    margin: 1em 0;
    color: #000;
    font-style: italic;
}

.rubrique-content code {
    background: #000;
    color: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: Helvetica, Arial, sans-serif;
    font-size: 0.9em;
}

.rubrique-content pre {
    background: #000;
    color: #fff;
    padding: 1em;
    border-radius: 5px;
    overflow-x: auto;
    margin: 1em 0;
}

.rubrique-content pre code {
    background: none;
    padding: 0;
}

.rubrique-content a {
    color: #000;
    text-decoration: underline;
}

.rubrique-content a:hover {
    opacity: 0.6;
}

.rubrique-content img.markdown-image {
    max-width: 100%;
    height: auto;
    margin: 1em 0;
    border-radius: 5px;
}

.rubrique-content iframe {
    max-width: 100%;
    width: 100%;
    height: auto;
    aspect-ratio: 16 / 9;
    margin: 1em 0;
    display: block;
}

.rubrique-content hr {
    border: none;
    border-top: 1px solid #000;
    margin: 2em 0;
}

.rubrique-content strong {
    font-weight: 600;
}

.rubrique-content em {
    font-style: italic;
}

/* Grille de rubriques sur la page d\'accueil */
.rubriques-grid {
    padding: 80px 0;
}

.rubriques-flex {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.rubrique-card {
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    background: #fff;
    transition: opacity 0.3s;
}

.rubrique-card:hover {
    opacity: 0.6;
    text-decoration: none;
    color: inherit;
}

.rubrique-card-image {
    display: block;
    width: auto;
    height: auto;
    max-width: 100%;
}

.rubrique-card-placeholder {
    width: 200px;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #000;
    font-size: 48px;
    opacity: 0.3;
}

.rubrique-card-content {
    padding: 10px 0;
}

.rubrique-card-title {
    font-size: 20px;
    font-weight: 400;
    margin: 0;
    text-align: left;
}

/* Navigation active */
.nav-link.active {
    color: #000;
    font-weight: bold;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 100px 20px;
}

.empty-state h2 {
    font-size: 28px;
    font-weight: 300;
    margin-bottom: 20px;
    color: #000;
}

.empty-state p {
    color: #000;
}

/* Footer */
.site-footer {
    padding: 40px 0;
    text-align: center;
    color: #000;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
    .site-header {
        padding: 20px 0;
    }
    
    .site-title {
        font-size: 20px;
        margin-bottom: 15px;
    }
    
    .site-nav {
        gap: 15px;
    }
    
    .nav-link {
        font-size: 12px;
    }
    
    .site-main {
        padding: 40px 0;
    }
    
    .rubrique-section {
        margin-bottom: 60px;
    }
    
    .rubrique-title {
        font-size: 24px;
        margin-bottom: 30px;
    }
    
    .rubrique-images {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .rubrique-image-wrapper.image-size-small,
    .rubrique-image-wrapper.image-size-medium,
    .rubrique-image-wrapper.image-size-large,
    .rubrique-image-wrapper.image-size-full {
        grid-column: span 1;
    }
    
    .rubriques-flex {
        flex-direction: column;
    }
    
    .rubrique-card {
        width: 100%;
    }
}';
    file_put_contents(CUSTOM_CSS_FILE, $defaultCSS);
}

if (!file_exists(CUSTOM_JS_FILE)) {
    file_put_contents(CUSTOM_JS_FILE, '// JavaScript personnalisé');
}

if (!file_exists(CONFIG_FILE)) {
    $defaultConfig = [
        'external_css' => [],
        'external_js' => [],
        'site_name' => 'Mon Portfolio',
        'site_footer' => '© ' . date('Y') . ' Mon Portfolio'
    ];
    file_put_contents(CONFIG_FILE, json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Fonction pour obtenir le nom du site (depuis config ou constante)
function getSiteName() {
    $config = getConfig();
    return $config['site_name'] ?? SITE_NAME;
}

// Fonction pour obtenir le footer (depuis config)
function getSiteFooter() {
    $config = getConfig();
    return $config['site_footer'] ?? '© ' . date('Y') . ' ' . getSiteName();
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

