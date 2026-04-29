<?php
require_once __DIR__ . '/../includes/auth.php';
handleLogout();
requireAuth();

$action = $_GET['action'] ?? 'list';
$message = $_GET['message'] ?? null;
$error = null;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        
        if (empty($title)) {
            $error = 'Le titre est obligatoire';
        } else {
            // Générer le slug si vide
            if (empty($slug)) {
                $slug = generateSlug($title);
            }
            
            $data = [
                'title' => $title,
                'slug' => $slug,
                'content' => $_POST['content'] ?? '',
                'images' => [],
                'is_homepage' => !empty($_POST['is_homepage'])
            ];
            
            if (createRubrique($data)) {
                $message = 'Rubrique créée avec succès';
                // Récupérer l'ID de la rubrique créée pour rediriger vers la page d'édition
                $rubriques = getRubriques();
                $newRubrique = end($rubriques);
                if ($newRubrique) {
                    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
                    header('Location: ' . $basePath . '/' . ADMIN_PATH . '/rubriques.php?action=edit&id=' . $newRubrique['id'] . '&message=' . urlencode($message));
                    exit;
                } else {
                    $action = 'list';
                }
            } else {
                $error = 'Erreur lors de la création de la rubrique. Vérifiez les permissions d\'écriture sur le fichier rubriques.json';
            }
        }
    } elseif (isset($_POST['update'])) {
        $id = $_POST['id'] ?? '';
        $data = [
            'title' => $_POST['title'] ?? '',
            'slug' => $_POST['slug'] ?? '',
            'content' => $_POST['content'] ?? '',
            'images' => json_decode($_POST['images'] ?? '[]', true),
            'is_homepage' => !empty($_POST['is_homepage'])
        ];
        if (updateRubrique($id, $data)) {
            $message = 'Rubrique mise à jour avec succès';
            // Rediriger vers la page d'édition pour rester sur la page
            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
            header('Location: ' . $basePath . '/' . ADMIN_PATH . '/rubriques.php?action=edit&id=' . $id . '&message=' . urlencode($message));
            exit;
        } else {
            $error = 'Erreur lors de la mise à jour de la rubrique';
        }
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'] ?? '';
        if (deleteRubrique($id)) {
            $message = 'Rubrique supprimée avec succès';
        } else {
            $error = 'Erreur lors de la suppression de la rubrique';
        }
    } elseif (isset($_POST['reorder'])) {
        $order = json_decode($_POST['order'] ?? '[]', true);
        if (reorderRubriques($order)) {
            $message = 'Ordre des rubriques mis à jour';
        } else {
            $error = 'Erreur lors de la réorganisation';
        }
    }
}

// Traitement des actions GET (hors du bloc POST)
if (isset($_GET['set_homepage'])) {
    $id = $_GET['set_homepage'] ?? '';
    if (setHomepageRubrique($id)) {
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        header('Location: ' . $basePath . '/' . ADMIN_PATH . '/rubriques.php?message=' . urlencode('Page d\'accueil définie avec succès'));
        exit;
    } else {
        $error = 'Erreur lors de la définition de la page d\'accueil';
    }
} elseif (isset($_GET['unset_homepage'])) {
    if (unsetHomepageRubrique()) {
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        header('Location: ' . $basePath . '/' . ADMIN_PATH . '/rubriques.php?message=' . urlencode('Page d\'accueil retirée avec succès'));
        exit;
    } else {
        $error = 'Erreur lors de la suppression de la page d\'accueil';
    }
}

