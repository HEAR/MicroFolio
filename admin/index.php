<?php
require_once __DIR__ . '/../includes/auth.php';

handleLogout();

// Si déjà connecté, rediriger vers le dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$loginError = handleLogin();
$registerError = handleRegister();
$showRegister = !adminExists();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Admin Portfolio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2rem;
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="text-center mb-4">Portfolio CMS</h2>
        
        <?php if ($showRegister): ?>
            <h5 class="text-center mb-3 text-muted">Création du compte administrateur</h5>
            <?php if ($registerError && isset($registerError['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($registerError['error']) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="register" class="btn btn-primary w-100">Créer le compte</button>
            </form>
        <?php else: ?>
            <h5 class="text-center mb-3 text-muted">Connexion</h5>
            <?php if ($loginError && isset($loginError['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($loginError['error']) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100">Se connecter</button>
            </form>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

