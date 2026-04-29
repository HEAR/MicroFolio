<?php
require_once __DIR__ . '/../includes/auth.php';
handleLogout();
requireAuth();

$message = null;
$error = null;

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_username'])) {
        $newUsername = trim($_POST['new_username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($newUsername) || empty($password)) {
            $error = 'Veuillez remplir tous les champs';
        } else {
            // Vérifier le mot de passe actuel
            $user = verifyCredentials($_SESSION['username'], $password);
            if ($user) {
                // Mettre à jour le nom d'utilisateur
                $users = readJsonFile(USERS_FILE);
                foreach ($users as &$u) {
                    if ($u['id'] === $_SESSION['user_id']) {
                        $u['username'] = $newUsername;
                        break;
                    }
                }
                if (writeJsonFile(USERS_FILE, $users)) {
                    $_SESSION['username'] = $newUsername;
                    $message = 'Nom d\'utilisateur mis à jour avec succès';
                } else {
                    $error = 'Erreur lors de la mise à jour';
                }
            } else {
                $error = 'Mot de passe incorrect';
            }
        }
    } elseif (isset($_POST['update_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Veuillez remplir tous les champs';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Les nouveaux mots de passe ne correspondent pas';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères';
        } else {
            // Vérifier le mot de passe actuel
            $user = verifyCredentials($_SESSION['username'], $currentPassword);
            if ($user) {
                // Mettre à jour le mot de passe
                $users = readJsonFile(USERS_FILE);
                foreach ($users as &$u) {
                    if ($u['id'] === $_SESSION['user_id']) {
                        $u['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                        break;
                    }
                }
                if (writeJsonFile(USERS_FILE, $users)) {
                    $message = 'Mot de passe mis à jour avec succès';
                } else {
                    $error = 'Erreur lors de la mise à jour';
                }
            } else {
                $error = 'Mot de passe actuel incorrect';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Admin Portfolio</title>
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
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i> Paramètres
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
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

        <h1 class="mb-4">Mon profil</h1>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Changer le nom d'utilisateur</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="update_username" value="1">
                            <div class="mb-3">
                                <label for="current_username" class="form-label">Nom d'utilisateur actuel</label>
                                <input type="text" class="form-control" id="current_username" 
                                       value="<?= htmlspecialchars($_SESSION['username']) ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="new_username" class="form-label">Nouveau nom d'utilisateur</label>
                                <input type="text" class="form-control" id="new_username" name="new_username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe actuel (confirmation)</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Mettre à jour le nom d'utilisateur
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Changer le mot de passe</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="update_password" value="1">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mot de passe actuel</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="form-text text-muted">Minimum 6 caractères</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key"></i> Mettre à jour le mot de passe
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
