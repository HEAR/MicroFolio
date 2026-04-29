<?php
require_once __DIR__ . '/../includes/auth.php';
handleLogout();
requireAuth();

$rubriques = getRubriques();
$isMaintenance = isMaintenanceMode();
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorder'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session invalide, veuillez recharger la page';
    } else {
        $order = json_decode($_POST['order'] ?? '[]', true);
        if (reorderRubriques($order)) {
            $message = 'Ordre des pages mis à jour';
            $rubriques = getRubriques();
        } else {
            $error = 'Erreur lors de la réorganisation';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - MicroFolio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rubriques.php">
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

        <div class="row">
            <div class="col-12">
                <div class="admin-page-header">
                    <h1 class="admin-page-title">Tableau de bord</h1>
                    <div class="admin-page-actions">
                        <a href="<?= BASE_PATH ?>/index.php" target="_blank" class="btn btn-outline-secondary">
                            <i class="bi bi-eye"></i> Voir le site
                        </a>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary stats-card-compact">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-folder"></i> Pages</h5>
                                <p class="card-value"><?= count($rubriques) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white <?= $isMaintenance ? 'bg-warning' : 'bg-success' ?> stats-card-compact">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-check-circle"></i> Statut</h5>
                                <p class="card-value"><?= $isMaintenance ? 'Maintenance' : 'Actif' ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info stats-card-compact">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-shield-lock"></i> Session</h5>
                                <p class="card-value">Admin connecté</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card admin-section-card">
                    <div class="card-header">
                        <h5 class="mb-0">Actions rapides</h5>
                    </div>
                    <div class="card-body">
                        <a href="rubriques.php?action=create" class="btn btn-primary me-2">
                            <i class="bi bi-plus-circle"></i> Créer une page
                        </a>
                        <a href="custom.php" class="btn btn-secondary me-2">
                            <i class="bi bi-palette"></i> Personnaliser le site
                        </a>
                        <a href="<?= BASE_PATH ?>/index.php" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-eye"></i> Voir le site
                        </a>
                    </div>
                </div>

                <?php if (!empty($rubriques)): ?>
                <div class="card admin-section-card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pages (glissez pour réorganiser)</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="dashboardReorderForm">
                            <input type="hidden" name="reorder" value="1">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <input type="hidden" name="order" id="dashboardOrderInput">
                            <ul class="list-group" id="dashboardRubriquesList">
                            <?php foreach ($rubriques as $rubrique): ?>
                                <?php
                                    $hostedImageCount = 0;
                                    $externalImageCount = 0;
                                    foreach ($rubrique['images'] ?? [] as $img) {
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
                                    $content = $rubrique['content'] ?? '';
                                    if (!empty($content)) {
                                        preg_match_all('/(?:https?:\/\/[^\s\)]+)?\/assets\/docs\/([a-zA-Z0-9._-]+\.pdf)/i', $content, $matches);
                                        if (!empty($matches[1])) {
                                            $pdfCount = count(array_unique($matches[1]));
                                        }
                                    }
                                    $isHomepage = !empty($rubrique['is_homepage']);
                                    $galleryPositionLabel = (($rubrique['gallery_position'] ?? 'before') === 'after')
                                        ? 'Galerie: apres contenu'
                                        : 'Galerie: avant contenu';
                                ?>
                                <li class="list-group-item rubrique-item-compact" data-id="<?= $rubrique['id'] ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-start">
                                            <span class="drag-handle" title="Glisser pour réorganiser">
                                                <i class="bi bi-grip-vertical"></i>
                                            </span>
                                            <div>
                                            <h5 class="rubrique-title-compact d-flex align-items-center flex-wrap gap-2">
                                                <?= htmlspecialchars($rubrique['title']) ?>
                                                <?php if ($isHomepage): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-house"></i> Page d'accueil
                                                    </span>
                                                <?php endif; ?>
                                            </h5>
                                            <div class="rubrique-meta-compact text-muted">
                                                <code><?= htmlspecialchars($rubrique['slug']) ?></code> - <?= htmlspecialchars(substr($rubrique['created_at'], 0, 10)) ?>
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
                                            <a href="rubriques.php?action=edit&id=<?= $rubrique['id'] ?>" class="btn btn-sm btn-outline-primary admin-icon-btn" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dashboardList = document.getElementById('dashboardRubriquesList');
        if (dashboardList && typeof Sortable !== 'undefined') {
            new Sortable(dashboardList, {
                animation: 150,
                handle: '.drag-handle',
                draggable: '.list-group-item',
                filter: 'a,button,input,textarea,select,label',
                preventOnFilter: false,
                onEnd: function() {
                    const order = Array.from(dashboardList.children).map(li => li.dataset.id);
                    document.getElementById('dashboardOrderInput').value = JSON.stringify(order);
                    document.getElementById('dashboardReorderForm').submit();
                }
            });
        }
    </script>
</body>
</html>

