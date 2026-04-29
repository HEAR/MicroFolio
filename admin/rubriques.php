<?php
require_once __DIR__ . '/../includes/auth.php';
handleLogout();
requireAuth();

$action = $_GET['action'] ?? 'list';
$message = $_GET['message'] ?? null;
$error = null;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session invalide, veuillez recharger la page';
    } elseif (isset($_POST['create'])) {
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
                'is_homepage' => !empty($_POST['is_homepage']),
                'gallery_position' => ($_POST['gallery_position'] ?? 'before') === 'after' ? 'after' : 'before'
            ];
            
            if (createRubrique($data)) {
                $message = 'Page créée avec succès';
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
                $error = 'Erreur lors de la création de la page. Vérifiez les permissions d\'écriture sur le fichier rubriques.json';
            }
        }
    } elseif (isset($_POST['update'])) {
        $id = $_POST['id'] ?? '';
        $data = [
            'title' => $_POST['title'] ?? '',
            'slug' => $_POST['slug'] ?? '',
            'content' => $_POST['content'] ?? '',
            'images' => json_decode($_POST['images'] ?? '[]', true),
            'is_homepage' => !empty($_POST['is_homepage']),
            'gallery_position' => ($_POST['gallery_position'] ?? 'before') === 'after' ? 'after' : 'before'
        ];
        if (updateRubrique($id, $data)) {
            $message = 'Page mise à jour avec succès';
            // Rediriger vers la page d'édition pour rester sur la page
            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
            header('Location: ' . $basePath . '/' . ADMIN_PATH . '/rubriques.php?action=edit&id=' . $id . '&message=' . urlencode($message));
            exit;
        } else {
            $error = 'Erreur lors de la mise à jour de la page';
        }
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'] ?? '';
        if (deleteRubrique($id)) {
            $message = 'Page supprimée avec succès';
        } else {
            $error = 'Erreur lors de la suppression de la page';
        }
    } elseif (isset($_POST['reorder'])) {
        $order = json_decode($_POST['order'] ?? '[]', true);
        if (reorderRubriques($order)) {
            $message = 'Ordre des pages mis à jour';
        } else {
            $error = 'Erreur lors de la réorganisation';
        }
    }
}

