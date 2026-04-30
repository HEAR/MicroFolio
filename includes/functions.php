<?php
/**
 * Fonctions utilitaires
 */

require_once __DIR__ . '/config.php';

/**
 * Lire un fichier JSON
 */
function readJsonFile($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

/**
 * Écrire dans un fichier JSON
 */
function writeJsonFile($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/**
 * Vérifier si un utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Vérifier si un admin existe
 */
function adminExists() {
    $users = readJsonFile(USERS_FILE);
    return !empty($users);
}

/**
 * Créer un utilisateur admin
 */
function createAdmin($username, $password) {
    $users = readJsonFile(USERS_FILE);
    $users[] = [
        'id' => uniqid(),
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s')
    ];
    return writeJsonFile(USERS_FILE, $users);
}

/**
 * Vérifier les identifiants
 */
function verifyCredentials($username, $password) {
    $users = readJsonFile(USERS_FILE);
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return false;
}

/**
 * Obtenir toutes les rubriques (triées par ordre)
 */
function getRubriques() {
    $rubriques = readJsonFile(RUBRIQUES_FILE);
    
    // S'assurer que c'est un tableau
    if (!is_array($rubriques)) {
        return [];
    }
    
    // Trier par ordre (s'assurer que order est un entier)
    usort($rubriques, function($a, $b) {
        $orderA = isset($a['order']) ? (int)$a['order'] : 0;
        $orderB = isset($b['order']) ? (int)$b['order'] : 0;
        return $orderA - $orderB;
    });
    
    return $rubriques;
}

/**
 * Obtenir une rubrique par ID
 */
function getRubrique($id) {
    $rubriques = getRubriques();
    foreach ($rubriques as $rubrique) {
        if ($rubrique['id'] === $id) {
            return $rubrique;
        }
    }
    return null;
}

/**
 * Obtenir la rubrique définie comme page d'accueil
 */
function getHomepageRubrique() {
    $rubriques = getRubriques();
    foreach ($rubriques as $rubrique) {
        if (!empty($rubrique['is_homepage'])) {
            return $rubrique;
        }
    }
    return null;
}

/**
 * Définir une rubrique comme page d'accueil
 */
function setHomepageRubrique($id) {
    $rubriques = readJsonFile(RUBRIQUES_FILE);
    $found = false;
    
    foreach ($rubriques as &$rubrique) {
        if ($rubrique['id'] === $id) {
            $rubrique['is_homepage'] = true;
            $found = true;
        } else {
            // Retirer le flag des autres rubriques
            $rubrique['is_homepage'] = false;
        }
    }
    
    if ($found) {
        $result = writeJsonFile(RUBRIQUES_FILE, $rubriques);
        return $result !== false;
    }
    return false;
}

/**
 * Retirer la page d'accueil
 */
function unsetHomepageRubrique() {
    $rubriques = readJsonFile(RUBRIQUES_FILE);
    
    foreach ($rubriques as &$rubrique) {
        $rubrique['is_homepage'] = false;
    }
    
    $result = writeJsonFile(RUBRIQUES_FILE, $rubriques);
    return $result !== false;
}

/**
 * Créer une rubrique
 */
function createRubrique($data) {
    $rubriques = readJsonFile(RUBRIQUES_FILE); // Lire sans tri pour avoir l'ordre original
    $isHomepage = !empty($data['is_homepage']);
    
    // Si cette rubrique est définie comme page d'accueil, retirer le flag des autres
    if ($isHomepage) {
        foreach ($rubriques as &$r) {
            $r['is_homepage'] = false;
        }
    }
    
    $rubrique = [
        'id' => uniqid(),
        'title' => $data['title'] ?? '',
        'slug' => $data['slug'] ?? '',
        'content' => $data['content'] ?? '',
        'images' => $data['images'] ?? [],
        'gallery_position' => (($data['gallery_position'] ?? 'before') === 'after') ? 'after' : 'before',
        'order' => count($rubriques),
        'is_homepage' => $isHomepage,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    $rubriques[] = $rubrique;
    return writeJsonFile(RUBRIQUES_FILE, $rubriques);
}

/**
 * Mettre à jour une rubrique
 */
function updateRubrique($id, $data) {
    $rubriques = readJsonFile(RUBRIQUES_FILE); // Lire sans tri pour avoir l'ordre original
    $oldRubrique = null;
    $isHomepage = !empty($data['is_homepage']);
    
    // Si cette rubrique est définie comme page d'accueil, retirer le flag des autres
    if ($isHomepage) {
        foreach ($rubriques as &$r) {
            if ($r['id'] !== $id) {
                $r['is_homepage'] = false;
            }
        }
    }
    
    foreach ($rubriques as &$rubrique) {
        if ($rubrique['id'] === $id) {
            $oldRubrique = $rubrique;
            
            // Supprimer les images qui ne sont plus dans la nouvelle liste
            $oldImages = $oldRubrique['images'] ?? [];
            $newImages = $data['images'] ?? [];
            
            // Extraire les URLs des nouvelles images
            $newUrls = [];
            foreach ($newImages as $img) {
                if (is_array($img)) {
                    $newUrls[] = $img['url'] ?? '';
                } else {
                    $newUrls[] = $img;
                }
            }
            
            // Supprimer les fichiers des images qui ne sont plus dans la liste
            foreach ($oldImages as $oldImg) {
                $oldUrl = is_array($oldImg) ? ($oldImg['url'] ?? '') : $oldImg;
                if (!empty($oldUrl) && !in_array($oldUrl, $newUrls)) {
                    deleteImage($oldUrl);
                }
            }
            
            $rubrique['title'] = $data['title'] ?? $rubrique['title'];
            $rubrique['slug'] = $data['slug'] ?? $rubrique['slug'];
            $rubrique['content'] = $data['content'] ?? $rubrique['content'];
            $rubrique['images'] = $newImages;
            $rubrique['is_homepage'] = $isHomepage;
            $rubrique['gallery_position'] = (($data['gallery_position'] ?? ($rubrique['gallery_position'] ?? 'before')) === 'after') ? 'after' : 'before';
            $rubrique['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    return writeJsonFile(RUBRIQUES_FILE, $rubriques);
}

/**
 * Mettre a jour uniquement les images d'une page
 */
function updateRubriqueImages($id, $images) {
    $rubriques = readJsonFile(RUBRIQUES_FILE);

    foreach ($rubriques as &$rubrique) {
        if (($rubrique['id'] ?? '') === $id) {
            $rubrique['images'] = is_array($images) ? $images : [];
            $rubrique['updated_at'] = date('Y-m-d H:i:s');
            return writeJsonFile(RUBRIQUES_FILE, $rubriques);
        }
    }

    return false;
}

/**
 * Supprimer une rubrique
 */
function deleteRubrique($id) {
    $rubriques = getRubriques();
    $rubriqueToDelete = null;
    
    // Trouver la rubrique à supprimer
    foreach ($rubriques as $rubrique) {
        if ($rubrique['id'] === $id) {
            $rubriqueToDelete = $rubrique;
            break;
        }
    }
    
    // Supprimer les images de la rubrique
    if ($rubriqueToDelete && !empty($rubriqueToDelete['images'])) {
        foreach ($rubriqueToDelete['images'] as $image) {
            deleteImage($image);
        }
    }
    
    // Supprimer la rubrique de la liste
    $rubriques = array_filter($rubriques, function($rubrique) use ($id) {
        return $rubrique['id'] !== $id;
    });
    $rubriques = array_values($rubriques);
    return writeJsonFile(RUBRIQUES_FILE, $rubriques);
}

/**
 * Réorganiser les rubriques
 */
function reorderRubriques($order) {
    // Lire directement le fichier sans trier pour avoir l'ordre original
    $rubriques = readJsonFile(RUBRIQUES_FILE);
    
    // Créer un tableau indexé par ID pour faciliter la recherche
    $rubriquesById = [];
    foreach ($rubriques as $rubrique) {
        $rubriquesById[$rubrique['id']] = $rubrique;
    }
    
    // Réorganiser selon l'ordre fourni et mettre à jour le champ order
    $ordered = [];
    foreach ($order as $index => $id) {
        if (isset($rubriquesById[$id])) {
            $rubrique = $rubriquesById[$id];
            $rubrique['order'] = $index; // Mettre à jour l'ordre selon la position
            $ordered[] = $rubrique;
        }
    }
    
    // Ajouter les rubriques qui n'ont pas été incluses dans l'ordre (au cas où)
    foreach ($rubriquesById as $id => $rubrique) {
        if (!in_array($id, $order)) {
            $rubrique['order'] = count($ordered);
            $ordered[] = $rubrique;
        }
    }
    
    return writeJsonFile(RUBRIQUES_FILE, $ordered);
}

/**
 * Obtenir la configuration
 */
function getConfig() {
    return readJsonFile(CONFIG_FILE);
}

/**
 * Mettre à jour la configuration
 */
function updateConfig($data) {
    $config = getConfig();
    $config = array_merge($config, $data);
    return writeJsonFile(CONFIG_FILE, $config);
}

/**
 * Vérifier si le mode maintenance est activé
 */
function isMaintenanceMode() {
    $config = getConfig();
    return !empty($config['maintenance_mode']);
}

/**
 * Générer un slug à partir d'un titre
 */
function generateSlug($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Générer l'URL propre d'une rubrique
 */
function getRubriqueUrl($slug) {
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    return $basePath . '/' . $slug;
}

/**
 * Canoniser un chemin d'asset local pour stockage (format portable)
 */
function normalizeLocalAssetPath($url) {
    if (empty($url)) {
        return $url;
    }

    if (is_array($url)) {
        $url = $url['url'] ?? $url;
    }

    if (preg_match('/^https?:\/\//', $url)) {
        return $url;
    }

    $basePath = BASE_PATH;

    if ($basePath && strpos($url, $basePath) === 0) {
        $url = substr($url, strlen($basePath));
    }

    $url = str_replace('/admin/assets/images/', '/assets/images/', $url);

    $assetsPos = strpos($url, '/assets/images/');
    if ($assetsPos !== false) {
        return substr($url, $assetsPos);
    }

    if (strpos($url, 'assets/images/') === 0) {
        return '/' . $url;
    }

    return $url;
}

/**
 * Résoudre un chemin d'asset en URL affichable selon l'environnement
 */
function resolveAssetUrl($url) {
    if (empty($url)) {
        return $url;
    }

    $normalized = normalizeLocalAssetPath($url);
    if (preg_match('/^https?:\/\//', $normalized)) {
        return $normalized;
    }

    if (strpos($normalized, '/') !== 0) {
        return $normalized;
    }

    return BASE_PATH . $normalized;
}

/**
 * Compatibilité legacy: normaliser l'URL d'image pour affichage
 */
function normalizeImageUrl($url) {
    return resolveAssetUrl($url);
}

/**
 * Obtenir les informations d'une image (support ancien et nouveau format)
 */
function getImageInfo($image) {
    $allowedSizes = ['sixth', 'quarter', 'small', 'medium', 'large', 'full'];

    // Nouveau format : tableau avec url, caption, size
    if (is_array($image)) {
        $url = $image['url'] ?? '';
        $size = $image['size'] ?? 'medium';
        if (!in_array($size, $allowedSizes, true)) {
            $size = 'medium';
        }
        $thumbnailUrl = getThumbnailUrl($url, $size);
        return [
            'url' => normalizeImageUrl($url),
            'caption' => $image['caption'] ?? '',
            'size' => $size,
            'hidden' => !empty($image['hidden']),
            'is_thumbnail' => !empty($image['is_thumbnail']),
            'thumbnail_url' => $thumbnailUrl ? normalizeImageUrl($thumbnailUrl) : null
        ];
    }
    
    // Ancien format : juste une URL
    $size = 'medium';
    $thumbnailUrl = getThumbnailUrl($image, $size);
    return [
        'url' => normalizeImageUrl($image),
        'caption' => '',
        'size' => $size,
        'hidden' => false,
        'is_thumbnail' => false,
        'thumbnail_url' => $thumbnailUrl ? normalizeImageUrl($thumbnailUrl) : null
    ];
}

/**
 * Vérifier si une image est masquée
 */
function isImageHidden($image) {
    return is_array($image) && !empty($image['hidden']);
}

/**
 * Obtenir l'image de miniature prioritaire d'une page.
 * Priorité: image non masquée marquée thumbnail, sinon première non masquée.
 */
function getRubriqueThumbnailImage($rubrique) {
    $images = $rubrique['images'] ?? [];
    if (!is_array($images) || empty($images)) {
        return null;
    }

    foreach ($images as $image) {
        if (!isImageHidden($image) && is_array($image) && !empty($image['is_thumbnail'])) {
            return $image;
        }
    }

    foreach ($images as $image) {
        if (!isImageHidden($image)) {
            return $image;
        }
    }

    return null;
}

/**
 * Calculer le poids total de assets + data (en octets)
 */
function getStorageUsageBytes() {
    $directories = [
        __DIR__ . '/../assets',
        DATA_DIR
    ];

    $totalBytes = 0;

    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            continue;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $totalBytes += (int)$fileInfo->getSize();
                }
            }
        } catch (Exception $e) {
            // Ignorer les erreurs de lecture partielles, retourner le total disponible.
        }
    }

    return $totalBytes;
}

/**
 * Calculer le poids par dossier (assets et data)
 */
function getStorageUsageBreakdownBytes() {
    $breakdown = [
        'assets' => 0,
        'data' => 0
    ];

    $targets = [
        'assets' => __DIR__ . '/../assets',
        'data' => DATA_DIR
    ];

    foreach ($targets as $key => $directory) {
        if (!is_dir($directory)) {
            continue;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $breakdown[$key] += (int)$fileInfo->getSize();
                }
            }
        } catch (Exception $e) {
            // Ignorer les erreurs de lecture partielles.
        }
    }

    $breakdown['total'] = $breakdown['assets'] + $breakdown['data'];
    return $breakdown;
}

/**
 * Formater une taille en octets en unite lisible (KB, MB, ...)
 */
function formatBytes($bytes, $precision = 2) {
    $bytes = max(0, (float)$bytes);
    $units = ['o', 'Ko', 'Mo', 'Go', 'To'];

    $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    $pow = min($pow, count($units) - 1);

    $value = $bytes / (1024 ** $pow);
    return round($value, $precision) . ' ' . $units[$pow];
}

/**
 * Obtenir l'URL du thumbnail d'une image selon la taille
 */
function getThumbnailUrl($imageUrl, $size = 'medium') {
    if (empty($imageUrl)) {
        return null;
    }
    
    // Si c'est un tableau, extraire l'URL et la taille
    if (is_array($imageUrl)) {
        $size = $imageUrl['size'] ?? $size;
        $imageUrl = $imageUrl['url'] ?? '';
    }
    
    // Si c'est une URL externe, pas de thumbnail
    if (preg_match('/^https?:\/\//', $imageUrl)) {
        return null;
    }
    
    // Nettoyer l'URL pour obtenir le nom du fichier
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    $cleanUrl = $imageUrl;
    
    // Enlever BASE_PATH si présent
    if ($basePath && strpos($cleanUrl, $basePath) === 0) {
        $cleanUrl = substr($cleanUrl, strlen($basePath));
    }
    
    // Enlever le chemin pour obtenir juste le nom du fichier
    $filename = basename($cleanUrl);
    
    // Si le nom commence par "thumb_", c'est déjà un thumbnail
    if (strpos($filename, 'thumb_') === 0) {
        return null;
    }
    
    // Générer le nom du thumbnail avec la taille
    $thumbnailFilename = 'thumb_' . $size . '_' . $filename;
    
    // Vérifier si le thumbnail existe
    $thumbnailPath = THUMBNAILS_DIR . '/' . $thumbnailFilename;
    if (file_exists($thumbnailPath)) {
        return '/assets/images/thumbs/' . $thumbnailFilename;
    }
    
    // Si le thumbnail n'existe pas, essayer de le générer
    // Extraire le chemin complet de l'image originale
    $originalPath = IMAGES_DIR . '/' . $filename;
    if (file_exists($originalPath)) {
        // generateThumbnail() vérifie déjà si le thumbnail existe et le retourne si c'est le cas
        // Sinon, il le génère et retourne l'URL
        $thumbUrl = generateThumbnail($originalPath, $filename, $size);
        if ($thumbUrl) {
            return $thumbUrl;
        }
    }
    
    return null;
}

/**
 * Upload d'une image
 */
function uploadImage($file) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['error' => 'Paramètres invalides'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Erreur lors de l\'upload'];
    }
    
    // Vérifier le type de fichier
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['error' => 'Type de fichier non autorisé'];
    }
    
    // Vérifier la taille (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'Fichier trop volumineux (max 5MB)'];
    }
    
    $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    // Générer un nom de fichier unique avec extension contrôlée
    $extension = $mimeToExtension[$mimeType] ?? null;
    if ($extension === null) {
        return ['error' => 'Type de fichier non autorisé'];
    }
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $destination = IMAGES_DIR . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['error' => 'Impossible de déplacer le fichier'];
    }
    
    $imageUrl = '/assets/images/' . $filename;
    
    // Générer tous les thumbnails nécessaires
    // generateThumbnail() vérifie déjà si le thumbnail existe et ne le régénère pas
    $sizes = ['sixth', 'quarter', 'small', 'medium', 'large', 'full'];
    $thumbnails = [];
    foreach ($sizes as $size) {
        $thumbUrl = generateThumbnail($destination, $filename, $size);
        if ($thumbUrl) {
            $thumbnails[$size] = $thumbUrl;
        }
    }
    
    return [
        'success' => true, 
        'url' => $imageUrl,
        'thumbnails' => $thumbnails
    ];
}

