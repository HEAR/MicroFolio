<?php
/**
 * Gestion de l'authentification
 */

require_once __DIR__ . '/functions.php';

/**
 * Rediriger vers la page de connexion si non connecté
 */
function requireAuth() {
    if (!isLoggedIn()) {
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        header('Location: ' . $basePath . '/' . ADMIN_PATH . '/index.php');
        exit;
    }
}

/**
 * Traiter la connexion
 */
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            return ['error' => 'Veuillez remplir tous les champs'];
        }
        
        $user = verifyCredentials($username, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
            header('Location: ' . $basePath . '/' . ADMIN_PATH . '/dashboard.php');
            exit;
        } else {
            return ['error' => 'Identifiants incorrects'];
        }
    }
    return null;
}

/**
 * Traiter la création de compte (premier admin)
 */
function handleRegister() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($username) || empty($password) || empty($confirm_password)) {
            return ['error' => 'Veuillez remplir tous les champs'];
        }
        
        if ($password !== $confirm_password) {
            return ['error' => 'Les mots de passe ne correspondent pas'];
        }
        
        if (strlen($password) < 6) {
            return ['error' => 'Le mot de passe doit contenir au moins 6 caractères'];
        }
        
        if (adminExists()) {
            return ['error' => 'Un compte admin existe déjà'];
        }
        
        if (createAdmin($username, $password)) {
            $user = verifyCredentials($username, $password);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
            header('Location: ' . $basePath . '/' . ADMIN_PATH . '/dashboard.php');
            exit;
        } else {
            return ['error' => 'Erreur lors de la création du compte'];
        }
    }
    return null;
}

/**
 * Déconnexion
 */
function handleLogout() {
    if (isset($_GET['logout'])) {
        session_destroy();
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        header('Location: ' . $basePath . '/' . ADMIN_PATH . '/index.php');
        exit;
    }
}

