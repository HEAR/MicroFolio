<?php
/**
 * Script pour corriger les URLs d'images incorrectes dans les rubriques
 */
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$rubriques = getRubriques();
$updated = false;

foreach ($rubriques as &$rubrique) {
    if (!empty($rubrique['images'])) {
        foreach ($rubrique['images'] as &$image) {
            if (is_array($image) && isset($image['url'])) {
                // Corriger les URLs qui contiennent /admin/assets/images/
                if (strpos($image['url'], '/admin/assets/images/') !== false) {
                    $image['url'] = str_replace('/admin/assets/images/', '/assets/images/', $image['url']);
                    $updated = true;
                }
            } elseif (is_string($image)) {
                // Ancien format : corriger directement
                if (strpos($image, '/admin/assets/images/') !== false) {
                    $image = str_replace('/admin/assets/images/', '/assets/images/', $image);
                    $updated = true;
                }
            }
        }
    }
}

if ($updated) {
    if (writeJsonFile(RUBRIQUES_FILE, $rubriques)) {
        echo "✅ URLs d'images corrigées avec succès !";
    } else {
        echo "❌ Erreur lors de la sauvegarde";
    }
} else {
    echo "ℹ️ Aucune URL à corriger";
}
?>