/**
 * Upload d'un document PDF
 */
function uploadDocument($file) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['error' => 'Paramètres invalides'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Erreur lors de l\'upload'];
    }

    // Vérifier le type de fichier (PDF uniquement)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mimeType !== 'application/pdf') {
        return ['error' => 'Seuls les fichiers PDF sont autorisés'];
    }

    // Vérifier la taille (max 20MB)
    if ($file['size'] > 20 * 1024 * 1024) {
        return ['error' => 'Fichier trop volumineux (max 20MB)'];
    }

    // Conserver le nom d'origine (nettoyé) quand possible
    $originalName = $file['name'] ?? 'document.pdf';
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $baseName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $baseName);
    $baseName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $baseName);
    $baseName = trim($baseName, '-');
    if ($baseName === '' || $baseName === null) {
        $baseName = 'document';
    }

    $filename = $baseName . '.pdf';
    $counter = 1;
    while (file_exists(DOCS_DIR . '/' . $filename)) {
        $filename = $baseName . '-' . $counter . '.pdf';
        $counter++;
    }

    $destination = DOCS_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['error' => 'Impossible de déplacer le fichier'];
    }

    return [
        'success' => true,
        'url' => '/assets/docs/' . $filename
    ];
}

/**
 * Supprimer un document PDF local
 */