// Traitement des actions GET (hors du bloc POST)
if (isset($_GET['set_homepage'])) {
    if (!validateCsrfToken($_GET['csrf_token'] ?? '')) {
        $error = 'Session invalide, veuillez recharger la page';
    } else {
    $id = $_GET['set_homepage'] ?? '';
    if (setHomepageRubrique($id)) {
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        header('Location: ' . $basePath . '/' . ADMIN_PATH . '/rubriques.php?message=' . urlencode('Page d\'accueil définie avec succès'));
        exit;
    } else {
        $error = 'Erreur lors de la définition de la page d\'accueil';
    }
    }
} elseif (isset($_GET['unset_homepage'])) {
    if (!validateCsrfToken($_GET['csrf_token'] ?? '')) {
        $error = 'Session invalide, veuillez recharger la page';
    } elseif (unsetHomepageRubrique()) {
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
        $error = 'Page introuvable';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des pages - MicroFolio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">MicroFolio</a>
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
                            <i class="bi bi-folder"></i> Pages
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
            <div class="admin-page-header">
                <h1 class="admin-page-title">Gestion des pages</h1>
                <div class="admin-page-actions">
                    <a href="generate-thumbnails.php" class="btn btn-outline-secondary me-2" title="Générer les thumbnails des images existantes">
                        <i class="bi bi-image"></i> Générer les miniatures
                    </a>
                    <a href="?action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Créer une page
                    </a>
                </div>
            </div>

            <?php if (empty($rubriques)): ?>
                <div class="alert alert-info">
                    Aucune page pour le moment. <a href="?action=create">Créer la première page</a>
                </div>
            <?php else: ?>
                <div class="card admin-section-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Liste des pages (glissez pour réorganiser)</h5>
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
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <input type="hidden" name="order" id="orderInput">
                            <ul class="list-group" id="rubriquesList">
                                <?php foreach ($rubriques as $rub): 
                                    $isHomepage = !empty($rub['is_homepage']);
                                ?>
                                <li class="list-group-item rubrique-item-compact" data-id="<?= $rub['id'] ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-start">
                                            <span class="drag-handle" title="Glisser pour réorganiser">
                                                <i class="bi bi-grip-vertical"></i>
                                            </span>
                                            <div>
                                            <?php
                                                $hostedImageCount = 0;
                                                $externalImageCount = 0;
                                                foreach ($rub['images'] ?? [] as $img) {
                                                    $imgUrl = is_array($img) ? ($img['url'] ?? '') : $img;
                                                    if (!empty($imgUrl)) {
                                                        if (preg_match('/^https?:\/\//i', $imgUrl)) {
                                                            $externalImageCount++;
                                                        } else {
                                                            $hostedImageCount++;
                                                        }
                                                    }
                                                }

                                                $pdfCount = 0;
                                                $content = $rub['content'] ?? '';
                                                if (!empty($content)) {
                                                    preg_match_all('/(?:https?:\/\/[^\s\)]+)?\/assets\/docs\/([a-zA-Z0-9._-]+\.pdf)/i', $content, $matches);
                                                    if (!empty($matches[1])) {
                                                        $pdfCount = count(array_unique($matches[1]));
                                                    }
                                                }
                                                $galleryPositionLabel = (($rub['gallery_position'] ?? 'before') === 'after')
                                                    ? 'Galerie: apres contenu'
                                                    : 'Galerie: avant contenu';
                                            ?>
                                            <h5 class="rubrique-title-compact d-flex align-items-center flex-wrap gap-2">
                                                <?= htmlspecialchars($rub['title']) ?>
                                                <?php if ($isHomepage): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-house"></i> Page d'accueil
                                                    </span>
                                                <?php endif; ?>
                                            </h5>
                                            <div class="rubrique-meta-compact text-muted">
                                                <code><?= htmlspecialchars($rub['slug']) ?></code> - <?= htmlspecialchars(substr($rub['created_at'], 0, 10)) ?>
                                            </div>
                                            <div class="rubrique-stats-compact mt-1 d-flex flex-wrap gap-1">
                                                <span class="badge bg-light text-dark border">IMG local: <?= $hostedImageCount ?></span>
                                                <span class="badge bg-light text-dark border">IMG externe: <?= $externalImageCount ?></span>
                                                <span class="badge bg-light text-dark border">PDF: <?= $pdfCount ?></span>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($galleryPositionLabel) ?></span>
                                            </div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <?php if ($isHomepage): ?>
                                                <a href="?unset_homepage=1&csrf_token=<?= urlencode(csrfToken()) ?>" class="btn btn-sm btn-outline-warning admin-icon-btn" title="Retirer de la page d'accueil" onclick="return confirm('Retirer cette page de la page d\'accueil ?')">
                                                    <i class="bi bi-house-x"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?set_homepage=<?= $rub['id'] ?>&csrf_token=<?= urlencode(csrfToken()) ?>" class="btn btn-sm btn-outline-success admin-icon-btn" title="Définir comme page d'accueil" onclick="return confirm('Définir cette page comme page d\'accueil ? Elle sera cachée du menu.')">
                                                    <i class="bi bi-house"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?action=edit&id=<?= $rub['id'] ?>" class="btn btn-sm btn-outline-primary admin-icon-btn" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger admin-icon-btn" title="Supprimer" onclick="deleteRubrique('<?= $rub['id'] ?>', '<?= htmlspecialchars($rub['title']) ?>')">
                                                <i class="bi bi-trash"></i>
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
            <div class="admin-page-header">
                <h1 class="admin-page-title"><?= $action === 'create' ? 'Créer une page' : 'Modifier la page' ?></h1>
                <div class="admin-page-actions d-flex gap-2">
                    <?php if ($action === 'edit' && !empty($rubrique['slug'])): ?>
                        <a href="<?= htmlspecialchars(getRubriqueUrl($rubrique['slug'])) ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-eye"></i> Voir la page
                        </a>
                    <?php endif; ?>
                    <a href="rubriques.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card admin-section-card">
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $rubrique['id'] ?>">
                            <input type="hidden" name="update" value="1">
                        <?php else: ?>
                            <input type="hidden" name="create" value="1">
                        <?php endif; ?>

                        <div class="form-section">
                            <h6>Informations de la page</h6>
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

                            <?php $galleryPosition = (($rubrique['gallery_position'] ?? 'before') === 'after') ? 'after' : 'before'; ?>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_homepage" name="is_homepage" value="1" <?= !empty($rubrique['is_homepage']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_homepage">
                                            Définir comme page d'accueil
                                        </label>
                                        <small class="form-text text-muted d-block">Si coché, cette page sera affichée à la racine du site et cachée du menu. Une seule page peut être définie comme page d'accueil.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="gallery_position" class="form-label">Position de la galerie d'images</label>
                                    <select class="form-select" id="gallery_position" name="gallery_position">
                                        <option value="before" <?= $galleryPosition === 'before' ? 'selected' : '' ?>>Avant le contenu Markdown</option>
                                        <option value="after" <?= $galleryPosition === 'after' ? 'selected' : '' ?>>Après le contenu Markdown</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6>Contenu</h6>
                            <div class="mb-0">
                                <label for="content" class="form-label">Contenu (Markdown)</label>
                                <textarea class="form-control" id="content" name="content" rows="15"><?= htmlspecialchars($rubrique['content'] ?? '') ?></textarea>
                                <small class="form-text text-muted">Vous pouvez utiliser la syntaxe Markdown pour formater votre texte</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6>Documents PDF</h6>
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="documentUpload" class="form-label">Uploader un PDF</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="documentUpload" accept="application/pdf,.pdf">
                                            <button type="button" class="btn btn-outline-primary" id="uploadDocumentBtn" disabled>
                                                <i class="bi bi-upload"></i> Téléverser
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">Format accepté: PDF (max 20MB)</small>
                                    </div>
                                    <?php
                                        $existingPdfUrls = [];
                                        $rubriqueContent = $rubrique['content'] ?? '';
                                        if (!empty($rubriqueContent)) {
                                            preg_match_all('/(?:https?:\/\/[^\s\)]+)?\/assets\/docs\/[a-zA-Z0-9._-]+\.pdf/i', $rubriqueContent, $pdfMatches);
                                            if (!empty($pdfMatches[0])) {
                                                $existingPdfUrls = array_values(array_unique($pdfMatches[0]));
                                            }
                                        }
                                    ?>
                                    <div id="uploadedDocuments" class="list-group">
                                        <?php foreach ($existingPdfUrls as $pdfUrl): ?>
                                            <?php
                                                $displayPdfUrl = resolveAssetUrl($pdfUrl);
                                                $pdfFileName = basename(parse_url($displayPdfUrl, PHP_URL_PATH) ?: $displayPdfUrl);
                                            ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="text-truncate me-2" style="max-width: 70%;"><?= htmlspecialchars($displayPdfUrl) ?></span>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-success insert-document-link"
                                                            data-url="<?= htmlspecialchars($displayPdfUrl) ?>"
                                                            data-label="<?= htmlspecialchars($pdfFileName) ?>">
                                                        <i class="bi bi-markdown"></i> Insérer
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary copy-document-url"
                                                            data-url="<?= htmlspecialchars($displayPdfUrl) ?>">
                                                        <i class="bi bi-clipboard"></i> Copier
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-document" data-url="<?= htmlspecialchars($displayPdfUrl) ?>">
                                                        <i class="bi bi-trash"></i> Supprimer
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="form-text text-muted mt-2 d-block">Après upload, copiez le lien et collez-le dans le Markdown.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6>Images</h6>
                            <div class="card">
                                <div class="card-body">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label for="imageUpload" class="form-label">Uploader une image</label>
                                            <input type="file" class="form-control" id="imageUpload" accept="image/jpeg,image/png,image/gif,image/webp">
                                            <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Ou ajouter une URL d'image</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="imageUrlInput" placeholder="https://example.com/image.jpg">
                                                <button type="button" class="btn btn-outline-secondary" id="addImageUrl">
                                                    <i class="bi bi-plus"></i> Ajouter
                                                </button>
                                            </div>
                                        </div>
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
                                                                    <option value="sixth" <?= $imgInfo['size'] === 'sixth' ? 'selected' : '' ?>>Tres petite (1/6)</option>
                                                                    <option value="quarter" <?= $imgInfo['size'] === 'quarter' ? 'selected' : '' ?>>Petite (1/4)</option>
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
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const csrfToken = <?= json_encode(csrfToken()) ?>;
        const basePath = <?= json_encode(BASE_PATH) ?>;
        const currentRubriqueId = <?= json_encode(($action === 'edit' && !empty($rubrique['id'])) ? $rubrique['id'] : '') ?>;
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
        const documentUpload = document.getElementById('documentUpload');
        const uploadDocumentBtn = document.getElementById('uploadDocumentBtn');
        const uploadedDocumentsDiv = document.getElementById('uploadedDocuments');

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

        function getDisplayImageUrl(url) {
            if (!url) return url;
            if (url.startsWith('http://') || url.startsWith('https://')) {
                return url;
            }
            if (url.startsWith('/assets/') && basePath) {
                return basePath + url;
            }
            return url;
        }

        function getDisplayAssetUrl(url) {
            if (!url) return url;
            if (url.startsWith('http://') || url.startsWith('https://')) {
                return url;
            }
            if (url.startsWith('/assets/') && basePath) {
                return basePath + url;
            }
            return url;
        }

        function escapeForHtmlAttr(value) {
            return (value || '').replace(/"/g, '&quot;');
        }

        function insertMarkdownLink(url, label = 'Document PDF') {
            const markdown = `[${label}](${url})`;
            if (easyMDE) {
                const cm = easyMDE.codemirror;
                const doc = cm.getDoc();
                const cursor = doc.getCursor();
                const currentLine = doc.getLine(cursor.line) || '';
                const needsNewline = currentLine.length > 0 ? '\n' : '';
                doc.replaceRange(needsNewline + markdown, cursor);
                cm.focus();
            } else if (contentTextarea) {
                const sep = contentTextarea.value && !contentTextarea.value.endsWith('\n') ? '\n' : '';
                contentTextarea.value += sep + markdown;
                contentTextarea.focus();
            }
        }

        function addUploadedDocument(url) {
            if (!uploadedDocumentsDiv || !url) return;
            const displayUrl = getDisplayAssetUrl(url);
            const filename = displayUrl.split('/').pop() || 'document.pdf';
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `
                <span class="text-truncate me-2" style="max-width: 70%;">${displayUrl}</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success insert-document-link" data-url="${escapeForHtmlAttr(displayUrl)}" data-label="${escapeForHtmlAttr(filename)}">
                        <i class="bi bi-markdown"></i> Insérer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary copy-document-url" data-url="${escapeForHtmlAttr(displayUrl)}">
                        <i class="bi bi-clipboard"></i> Copier
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-document" data-url="${escapeForHtmlAttr(displayUrl)}">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </div>
            `;
            uploadedDocumentsDiv.prepend(item);
        }

        // Charger les images existantes et convertir l'ancien format si nécessaire
        if (imagesInput && imagesInput.value) {
            try {
                images = JSON.parse(imagesInput.value);
                // Convertir l'ancien format (tableau de strings) au nouveau format
                images = images.map(img => {
                    if (typeof img === 'string') {
                        return { url: normalizeLocalImageUrl(img), caption: '', size: 'medium', hidden: false, is_thumbnail: false };
                    }
                    const normalizedUrl = normalizeLocalImageUrl(img.url || '');
                    return { ...img, url: normalizedUrl, hidden: !!img.hidden, is_thumbnail: !!img.is_thumbnail };
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
                const displayImgUrl = getDisplayImageUrl(imgUrl);
                const isExternalImage = /^https?:\/\//i.test(imgUrl);
                const caption = img.caption || '';
                const size = img.size || 'medium';
                const hidden = !!img.hidden;
                const isThumbnail = !!img.is_thumbnail;
                
                const col = document.createElement('div');
                col.className = 'col-md-3 image-item';
                col.dataset.index = index;
                col.innerHTML = `
                    <div class="card">
                        <img src="${displayImgUrl}" class="card-img-top" style="height: 150px; object-fit: cover;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'150\'%3E%3Crect fill=\'%23ddd\' width=\'200\' height=\'150\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EImage%3C/text%3E%3C/svg%3E'">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="drag-handle image-drag-handle" style="min-height:auto;padding:0 .35rem;cursor:grab;" title="Glisser pour trier">
                                    <i class="bi bi-grip-vertical"></i>
                                </span>
                                ${isThumbnail ? '<span class="badge bg-primary">Thumbnail</span>' : '<span></span>'}
                            </div>
                            ${isExternalImage ? `
                                <div class="mb-2">
                                    <a href="${displayImgUrl}" target="_blank" rel="noopener noreferrer" class="badge bg-info text-decoration-none">Image externe</a>
                                </div>
                            ` : ''}
                            ${hidden ? '<span class="badge bg-secondary mb-2">Masquee</span>' : ''}
                            <div class="mb-2">
                                <label class="form-label small">Légende</label>
                                <input type="text" class="form-control form-control-sm image-caption" 
                                       value="${caption.replace(/"/g, '&quot;')}" 
                                       placeholder="Légende de l'image">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Dimension</label>
                                <select class="form-select form-select-sm image-size">
                                    <option value="sixth" ${size === 'sixth' ? 'selected' : ''}>Tres petite (1/6)</option>
                                    <option value="quarter" ${size === 'quarter' ? 'selected' : ''}>Petite (1/4)</option>
                                    <option value="small" ${size === 'small' ? 'selected' : ''}>Petite (1/3)</option>
                                    <option value="medium" ${size === 'medium' ? 'selected' : ''}>Moyenne (1/2)</option>
                                    <option value="large" ${size === 'large' ? 'selected' : ''}>Grande (2/3)</option>
                                    <option value="full" ${size === 'full' ? 'selected' : ''}>Pleine largeur</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 mb-2 toggle-image-hidden" data-index="${index}">
                                <i class="bi ${hidden ? 'bi-eye' : 'bi-eye-slash'}"></i> ${hidden ? 'Afficher' : 'Masquer'}
                            </button>
                            <button type="button" class="btn btn-sm ${isThumbnail ? 'btn-primary' : 'btn-outline-primary'} w-100 mb-2 set-thumbnail-image" data-index="${index}">
                                <i class="bi bi-star${isThumbnail ? '-fill' : ''}"></i> ${isThumbnail ? 'Thumbnail active' : 'Definir thumbnail'}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-2 copy-image-url" data-index="${index}">
                                <i class="bi bi-clipboard"></i> Copier l'URL
                            </button>
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
                            images[idx] = { url: images[idx], caption: '', size: this.value, hidden: false, is_thumbnail: false };
                        } else {
                            images[idx].size = this.value;
                        }
                        saveImages();
                    }
                });
            });

            if (typeof Sortable !== 'undefined') {
                new Sortable(uploadedImagesDiv, {
                    animation: 150,
                    handle: '.image-drag-handle',
                    draggable: '.image-item',
                    onEnd: function() {
                        const reordered = Array.from(uploadedImagesDiv.querySelectorAll('.image-item'))
                            .map(item => images[parseInt(item.dataset.index, 10)])
                            .filter(Boolean);
                        images = reordered;
                        saveImages();
                        updateImagesDisplay();
                    }
                });
            }
        }
        
        // Fonction pour sauvegarder les images
        function saveImages() {
            if (imagesInput) {
                imagesInput.value = JSON.stringify(images);
            }
        }

        function persistImagesNow() {
            if (!currentRubriqueId) return Promise.resolve(true);

            const formData = new FormData();
            formData.append('id', currentRubriqueId);
            formData.append('images', JSON.stringify(images));

            return fetch('save-images.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrfToken
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Erreur de sauvegarde');
                }
                return true;
            });
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
                    headers: {
                        'X-CSRF-Token': csrfToken
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    uploadProgress.classList.add('d-none');
                    if (data.error) {
                        alert('Erreur: ' + data.error);
                    } else if (data.url) {
                        images.push({ url: normalizeLocalImageUrl(data.url), caption: '', size: 'medium', hidden: false, is_thumbnail: false });
                        updateImagesDisplay();
                        imageUpload.value = '';
                    }
                })
                .catch(error => {
                    uploadProgress.classList.add('d-none');
                    alert('Erreur lors de l\'upload: ' + error.message);
                });
            });
        }

        // Upload de PDF (validation explicite via bouton)
        function uploadSelectedDocument() {
            if (!documentUpload) return;
            const file = documentUpload.files[0];
            if (!file) {
                alert('Selectionnez un fichier PDF avant de televerser.');
                return;
            }

            const formData = new FormData();
            formData.append('document', file);

            if (uploadDocumentBtn) {
                uploadDocumentBtn.disabled = true;
            }

            fetch('upload-document.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrfToken
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Erreur: ' + data.error);
                } else if (data.url) {
                    const displayUrl = getDisplayAssetUrl(data.url);
                    addUploadedDocument(data.url);
                    alert('PDF televerse. Utilisez "Insérer" ou "Copier" dans la liste.');
                    documentUpload.value = '';
                }
            })
            .catch(error => {
                alert('Erreur lors de l\'upload du PDF: ' + error.message);
            })
            .finally(() => {
                if (uploadDocumentBtn) {
                    uploadDocumentBtn.disabled = !documentUpload.files.length;
                }
            });
        }

        if (documentUpload) {
            documentUpload.addEventListener('change', function() {
                if (uploadDocumentBtn) {
                    uploadDocumentBtn.disabled = !documentUpload.files.length;
                }
            });
        }

        if (uploadDocumentBtn) {
            uploadDocumentBtn.addEventListener('click', uploadSelectedDocument);
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
                        images.push({ url: normalizedUrl, caption: '', size: 'medium', hidden: false, is_thumbnail: false });
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
            if (e.target.closest('.toggle-image-hidden')) {
                const index = parseInt(e.target.closest('.toggle-image-hidden').dataset.index);
                if (images[index]) {
                    if (typeof images[index] === 'string') {
                        images[index] = { url: images[index], caption: '', size: 'medium', hidden: true, is_thumbnail: false };
                    } else {
                        images[index].hidden = !images[index].hidden;
                        if (images[index].hidden) {
                            images[index].is_thumbnail = false;
                        }
                    }
                    saveImages();
                    updateImagesDisplay();
                }
                return;
            }

            if (e.target.closest('.set-thumbnail-image')) {
                const index = parseInt(e.target.closest('.set-thumbnail-image').dataset.index);
                if (!images[index]) return;

                if (images[index].hidden) {
                    images[index].hidden = false;
                }

                images = images.map((img, i) => {
                    if (typeof img === 'string') {
                        return { url: img, caption: '', size: 'medium', hidden: false, is_thumbnail: i === index };
                    }
                    return { ...img, is_thumbnail: i === index };
                });
                saveImages();
                updateImagesDisplay();
                return;
            }

            if (e.target.closest('.copy-image-url')) {
                const index = parseInt(e.target.closest('.copy-image-url').dataset.index);
                const image = images[index];
                if (!image) return;

                const imageUrl = typeof image === 'string' ? image : (image.url || '');
                const normalizedUrl = normalizeLocalImageUrl(imageUrl);
                const copyUrl = getDisplayImageUrl(normalizedUrl);

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(copyUrl).then(() => {
                        alert('URL copiée: ' + copyUrl);
                    }).catch(() => {
                        alert('Impossible de copier automatiquement. URL: ' + copyUrl);
                    });
                } else {
                    alert('Copie non supportée par ce navigateur. URL: ' + copyUrl);
                }
                return;
            }

            if (e.target.closest('.copy-document-url')) {
                const button = e.target.closest('.copy-document-url');
                const url = button.dataset.url || '';
                if (!url) return;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(() => {
                        alert('URL copiée: ' + url);
                    }).catch(() => {
                        alert('Impossible de copier automatiquement. URL: ' + url);
                    });
                } else {
                    alert('Copie non supportée par ce navigateur. URL: ' + url);
                }
                return;
            }

            if (e.target.closest('.insert-document-link')) {
                const button = e.target.closest('.insert-document-link');
                const url = button.dataset.url || '';
                const label = button.dataset.label || 'Document PDF';
                if (!url) return;
                insertMarkdownLink(url, label);
                return;
            }

            if (e.target.closest('.delete-document')) {
                const button = e.target.closest('.delete-document');
                const url = button.dataset.url || '';
                if (!url) return;

                if (!confirm('Supprimer ce fichier PDF ?')) {
                    return;
                }

                const formData = new FormData();
                formData.append('url', url);

                fetch('delete-document.php', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': csrfToken
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Erreur: ' + data.error);
                        return;
                    }

                    // Retirer visuellement l'item
                    const row = button.closest('.list-group-item');
                    if (row) {
                        row.remove();
                    }

                    // Supprimer les occurrences du lien dans le contenu markdown
                    if (easyMDE) {
                        const cm = easyMDE.codemirror;
                        const doc = cm.getDoc();
                        let content = doc.getValue();
                        const escapedUrl = url.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        const markdownLinkPattern = new RegExp(`\\[[^\\]]*\\]\\(${escapedUrl}\\)\\n?`, 'g');
                        content = content.replace(markdownLinkPattern, '');
                        content = content.replace(new RegExp(`${escapedUrl}\\n?`, 'g'), '');
                        doc.setValue(content.trimEnd());
                    } else if (contentTextarea) {
                        let content = contentTextarea.value;
                        const escapedUrl = url.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        const markdownLinkPattern = new RegExp(`\\[[^\\]]*\\]\\(${escapedUrl}\\)\\n?`, 'g');
                        content = content.replace(markdownLinkPattern, '');
                        content = content.replace(new RegExp(`${escapedUrl}\\n?`, 'g'), '');
                        contentTextarea.value = content.trimEnd();
                    }
                })
                .catch(error => {
                    alert('Erreur lors de la suppression du PDF: ' + error.message);
                });
                return;
            }

            if (e.target.closest('.remove-image')) {
                const index = parseInt(e.target.closest('.remove-image').dataset.index);
                if (confirm('Supprimer cette image ?')) {
                    const imageToDelete = images[index];
                    const imageUrl = typeof imageToDelete === 'string' ? imageToDelete : (imageToDelete.url || '');
                    const wasThumbnail = !!(typeof imageToDelete === 'object' && imageToDelete.is_thumbnail);

                    const finalizeRemoval = () => {
                        images.splice(index, 1);
                        if (wasThumbnail && images.length > 0 && !images.some(img => typeof img === 'object' && img.is_thumbnail)) {
                            const firstVisible = images.findIndex(img => !(typeof img === 'object' && img.hidden));
                            if (firstVisible >= 0 && typeof images[firstVisible] === 'object') {
                                images[firstVisible].is_thumbnail = true;
                            }
                        }

                        saveImages();
                        updateImagesDisplay();
                        persistImagesNow().catch(error => {
                            alert('Image retiree localement, mais sauvegarde automatique impossible: ' + error.message + '. Cliquez sur Enregistrer.');
                        });
                    };

                    // Supprimer le fichier physique si c'est une image locale
                    if (imageUrl && !imageUrl.startsWith('http://') && !imageUrl.startsWith('https://')) {
                        const formData = new FormData();
                        formData.append('url', imageUrl);

                        fetch('delete-image.php', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-Token': csrfToken
                            },
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert('Erreur lors de la suppression du fichier: ' + data.error);
                                return;
                            }
                            finalizeRemoval();
                        })
                        .catch(error => {
                            alert('Erreur lors de la suppression du fichier: ' + error.message);
                        });
                    } else {
                        finalizeRemoval();
                    }
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
                        images[idx] = { url: images[idx], caption: caption, size: size, hidden: false, is_thumbnail: false };
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

        // Suppression d'une page
        function deleteRubrique(id, title) {
            if (confirm('Êtes-vous sûr de vouloir supprimer la page "' + title + '" ?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Réorganisation avec SortableJS
        <?php if ($action === 'list' && !empty($rubriques)): ?>
        const list = document.getElementById('rubriquesList');
        if (list) {
            const sortable = new Sortable(list, {
                animation: 150,
                handle: '.drag-handle',
                draggable: '.list-group-item',
                filter: 'a,button,input,textarea,select,label',
                preventOnFilter: false,
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

