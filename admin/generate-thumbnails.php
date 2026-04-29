<?php
/**
 * Script pour générer les thumbnails des images existantes
 */
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$rubriques = getRubriques();
$generated = 0;
$errors = 0;

foreach ($rubriques as $rubrique) {
    if (!empty($rubrique['images'])) {
        foreach ($rubrique['images'] as $image) {
            $imgInfo = getImageInfo($image);
            $imageUrl = $imgInfo['url'];
            
            // Nettoyer l'URL pour obtenir le chemin du fichier
            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
            $filePath = $imageUrl;
            
            // Enlever BASE_PATH de l'URL si présent
            if ($basePath && strpos($filePath, $basePath) === 0) {
                $filePath = substr($filePath, strlen($basePath));
            }
            
            // Enlever le slash initial
            $filePath = ltrim($filePath, '/');
            
            // Si c'est une URL externe, passer
            if (preg_match('/^https?:\/\//', $imageUrl)) {
                continue;
            }
            
            // Construire le chemin complet
            // Enlever BASE_PATH de l'URL si présent pour obtenir le chemin relatif
            $relativePath = $imageUrl;
            if ($basePath && strpos($relativePath, $basePath) === 0) {
                $relativePath = substr($relativePath, strlen($basePath));
            }
            $relativePath = ltrim($relativePath, '/');
            
            // Construire le chemin complet du fichier
            $fullPath = __DIR__ . '/../' . $relativePath;
            
            // Vérifier que le fichier existe
            if (file_exists($fullPath)) {
                $filename = basename($fullPath);
                $sizes = ['sixth', 'quarter', 'small', 'medium', 'large', 'full'];
                $size = $imgInfo['size'] ?? 'medium';
                
                // Générer tous les thumbnails pour cette image
                // generateThumbnail() vérifie déjà si le thumbnail existe et ne le régénère pas
                foreach ($sizes as $thumbSize) {
                    $thumbnailFilename = 'thumb_' . $thumbSize . '_' . $filename;
                    $thumbnailPath = THUMBNAILS_DIR . '/' . $thumbnailFilename;
                    $existedBefore = file_exists($thumbnailPath);
                    
                    $thumbnailUrl = generateThumbnail($fullPath, $filename, $thumbSize);
                    if ($thumbnailUrl) {
                        // Compter seulement les thumbnails qui ont été générés (pas ceux qui existaient déjà)
                        if (!$existedBefore) {
                            $generated++;
                        }
                    } else {
                        // Si l'image est trop petite, ce n'est pas une erreur
                        // On compte seulement comme erreur si le fichier existe mais qu'on n'a pas pu générer
                        if (file_exists($fullPath)) {
                            $errors++;
                        }
                    }
                }
            } else {
                $errors++;
            }
        }
    }
}

echo "<h1>Génération des thumbnails</h1>";
echo "<p>✅ $generated thumbnail(s) généré(s)</p>";
if ($errors > 0) {
    echo "<p>❌ $errors erreur(s)</p>";
}
echo "<p><a href='rubriques.php'>Retour aux pages</a></p>";
?>