function deleteDocument($url) {
    if (empty($url)) {
        return false;
    }

    // Refuser les URLs externes
    if (preg_match('/^https?:\/\//i', $url) && strpos($url, BASE_PATH . '/assets/docs/') === false) {
        return false;
    }

    $path = parse_url($url, PHP_URL_PATH) ?: $url;
    $normalized = normalizeLocalAssetPath($path);

    if (strpos($normalized, '/assets/docs/') !== 0) {
        return false;
    }

    $filename = basename($normalized);
    if (!preg_match('/\.pdf$/i', $filename)) {
        return false;
    }

    $fullPath = DOCS_DIR . '/' . $filename;
    if (!file_exists($fullPath)) {
        return false;
    }

    return unlink($fullPath);
}

/**
 * Obtenir les dimensions max pour une taille donnée
 */
function getThumbnailDimensions($size) {
    $dimensions = [
        'sixth' => ['width' => 240, 'height' => 240],
        'quarter' => ['width' => 320, 'height' => 320],
        'small' => ['width' => 400, 'height' => 400],
        'medium' => ['width' => 600, 'height' => 600],
        'large' => ['width' => 800, 'height' => 800],
        'full' => ['width' => 1200, 'height' => 1200]
    ];
    
    return $dimensions[$size] ?? $dimensions['medium'];
}

