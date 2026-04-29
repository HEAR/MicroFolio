<?php
/**
 * Page d'affichage d'une rubrique individuelle
 */
require_once __DIR__ . '/includes/functions.php';

// Récupérer le slug depuis le paramètre GET (routé par .htaccess)
$slug = $_GET['slug'] ?? '';

// Si pas de slug dans GET, essayer de l'extraire de REQUEST_URI
if (empty($slug)) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    
    // Nettoyer l'URI
    if ($basePath && strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }
    
    // Enlever le slash initial
    $requestUri = ltrim($requestUri, '/');
    
    // Extraire le slug (première partie avant / ou ?)
    $parts = explode('/', $requestUri);
    $slug = $parts[0] ?? '';
    
    // Nettoyer le slug (enlever les paramètres de requête et les caractères spéciaux)
    $slug = explode('?', $slug)[0];
    $slug = trim($slug);
    
    // Si le slug est vide ou correspond à "index.php" ou "rubrique.php", ce n'est pas un slug valide
    if (empty($slug) || in_array($slug, ['index.php', 'rubrique.php', 'admin', 'data', 'includes', 'assets'])) {
        $slug = '';
    }
}

$rubriques = getRubriques();
$config = getConfig();

// Filtrer les rubriques pour exclure la page d'accueil du menu
$rubriquesForMenu = array_filter($rubriques, function($r) {
    return empty($r['is_homepage']);
});

// Si le slug est vide, vérifier s'il y a une page d'accueil
if (empty($slug)) {
    $homepageRubrique = getHomepageRubrique();
    if ($homepageRubrique) {
        // Si on accède à la racine et qu'une page d'accueil existe, rediriger vers index.php
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        header('Location: ' . $basePath . '/');
        exit;
    }
}

// Trouver la rubrique par slug
$rubrique = null;
foreach ($rubriques as $r) {
    if ($r['slug'] === $slug) {
        $rubrique = $r;
        break;
    }
}

// Si rubrique non trouvée, rediriger vers l'accueil
if (!$rubrique) {
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    header('Location: ' . $basePath . '/');
    exit;
}

// Si la rubrique trouvée est la page d'accueil et qu'on accède via son slug, rediriger vers la racine
if (!empty($rubrique['is_homepage'])) {
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    header('Location: ' . $basePath . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($rubrique['title']) ?> - <?= getSiteName() ?></title>
    
    <!-- Librairies CSS externes -->
    <?php foreach ($config['external_css'] ?? [] as $css_url): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($css_url) ?>">
    <?php endforeach; ?>
    
    <!-- CSS personnalisé -->
    <style>
        <?= file_exists(CUSTOM_CSS_FILE) ? file_get_contents(CUSTOM_CSS_FILE) : '/* CSS personnalisé */' ?>
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1 class="site-title">
                <a href="<?= BASE_PATH ?>/" style="text-decoration: none; color: inherit;"><?= htmlspecialchars(getSiteName()) ?></a>
            </h1>
            <nav class="site-nav">
                <?php foreach ($rubriquesForMenu as $r): ?>
                    <a href="<?= getRubriqueUrl($r['slug']) ?>" 
                       class="nav-link <?= $r['slug'] === $slug ? 'active' : '' ?>">
                        <?= htmlspecialchars($r['title']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <section class="rubrique-section">
            <div class="container">
                <h2 class="rubrique-title"><?= htmlspecialchars($rubrique['title']) ?></h2>
                
                <?php if (!empty($rubrique['images'])): ?>
                    <div class="rubrique-images">
                        <?php foreach ($rubrique['images'] as $image): 
                            $imgInfo = getImageInfo($image);
                            $fullUrl = normalizeImageUrl($imgInfo['url']);
                            // Utiliser le thumbnail selon la taille de l'image
                            $displayUrl = $imgInfo['thumbnail_url'] ?: $fullUrl;
                        ?>
                            <figure class="rubrique-image-wrapper image-size-<?= htmlspecialchars($imgInfo['size']) ?>">
                                <a href="<?= htmlspecialchars($fullUrl) ?>" target="_blank" class="rubrique-image-link">
                                    <img src="<?= htmlspecialchars($displayUrl) ?>" alt="<?= htmlspecialchars($imgInfo['caption'] ?: $rubrique['title']) ?>" class="rubrique-image">
                                </a>
                                <?php if (!empty($imgInfo['caption'])): ?>
                                    <figcaption class="rubrique-image-caption"><?= htmlspecialchars($imgInfo['caption']) ?></figcaption>
                                <?php endif; ?>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="rubrique-content">
                    <?= parseMarkdown($rubrique['content']) ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p><?= htmlspecialchars(getSiteFooter()) ?></p>
        </div>
    </footer>

    <!-- Librairies JavaScript externes -->
    <?php foreach ($config['external_js'] ?? [] as $js_url): ?>
        <script src="<?= htmlspecialchars($js_url) ?>"></script>
    <?php endforeach; ?>
    
    <!-- JavaScript personnalisé -->
    <script>
        <?= file_exists(CUSTOM_JS_FILE) ? file_get_contents(CUSTOM_JS_FILE) : '// JavaScript personnalisé' ?>
    </script>
</body>
</html>
