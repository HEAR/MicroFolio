<?php
require_once __DIR__ . '/includes/functions.php';

$rubriques = getRubriques();
$config = getConfig();

// Vérifier si une rubrique est définie comme page d'accueil
$homepageRubrique = getHomepageRubrique();
$rubrique = $homepageRubrique; // Utiliser la rubrique page d'accueil si elle existe

// Filtrer les rubriques pour exclure la page d'accueil du menu
$rubriquesForMenu = array_filter($rubriques, function($r) {
    return empty($r['is_homepage']);
});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $rubrique ? htmlspecialchars($rubrique['title']) . ' - ' . getSiteName() : getSiteName() ?></title>
    
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
                    <a href="<?= getRubriqueUrl($r['slug']) ?>" class="nav-link">
                        <?= htmlspecialchars($r['title']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <?php if ($rubrique): ?>
            <!-- Afficher la rubrique page d'accueil -->
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
        <?php elseif (empty($rubriquesForMenu)): ?>
            <section class="empty-state">
                <div class="container">
                    <h2>Aucune rubrique pour le moment</h2>
                    <p>Connectez-vous à l'administration pour créer votre première rubrique.</p>
                </div>
            </section>
        <?php else: ?>
            <!-- Afficher la liste des rubriques -->
            <section class="rubriques-grid">
                <div class="container">
                    <div class="rubriques-flex">
                        <?php foreach ($rubriquesForMenu as $r): ?>
                            <a href="<?= getRubriqueUrl($r['slug']) ?>" class="rubrique-card">
                                <?php if (!empty($r['images'])): 
                                    $firstImg = getImageInfo($r['images'][0]);
                                    // Utiliser un thumbnail small pour la page d'accueil
                                    $thumbnailSmall = getThumbnailUrl($firstImg['url'], 'small');
                                    $displayUrl = $thumbnailSmall ? normalizeImageUrl($thumbnailSmall) : normalizeImageUrl($firstImg['url']);
                                ?>
                                    <img src="<?= htmlspecialchars($displayUrl) ?>" alt="<?= htmlspecialchars($r['title']) ?>" class="rubrique-card-image">
                                <?php else: ?>
                                    <div class="rubrique-card-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="rubrique-card-content">
                                    <h3 class="rubrique-card-title"><?= htmlspecialchars($r['title']) ?></h3>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
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