/**
 * Générer un thumbnail d'une image avec une taille spécifique
 */
function generateThumbnail($sourcePath, $filename, $size = 'medium', $quality = 85) {
    $dimensions = getThumbnailDimensions($size);
    $maxWidth = $dimensions['width'];
    $maxHeight = $dimensions['height'];
    
    if (!file_exists($sourcePath)) {
        return null;
    }
    
    // Générer le nom du fichier thumbnail avec la taille
    $thumbnailFilename = 'thumb_' . $size . '_' . $filename;
    $thumbnailPath = THUMBNAILS_DIR . '/' . $thumbnailFilename;
    
    // Si le thumbnail existe déjà, retourner son URL sans le régénérer
    if (file_exists($thumbnailPath)) {
        return '/assets/images/thumbs/' . $thumbnailFilename;
    }
    
    // Obtenir les informations de l'image
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return null;
    }
    
    $mimeType = $imageInfo['mime'];
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    
    // Si l'image est déjà plus petite que les dimensions max, pas besoin de thumbnail
    if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
        return null;
    }
    
    // Calculer les nouvelles dimensions en conservant le ratio
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $newWidth = (int)($originalWidth * $ratio);
    $newHeight = (int)($originalHeight * $ratio);
    
    // Créer l'image source selon le type
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return null;
    }
    
    if (!$sourceImage) {
        return null;
    }
    
    // Créer l'image de destination
    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
    
    // Préserver la transparence pour PNG et GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Redimensionner l'image
    imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    // Sauvegarder le thumbnail
    $saved = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $saved = imagejpeg($thumbnail, $thumbnailPath, $quality);
            break;
        case 'image/png':
            $saved = imagepng($thumbnail, $thumbnailPath, 9);
            break;
        case 'image/gif':
            $saved = imagegif($thumbnail, $thumbnailPath);
            break;
        case 'image/webp':
            $saved = imagewebp($thumbnail, $thumbnailPath, $quality);
            break;
    }
    
    // Libérer la mémoire
    imagedestroy($sourceImage);
    imagedestroy($thumbnail);
    
    if (!$saved) {
        return null;
    }
    
    // Générer l'URL du thumbnail (format portable)
    return '/assets/images/thumbs/' . $thumbnailFilename;
}

