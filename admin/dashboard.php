<?php
require_once __DIR__ . '/../includes/auth.php';
handleLogout();
requireAuth();

$rubriques = getRubriques();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Admin Portfolio</title>
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
                        <a class="nav-link active" href="dashboard.php">
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
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Tableau de bord</h1>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-folder"></i> Rubriques</h5>
                                <h2 class="mb-0"><?= count($rubriques) ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-check-circle"></i> Statut</h5>
                                <h2 class="mb-0">Actif</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-person"></i> Utilisateur</h5>
                                <h2 class="mb-0"><?= htmlspecialchars($_SESSION['username']) ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Actions rapides</h5>
                    </div>
                    <div class="card-body">
                        <a href="rubriques.php?action=create" class="btn btn-primary me-2">
                            <i class="bi bi-plus-circle"></i> Créer une rubrique
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
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Dernières rubriques</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Titre</th>
                                        <th>Slug</th>
                                        <th>Date de création</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($rubriques, -5) as $rubrique): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($rubrique['title']) ?></td>
                                        <td><code><?= htmlspecialchars($rubrique['slug']) ?></code></td>
                                        <td><?= htmlspecialchars($rubrique['created_at']) ?></td>
                                        <td>
                                            <a href="rubriques.php?action=edit&id=<?= $rubrique['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

