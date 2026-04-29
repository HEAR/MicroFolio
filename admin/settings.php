<?php
require_once __DIR__ . '/../includes/auth.php';
handleLogout();
requireAuth();

$message = null;
$error = null;

// Traitement de la sauvegarde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $siteName = trim($_POST['site_name'] ?? '');
    $siteFooter = trim($_POST['site_footer'] ?? '');
    
    if (empty($siteName)) {
        $error = 'Le nom du site ne peut pas être vide';
    } else {
        if (updateConfig([
            'site_name' => $siteName,
            'site_footer' => $siteFooter
        ])) {
            $message = 'Paramètres sauvegardés avec succès';
        } else {
            $error = 'Erreur lors de la sauvegarde';
        }
    }
}

$config = getConfig();
$siteName = $config['site_name'] ?? 'Mon Portfolio';
$siteFooter = $config['site_footer'] ?? '© ' . date('Y') . ' Mon Portfolio';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres du site - Admin Portfolio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
                        <a class="nav-link" href="rubriques.php">
                            <i class="bi bi-folder"></i> Rubriques
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="custom.php">
                            <i class="bi bi-code-slash"></i> Personnalisation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
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

        <h1 class="mb-4">Paramètres du site</h1>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informations générales</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="save_settings" value="1">
                    
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Titre du site</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" 
                               value="<?= htmlspecialchars($siteName) ?>" required>
                        <small class="form-text text-muted">Le titre apparaît dans l'en-tête du site et dans l'onglet du navigateur</small>
                    </div>

                    <div class="mb-3">
                        <label for="site_footer" class="form-label">Texte du footer</label>
                        <input type="text" class="form-control" id="site_footer" name="site_footer" 
                               value="<?= htmlspecialchars($siteFooter) ?>">
                        <small class="form-text text-muted">Exemple: © 2026 Mon Portfolio</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer les paramètres
                    </button>
                    <a href="<?= BASE_PATH ?>/index.php" target="_blank" class="btn btn-outline-secondary">
                        <i class="bi bi-eye"></i> Voir le site
                    </a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