/**
 * Supprimer une image et son thumbnail
 */
function deleteImage($url) {
    if (empty($url)) {
        return false;
    }
    
    // Si c'est un tableau, extraire l'URL
    if (is_array($url)) {
        $url = $url['url'] ?? '';
    }
    
    // Si c'est une URL externe, ne rien faire
    if (preg_match('/^https?:\/\//', $url)) {
        return false;
    }
    
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    $cleanUrl = $url;
    
    // Enlever BASE_PATH si présent
    if ($basePath && strpos($cleanUrl, $basePath) === 0) {
        $cleanUrl = substr($cleanUrl, strlen($basePath));
    }
    
    // Enlever le slash initial
    $cleanUrl = ltrim($cleanUrl, '/');
    
    // Extraire le nom du fichier
    $filename = basename($cleanUrl);
    
    // Si c'est déjà un thumbnail, ne rien faire (on ne supprime que les originaux)
    if (strpos($filename, 'thumb_') === 0) {
        return false;
    }
    
    $deleted = false;
    
    // Supprimer l'image originale
    $filepath = IMAGES_DIR . '/' . $filename;
    if (file_exists($filepath)) {
        if (unlink($filepath)) {
            $deleted = true;
        }
    }
    
    // Supprimer tous les thumbnails de toutes les tailles
    $sizes = ['sixth', 'quarter', 'small', 'medium', 'large', 'full'];
    foreach ($sizes as $size) {
        $thumbnailFilename = 'thumb_' . $size . '_' . $filename;
        $thumbnailPath = THUMBNAILS_DIR . '/' . $thumbnailFilename;
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
    }
    
    return $deleted;
}