$rubriques = getRubriques();
$homepageRubrique = getHomepageRubrique();
$rubrique = null;
if (isset($_GET['id']) && $action === 'edit') {
    $rubrique = getRubrique($_GET['id']);
    if (!$rubrique) {
        $action = 'list';
        $error = 'Rubrique introuvable';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des rubriques - Admin Portfolio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Portfolio CMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="rubriques.php">
                            <i class="bi bi-folder"></i> Rubriques
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="custom.php">
                            <i class="bi bi-code-slash"></i> Personnalisation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i> Paramètres
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=1">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Gestion des rubriques</h1>
                <div>
                    <a href="generate-thumbnails.php" class="btn btn-outline-secondary me-2" title="Générer les thumbnails des images existantes">
                        <i class="bi bi-image"></i> Générer thumbnails
                    </a>
                    <a href="?action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Créer une rubrique
                    </a>
                </div>
            </div>

            <?php if (empty($rubriques)): ?>
                <div class="alert alert-info">
                    Aucune rubrique pour le moment. <a href="?action=create">Créer la première rubrique</a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Liste des rubriques (glissez pour réorganiser)</h5>
                            <?php if ($homepageRubrique): ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-house"></i> Page d'accueil : <?= htmlspecialchars($homepageRubrique['title']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">
                                    <i class="bi bi-house"></i> Aucune page d'accueil définie
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="reorderForm">
                            <input type="hidden" name="reorder" value="1">
                            <input type="hidden" name="order" id="orderInput">
                            <ul class="list-group" id="rubriquesList">
                                <?php foreach ($rubriques as $rub): 
                                    $isHomepage = !empty($rub['is_homepage']);
                                ?>
                                <li class="list-group-item" data-id="<?= $rub['id'] ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-1">
                                                <?= htmlspecialchars($rub['title']) ?>
                                                <?php if ($isHomepage): ?>
                                                    <span class="badge bg-success ms-2">
                                                        <i class="bi bi-house"></i> Page d'accueil
                                                    </span>
                                                <?php endif; ?>
                                            </h5>
                                            <small class="text-muted">
                                                <code><?= htmlspecialchars($rub['slug']) ?></code> - 
                                                Créé le <?= htmlspecialchars($rub['created_at']) ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php if ($isHomepage): ?>
                                                <a href="?unset_homepage=1" class="btn btn-sm btn-outline-warning" onclick="return confirm('Retirer cette rubrique de la page d\'accueil ?')">
                                                    <i class="bi bi-house-x"></i> Retirer de l'accueil
                                                </a>
                                            <?php else: ?>
                                                <a href="?set_homepage=<?= $rub['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Définir cette rubrique comme page d\'accueil ? Elle sera cachée du menu.')">
                                                    <i class="bi bi-house"></i> Page d'accueil
                                                </a>
                                            <?php endif; ?>
                                            <a href="?action=edit&id=<?= $rub['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Modifier
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteRubrique('<?= $rub['id'] ?>', '<?= htmlspecialchars($rub['title']) ?>')">
                                                <i class="bi bi-trash"></i> Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?= $action === 'create' ? 'Créer une rubrique' : 'Modifier la rubrique' ?></h1>
                <a href="rubriques.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $rubrique['id'] ?>">
                            <input type="hidden" name="update" value="1">
                        <?php else: ?>
                            <input type="hidden" name="create" value="1">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="title" class="form-label">Titre</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($rubrique['title'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug (URL)</label>
                            <input type="text" class="form-control" id="slug" name="slug" 
                                   value="<?= htmlspecialchars($rubrique['slug'] ?? '') ?>" required>
                            <small class="form-text text-muted">Généré automatiquement à partir du titre si vide</small>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Contenu (Markdown)</label>
                            <textarea class="form-control" id="content" name="content" rows="15"><?= htmlspecialchars($rubrique['content'] ?? '') ?></textarea>
                            <small class="form-text text-muted">Vous pouvez utiliser la syntaxe Markdown pour formater votre texte</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_homepage" name="is_homepage" value="1" <?= !empty($rubrique['is_homepage']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_homepage">
                                    Définir comme page d'accueil
                                </label>
                                <small class="form-text text-muted d-block">Si coché, cette rubrique sera affichée à la racine du site et cachée du menu. Une seule rubrique peut être définie comme page d'accueil.</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Images</label>
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="imageUpload" class="form-label">Uploader une image</label>
                                        <input type="file" class="form-control" id="imageUpload" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</small>
                                    </div>
                                    <div id="uploadProgress" class="d-none">
                                        <div class="progress mb-2">
                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div id="uploadedImages" class="row g-2 mb-3">
                                        <?php if ($rubrique && !empty($rubrique['images'])): ?>
                                            <?php foreach ($rubrique['images'] as $index => $img): 
                                                $imgInfo = getImageInfo($img);
                                            ?>
                                                <div class="col-md-3 image-item" data-index="<?= $index ?>">
                                                    <div class="card">
                                                        <img src="<?= htmlspecialchars($imgInfo['url']) ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                                                        <div class="card-body p-2">
                                                            <div class="mb-2">
                                                                <label class="form-label small">Légende</label>
                                                                <input type="text" class="form-control form-control-sm image-caption" 
                                                                       value="<?= htmlspecialchars($imgInfo['caption']) ?>" 
                                                                       placeholder="Légende de l'image">
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label small">Dimension</label>
                                                                <select class="form-select form-select-sm image-size">
                                                                    <option value="small" <?= $imgInfo['size'] === 'small' ? 'selected' : '' ?>>Petite (1/3)</option>
                                                                    <option value="medium" <?= $imgInfo['size'] === 'medium' ? 'selected' : '' ?>>Moyenne (1/2)</option>
                                                                    <option value="large" <?= $imgInfo['size'] === 'large' ? 'selected' : '' ?>>Grande (2/3)</option>
                                                                    <option value="full" <?= $imgInfo['size'] === 'full' ? 'selected' : '' ?>>Pleine largeur</option>
                                                                </select>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-danger w-100 remove-image">
                                                                <i class="bi bi-trash"></i> Supprimer
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ou ajouter une URL d'image</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="imageUrlInput" placeholder="https://example.com/image.jpg">
                                            <button type="button" class="btn btn-outline-secondary" id="addImageUrl">
                                                <i class="bi bi-plus"></i> Ajouter
                                            </button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="images" id="imagesInput" value="<?= htmlspecialchars(json_encode($rubrique['images'] ?? [])) ?>">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> <?= $action === 'create' ? 'Créer' : 'Enregistrer' ?>
                        </button>
                        <a href="rubriques.php" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <form method="POST" id="deleteForm" style="display:none;">
        <input type="hidden" name="delete" value="1">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Génération automatique du slug
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');
        if (titleInput && slugInput) {
            titleInput.addEventListener('input', function() {
                // Générer le slug seulement si le champ slug est vide ou si l'utilisateur n'a pas modifié manuellement
                if (!slugInput.dataset.manual) {
                    slugInput.value = this.value.toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '') // Supprimer les accents
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                }
            });
            
            // Marquer si l'utilisateur modifie manuellement le slug
            slugInput.addEventListener('input', function() {
                if (this.value) {
                    this.dataset.manual = 'true';
                }
            });
        }

        // Initialiser l'éditeur Markdown
        const contentTextarea = document.getElementById('content');
        let easyMDE = null;
        if (contentTextarea) {
            easyMDE = new EasyMDE({
                element: contentTextarea,
                spellChecker: false,
                toolbar: ['bold', 'italic', 'heading', '|', 'quote', 'unordered-list', 'ordered-list', '|', 'link', 'image', '|', 'preview', 'side-by-side', 'fullscreen', '|', 'guide'],
                placeholder: 'Rédigez votre contenu en Markdown...',
                status: ['lines', 'words', 'cursor']
            });
        }

        // Gestion des images
        let images = [];
        const imagesInput = document.getElementById('imagesInput');
        const uploadedImagesDiv = document.getElementById('uploadedImages');
        const uploadProgress = document.getElementById('uploadProgress');
        const imageUpload = document.getElementById('imageUpload');
        const imageUrlInput = document.getElementById('imageUrlInput');
        const addImageUrlBtn = document.getElementById('addImageUrl');

        function normalizeLocalImageUrl(url) {
            if (!url) return url;

            const assetsMarker = '/assets/images/';
            const markerIndex = url.indexOf(assetsMarker);

            // Si l'URL contient /assets/images, conserver uniquement cette partie
            // pour éviter les préfixes de dossier local obsolètes après migration.
            if (markerIndex !== -1) {
                return url.substring(markerIndex);
            }

            return url;
        }

        // Charger les images existantes et convertir l'ancien format si nécessaire
        if (imagesInput && imagesInput.value) {
            try {
                images = JSON.parse(imagesInput.value);
                // Convertir l'ancien format (tableau de strings) au nouveau format
                images = images.map(img => {
                    if (typeof img === 'string') {
                        return { url: normalizeLocalImageUrl(img), caption: '', size: 'medium' };
                    }
                    const normalizedUrl = normalizeLocalImageUrl(img.url || '');
                    return { ...img, url: normalizedUrl };
                });
            } catch (e) {
                images = [];
            }
        }

        // Fonction pour mettre à jour l'affichage des images
        function updateImagesDisplay() {
            if (!uploadedImagesDiv) return;
            
            uploadedImagesDiv.innerHTML = '';
            images.forEach((img, index) => {
                const imgUrl = normalizeLocalImageUrl(typeof img === 'string' ? img : (img.url || ''));
                const caption = img.caption || '';
                const size = img.size || 'medium';
                
                const col = document.createElement('div');
                col.className = 'col-md-3 image-item';
                col.dataset.index = index;
                col.innerHTML = `
                    <div class="card">
                        <img src="${imgUrl}" class="card-img-top" style="height: 150px; object-fit: cover;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'150\'%3E%3Crect fill=\'%23ddd\' width=\'200\' height=\'150\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EImage%3C/text%3E%3C/svg%3E'">
                        <div class="card-body p-2">
                            <div class="mb-2">
                                <label class="form-label small">Légende</label>
                                <input type="text" class="form-control form-control-sm image-caption" 
                                       value="${caption.replace(/"/g, '&quot;')}" 
                                       placeholder="Légende de l'image">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Dimension</label>
                                <select class="form-select form-select-sm image-size">
                                    <option value="small" ${size === 'small' ? 'selected' : ''}>Petite (1/3)</option>
                                    <option value="medium" ${size === 'medium' ? 'selected' : ''}>Moyenne (1/2)</option>
                                    <option value="large" ${size === 'large' ? 'selected' : ''}>Grande (2/3)</option>
                                    <option value="full" ${size === 'full' ? 'selected' : ''}>Pleine largeur</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger w-100 remove-image" data-index="${index}">
                                <i class="bi bi-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                `;
                uploadedImagesDiv.appendChild(col);
            });
            
            // Ajouter les event listeners pour les champs de légende et dimension
            uploadedImagesDiv.querySelectorAll('.image-caption').forEach((input, idx) => {
                input.addEventListener('input', function() {
                    if (images[idx]) {
                        if (typeof images[idx] === 'string') {
                            images[idx] = { url: images[idx], caption: this.value, size: 'medium' };
                        } else {
                            images[idx].caption = this.value;
                        }
                        saveImages();
                    }
                });
            });
            
            uploadedImagesDiv.querySelectorAll('.image-size').forEach((select, idx) => {
                select.addEventListener('change', function() {
                    if (images[idx]) {
                        if (typeof images[idx] === 'string') {
                            images[idx] = { url: images[idx], caption: '', size: this.value };
                        } else {
                            images[idx].size = this.value;
                        }
                        saveImages();
                    }
                });
            });
        }
        
        // Fonction pour sauvegarder les images
        function saveImages() {
            if (imagesInput) {
                imagesInput.value = JSON.stringify(images);
            }
        }

        // Upload d'image
        if (imageUpload) {
            imageUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('image', file);

                uploadProgress.classList.remove('d-none');
                const progressBar = uploadProgress.querySelector('.progress-bar');
                progressBar.style.width = '0%';

                fetch('upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    uploadProgress.classList.add('d-none');
                    if (data.error) {
                        alert('Erreur: ' + data.error);
                    } else if (data.url) {
                        images.push({ url: normalizeLocalImageUrl(data.url), caption: '', size: 'medium' });
                        updateImagesDisplay();
                        imageUpload.value = '';
                        // Afficher un message si un thumbnail a été généré
                        if (data.thumbnail) {
                            console.log('Thumbnail généré: ' + data.thumbnail);
                        }
                    }
                })
                .catch(error => {
                    uploadProgress.classList.add('d-none');
                    alert('Erreur lors de l\'upload: ' + error.message);
                });
            });
        }

        // Ajouter une image par URL
        if (addImageUrlBtn && imageUrlInput) {
            addImageUrlBtn.addEventListener('click', function() {
                const url = imageUrlInput.value.trim();
                if (url) {
                    const normalizedUrl = normalizeLocalImageUrl(url);
                    // Vérifier si l'URL existe déjà
                    const exists = images.some(img => {
                        const imgUrl = typeof img === 'string' ? img : (img.url || '');
                        return imgUrl === normalizedUrl;
                    });
                    if (!exists) {
                        images.push({ url: normalizedUrl, caption: '', size: 'medium' });
                        updateImagesDisplay();
                        imageUrlInput.value = '';
                    }
                }
            });

            imageUrlInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    addImageUrlBtn.click();
                }
            });
        }

        // Supprimer une image
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-image')) {
                const index = parseInt(e.target.closest('.remove-image').dataset.index);
                if (confirm('Supprimer cette image ?')) {
                    const imageToDelete = images[index];
                    const imageUrl = typeof imageToDelete === 'string' ? imageToDelete : (imageToDelete.url || '');
                    
                    // Supprimer le fichier physique si c'est une image locale
                    if (imageUrl && !imageUrl.startsWith('http://') && !imageUrl.startsWith('https://')) {
                        const formData = new FormData();
                        formData.append('url', imageUrl);
                        
                        fetch('delete-image.php', {
                            method: 'POST',
                            body: formData
                        }).catch(error => {
                            console.error('Erreur lors de la suppression du fichier:', error);
                        });
                    }
                    
                    // Retirer l'image de la liste
                    images.splice(index, 1);
                    updateImagesDisplay();
                }
            }
        });

        // Initialiser l'affichage
        updateImagesDisplay();

        // Conversion des images en JSON avant soumission
        document.querySelector('form[method="POST"]')?.addEventListener('submit', function(e) {
            // S'assurer que toutes les légendes et dimensions sont sauvegardées
            uploadedImagesDiv?.querySelectorAll('.image-item').forEach((item, idx) => {
                const caption = item.querySelector('.image-caption')?.value || '';
                const size = item.querySelector('.image-size')?.value || 'medium';
                if (images[idx]) {
                    if (typeof images[idx] === 'string') {
                        images[idx] = { url: images[idx], caption: caption, size: size };
                    } else {
                        images[idx].caption = caption;
                        images[idx].size = size;
                    }
                }
            });
            
            if (imagesInput) {
                imagesInput.value = JSON.stringify(images);
            }
            if (easyMDE) {
                easyMDE.toTextArea();
            }
        });

        // Suppression d'une rubrique
        function deleteRubrique(id, title) {
            if (confirm('Êtes-vous sûr de vouloir supprimer la rubrique "' + title + '" ?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Réorganisation avec SortableJS
        <?php if ($action === 'list' && !empty($rubriques)): ?>
        const list = document.getElementById('rubriquesList');
        if (list) {
            new Sortable(list, {
                animation: 150,
                onEnd: function() {
                    const order = Array.from(list.children).map(li => li.dataset.id);
                    document.getElementById('orderInput').value = JSON.stringify(order);
                    document.getElementById('reorderForm').submit();
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>

