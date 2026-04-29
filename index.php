<?php
require_once __DIR__ . '/includes/functions.php';

if (isMaintenanceMode() && !isLoggedIn()) {
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maintenance - <?= htmlspecialchars(getSiteName()) ?></title>
        <style>
            body { font-family: Helvetica, Arial, sans-serif; margin: 0; background: #fff; color: #000; }
            .maintenance { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
            .maintenance-box { max-width: 640px; text-align: center; }
            .maintenance-box h1 { margin-bottom: 12px; font-size: 32px; font-weight: 400; }
            .maintenance-box p { margin-bottom: 24px; line-height: 1.6; }
            .maintenance-box a { color: #000; }
        </style>
    </head>
    <body>
        <main class="maintenance">
            <section class="maintenance-box">
                <h1>Site temporairement en maintenance</h1>
                <p>Le site est en cours de mise a jour. Merci de revenir un peu plus tard.</p>
            </section>
        </main>
    </body>
    </html>
    <?php
    exit;
}

$rubriques = getRubriques();
$config = getConfig();

// Vérifier si une rubrique est définie comme page d'accueil
$homepageRubrique = getHomepageRubrique();
$rubrique = $homepageRubrique; // Utiliser la rubrique page d'accueil si elle existe
$showMenuWithoutHomepage = !array_key_exists('show_menu_without_homepage', $config) || !empty($config['show_menu_without_homepage']);
$shouldShowMenu = $homepageRubrique || $showMenuWithoutHomepage;

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
        /* Fallback tailles additionnelles si non définies dans le CSS personnalisé */
        .rubrique-image-wrapper.image-size-quarter { grid-column: span 3; }
        .rubrique-image-wrapper.image-size-sixth { grid-column: span 2; }
        @media (max-width: 768px) {
            .rubrique-image-wrapper.image-size-quarter,
            .rubrique-image-wrapper.image-size-sixth { grid-column: span 1; }
        }
    </style>
</head>
<body>
    <?php if (isMaintenanceMode() && isLoggedIn()): ?>
        <div style="background:#111;color:#fff;padding:10px 16px;text-align:center;font-size:14px;">
            Vous consultez le site en administrateur
        </div>
    <?php endif; ?>
    <header class="site-header">
        <div class="container">
            <h1 class="site-title">
                <a href="<?= BASE_PATH ?>/" style="text-decoration: none; color: inherit;"><?= htmlspecialchars(getSiteName()) ?></a>
            </h1>
            <?php if ($shouldShowMenu): ?>
                <nav class="site-nav">
                    <?php foreach ($rubriquesForMenu as $r): ?>
                        <a href="<?= getRubriqueUrl($r['slug']) ?>" class="nav-link">
                            <?= htmlspecialchars($r['title']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </div>
    </header>

    <main class="site-main">
        <?php if ($rubrique): ?>
            <!-- Afficher la rubrique page d'accueil -->
            <section class="rubrique-section">
                <div class="container">
                    <h2 class="rubrique-title"><?= htmlspecialchars($rubrique['title']) ?></h2>
                    
                    <?php $visibleImages = array_values(array_filter($rubrique['images'], function($img) { return !isImageHidden($img); })); ?>
                    <?php $galleryPosition = (($rubrique['gallery_position'] ?? 'before') === 'after') ? 'after' : 'before'; ?>
                    <?php if (!empty($visibleImages) && $galleryPosition === 'before'): ?>
                        <div class="rubrique-images">
                            <?php foreach ($visibleImages as $image): 
                                $imgInfo = getImageInfo($image);
                                $fullUrl = normalizeImageUrl($imgInfo['url']);
                                // Utiliser le thumbnail selon la taille de l'image
                                $displayUrl = $imgInfo['thumbnail_url'] ?: $fullUrl;
                            ?>
                                <figure class="rubrique-image-wrapper image-size-<?= htmlspecialchars($imgInfo['size']) ?>">
                                    <a href="<?= htmlspecialchars($fullUrl) ?>" target="_blank" class="rubrique-image-link" data-group="gallery">
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

                    <?php if (!empty($visibleImages) && $galleryPosition === 'after'): ?>
                        <div class="rubrique-images">
                            <?php foreach ($visibleImages as $image): 
                                $imgInfo = getImageInfo($image);
                                $fullUrl = normalizeImageUrl($imgInfo['url']);
                                // Utiliser le thumbnail selon la taille de l'image
                                $displayUrl = $imgInfo['thumbnail_url'] ?: $fullUrl;
                            ?>
                                <figure class="rubrique-image-wrapper image-size-<?= htmlspecialchars($imgInfo['size']) ?>">
                                    <a href="<?= htmlspecialchars($fullUrl) ?>" target="_blank" class="rubrique-image-link" data-group="gallery">
                                        <img src="<?= htmlspecialchars($displayUrl) ?>" alt="<?= htmlspecialchars($imgInfo['caption'] ?: $rubrique['title']) ?>" class="rubrique-image">
                                    </a>
                                    <?php if (!empty($imgInfo['caption'])): ?>
                                        <figcaption class="rubrique-image-caption"><?= htmlspecialchars($imgInfo['caption']) ?></figcaption>
                                    <?php endif; ?>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
                                <?php
                                    $cardImage = getRubriqueThumbnailImage($r);
                                    if ($cardImage):
                                    $cardImgInfo = getImageInfo($cardImage);
                                    // Utiliser un thumbnail small pour la carte de la page d'accueil
                                    $thumbnailSmall = getThumbnailUrl($cardImgInfo['url'], 'small');
                                    $displayUrl = $thumbnailSmall ? normalizeImageUrl($thumbnailSmall) : normalizeImageUrl($cardImgInfo['url']);
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