/**
 * Convertir un lien YouTube en iframe
 */
function convertYouTubeToIframe($url) {
    $videoId = null;
    $params = [];
    
    // Format: https://www.youtube.com/watch?v=VIDEO_ID
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $videoId = $matches[1];
        // Extraire tous les paramètres de l'URL après le v=VIDEO_ID
        // On extrait tout ce qui suit & après le v=VIDEO_ID
        if (preg_match('/youtube\.com\/watch\?v=[^&]+&(.+)$/', $url, $paramMatch)) {
            parse_str($paramMatch[1], $parsedParams);
            // Filtrer pour ne garder que les paramètres pertinents pour embed (si, t, list, index, etc.)
            $allowedParams = ['si', 't', 'list', 'index', 'start', 'end'];
            foreach ($parsedParams as $key => $value) {
                if (in_array($key, $allowedParams)) {
                    $params[$key] = $value;
                }
            }
        }
    }
    // Format: https://youtu.be/VIDEO_ID
    elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $videoId = $matches[1];
        // Extraire les paramètres de l'URL
        if (preg_match('/\?([^#]+)/', $url, $queryMatch)) {
            parse_str($queryMatch[1], $parsedParams);
            $params = array_merge($params, $parsedParams);
        }
    }
    // Format: https://www.youtube.com/embed/VIDEO_ID
    elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $videoId = $matches[1];
        // Extraire les paramètres de l'URL
        if (preg_match('/\?([^#]+)/', $url, $queryMatch)) {
            parse_str($queryMatch[1], $parsedParams);
            $params = array_merge($params, $parsedParams);
        }
    }
    
    if ($videoId) {
        // Construire la chaîne de paramètres
        $paramString = '';
        if (!empty($params)) {
            $paramString = '?' . http_build_query($params);
        }
        
        return '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . $paramString . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
    }
    
    return null;
}

/**
 * Parser le markdown en HTML
 */
function parseMarkdown($text) {
    if (empty($text)) {
        return '';
    }
    
    // Stocker les iframes pour les restaurer après l'échappement HTML
    $iframePlaceholders = [];
    $placeholderIndex = 0;
    
    // 1. Détecter et protéger les iframes existants
    $text = preg_replace_callback('/<iframe[^>]*>.*?<\/iframe>/is', function($matches) use (&$iframePlaceholders, &$placeholderIndex) {
        $placeholder = "\x01IFRAME_PLACEHOLDER_" . $placeholderIndex . "\x01";
        $iframePlaceholders[$placeholderIndex] = $matches[0];
        $placeholderIndex++;
        return $placeholder;
    }, $text);
    
    // 2. Convertir les liens YouTube dans les liens markdown (AVANT les URLs brutes)
    $text = preg_replace_callback('/\[([^\]]*)\]\((https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)[^\)]+)\)/i', function($matches) use (&$iframePlaceholders, &$placeholderIndex) {
        $url = $matches[2];
        $iframe = convertYouTubeToIframe($url);
        if ($iframe) {
            $placeholder = "\x01IFRAME_PLACEHOLDER_" . $placeholderIndex . "\x01";
            $iframePlaceholders[$placeholderIndex] = $iframe;
            $placeholderIndex++;
            return $placeholder;
        }
        return $matches[0];
    }, $text);
    
    // 3. Convertir les URLs YouTube brutes en iframes (après les liens markdown pour éviter les conflits)
    // Pattern amélioré pour capturer toutes les variantes d'URL YouTube avec tous leurs paramètres
    // Supporte: youtube.com/watch?v=, youtu.be/, youtube.com/embed/
    // Pattern plus permissif pour capturer tous les paramètres (y compris &sourcevepath=, etc.)
    // Utilise une approche robuste qui capture l'URL complète avec tous ses paramètres
    $youtubeUrlPattern = '/(?:^|[\s>])(https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)[a-zA-Z0-9_-]+(?:[^\s\)<\"\']*)?)/i';
    $text = preg_replace_callback($youtubeUrlPattern, function($matches) use (&$iframePlaceholders, &$placeholderIndex) {
        $fullMatch = $matches[0];
        $url = trim($matches[1]);
        
        // Nettoyer l'URL (enlever les caractères de fin de ligne ou autres, mais garder les paramètres)
        $url = rtrim($url, ".,;:!?\n\r\t");
        
        // Vérifier que c'est bien une URL YouTube valide et qu'elle contient un ID vidéo
        if (preg_match('/youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\//i', $url)) {
            $iframe = convertYouTubeToIframe($url);
            if ($iframe) {
                $placeholder = "\x01IFRAME_PLACEHOLDER_" . $placeholderIndex . "\x01";
                $iframePlaceholders[$placeholderIndex] = $iframe;
                $placeholderIndex++;
                // Préserver l'espace ou le caractère avant si présent
                $prefix = strlen($fullMatch) > strlen($url) ? substr($fullMatch, 0, strlen($fullMatch) - strlen($url)) : '';
                return $prefix . $placeholder;
            }
        }
        return $fullMatch;
    }, $text);
    
    // 4. Détection supplémentaire pour les URLs YouTube qui pourraient être sur leur propre ligne
    // Cette passe supplémentaire capture les URLs qui pourraient avoir été manquées
    $text = preg_replace_callback('/^[\s]*(https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)[^\s\)<\"\']+)[\s]*$/im', function($matches) use (&$iframePlaceholders, &$placeholderIndex) {
        $url = trim($matches[1]);
        if (preg_match('/youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\//i', $url)) {
            $iframe = convertYouTubeToIframe($url);
            if ($iframe) {
                $placeholder = "\x01IFRAME_PLACEHOLDER_" . $placeholderIndex . "\x01";
                $iframePlaceholders[$placeholderIndex] = $iframe;
                $placeholderIndex++;
                return $placeholder;
            }
        }
        return $matches[0];
    }, $text);
    
    // Échapper le HTML sauf pour les placeholders d'iframes (caractères de contrôle \x01 ne seront pas échappés)
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // Restaurer les iframes immédiatement après l'échappement (avant le traitement markdown)
    foreach ($iframePlaceholders as $index => $iframe) {
        $placeholder = "\x01IFRAME_PLACEHOLDER_" . $index . "\x01";
        $text = str_replace($placeholder, $iframe, $text);
    }
    
    // Code inline (avant les autres transformations)
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    
    // Code blocks
    $text = preg_replace('/```(\w+)?\n(.*?)```/s', '<pre><code class="language-$1">$2</code></pre>', $text);
    
    // Titres (ordre important : du plus grand au plus petit)
    $text = preg_replace('/^###### (.*?)$/m', '<h6>$1</h6>', $text);
    $text = preg_replace('/^##### (.*?)$/m', '<h5>$1</h5>', $text);
    $text = preg_replace('/^#### (.*?)$/m', '<h4>$1</h4>', $text);
    $text = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $text);
    
    // Gras (double étoile ou double underscore)
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
    
    // Italique (simple étoile ou underscore, mais pas si précédé/suivi d'un caractère alphanumérique)
    $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $text);
    $text = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/', '<em>$1</em>', $text);
    
    // Images (avant les liens)
    $text = preg_replace('/!\[([^\]]*)\]\(([^\)]+)\)/', '<img src="$2" alt="$1" class="markdown-image">', $text);
    
    // Liens
    $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2">$1</a>', $text);

    // Ouvrir les liens PDF dans un nouvel onglet
    $text = preg_replace(
        '/<a href="([^"]+\.pdf(?:\?[^"]*)?)">([^<]+)<\/a>/i',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>',
        $text
    );
    
    // Listes non ordonnées
    $lines = explode("\n", $text);
    $inList = false;
    $inOrderedList = false;
    $result = [];
    
    foreach ($lines as $line) {
        // Liste non ordonnée
        if (preg_match('/^[\*\-\+]\s+(.+)$/', $line, $matches)) {
            if (!$inList) {
                $result[] = '<ul>';
                $inList = true;
            }
            $result[] = '<li>' . $matches[1] . '</li>';
        }
        // Liste ordonnée
        elseif (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
            if ($inList) {
                $result[] = '</ul>';
                $inList = false;
            }
            if (!$inOrderedList) {
                $result[] = '<ol>';
                $inOrderedList = true;
            }
            $result[] = '<li>' . $matches[1] . '</li>';
        }
        // Ligne vide
        elseif (trim($line) === '') {
            if ($inList) {
                $result[] = '</ul>';
                $inList = false;
            }
            if ($inOrderedList) {
                $result[] = '</ol>';
                $inOrderedList = false;
            }
            $result[] = '';
        }
        // Ligne normale
        else {
            if ($inList) {
                $result[] = '</ul>';
                $inList = false;
            }
            if ($inOrderedList) {
                $result[] = '</ol>';
                $inOrderedList = false;
            }
            $result[] = $line;
        }
    }
    
    // Fermer les listes ouvertes
    if ($inList) {
        $result[] = '</ul>';
    }
    if ($inOrderedList) {
        $result[] = '</ol>';
    }
    
    $text = implode("\n", $result);
    
    // Blockquotes
    $text = preg_replace('/^>\s+(.+)$/m', '<blockquote>$1</blockquote>', $text);
    $text = preg_replace('/<\/blockquote>\s*<blockquote>/', '<br>', $text);
    
    // Lignes horizontales
    $text = preg_replace('/^---$|^___$|^\*\*\*$/m', '<hr>', $text);
    
    // Paragraphes (diviser par double saut de ligne)
    $paragraphs = preg_split('/\n\s*\n/', $text);
    $paragraphs = array_map(function($p) {
        $p = trim($p);
        if (empty($p)) {
            return '';
        }
        // Ne pas envelopper si c'est déjà une balise block ou un iframe (et rien d'autre)
        if (preg_match('/^<(h[1-6]|ul|ol|pre|blockquote|hr|iframe)[^>]*>.*?<\/\1>\s*$/is', $p)) {
            return $p;
        }
        
        // Si le paragraphe contient un iframe, il faut le séparer
        if (preg_match('/<iframe[^>]*>.*?<\/iframe>/is', $p)) {
            // Séparer le texte avant, l'iframe, et le texte après
            $parts = preg_split('/(<iframe[^>]*>.*?<\/iframe>)/is', $p, -1, PREG_SPLIT_DELIM_CAPTURE);
            $result = [];
            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part)) {
                    continue;
                }
                // Si c'est un iframe, l'ajouter tel quel
                if (preg_match('/^<iframe[^>]*>.*?<\/iframe>$/is', $part)) {
                    $result[] = $part;
                } else {
                    // Sinon, envelopper dans un paragraphe seulement s'il y a du contenu textuel
                    $textContent = trim(strip_tags($part));
                    if (!empty($textContent)) {
                        $result[] = '<p>' . $part . '</p>';
                    } elseif (!empty($part)) {
                        // Si c'est du HTML mais pas de texte, l'ajouter tel quel (pour les autres balises)
                        $result[] = $part;
                    }
                }
            }
            return implode("\n", $result);
        }
        
        return '<p>' . $p . '</p>';
    }, $paragraphs);
    
    $text = implode("\n", array_filter($paragraphs));
    
    // Nettoyer les sauts de ligne multiples
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    
    return $text;
}

