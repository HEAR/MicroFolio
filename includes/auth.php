<?php
/**
 * Gestion de l'authentification
 */

require_once __DIR__ . '/functions.php';

define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_WINDOW_SECONDS', 900); // 15 minutes
define('LOGIN_LOCK_SECONDS', 900);   // 15 minutes

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
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            return ['error' => 'Session invalide, veuillez recharger la page'];
        }
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $ip = getClientIp();
        $config = getConfig();
        $failedPasswordDetectionEnabled = !array_key_exists('failed_password_detection', $config) || !empty($config['failed_password_detection']);
        
        if (empty($username) || empty($password)) {
            return ['error' => 'Veuillez remplir tous les champs'];
        }

        if ($failedPasswordDetectionEnabled) {
            $secondsRemaining = 0;
            if (isLoginRateLimited($username, $ip, $secondsRemaining)) {
                $minutes = (int)ceil($secondsRemaining / 60);
                return ['error' => 'Trop de tentatives de connexion. Reessayez dans ' . $minutes . ' minute(s).'];
            }
        }
        
        $user = verifyCredentials($username, $password);
        if ($user) {
            if ($failedPasswordDetectionEnabled) {
                clearLoginAttempts($username, $ip);
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
            header('Location: ' . $basePath . '/' . ADMIN_PATH . '/dashboard.php');
            exit;
        } else {
            if ($failedPasswordDetectionEnabled) {
                recordLoginFailure($username, $ip);
            }
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
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            return ['error' => 'Session invalide, veuillez recharger la page'];
        }
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
            session_regenerate_id(true);
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

/**
 * Obtenir/créer un token CSRF pour la session
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier un token CSRF
 */
function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function getClientIp() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return substr($ip, 0, 64);
}

function loginAttemptKey($username, $ip) {
    return hash('sha256', strtolower(trim($username)) . '|' . $ip);
}

function getLoginAttemptsData() {
    $data = readJsonFile(LOGIN_ATTEMPTS_FILE);
    return is_array($data) ? $data : [];
}

function saveLoginAttemptsData($data) {
    return writeJsonFile(LOGIN_ATTEMPTS_FILE, $data);
}

function isLoginRateLimited($username, $ip, &$secondsRemaining = 0) {
    $secondsRemaining = 0;
    $data = getLoginAttemptsData();
    $key = loginAttemptKey($username, $ip);
    $entry = $data[$key] ?? null;

    if (!$entry || empty($entry['locked_until'])) {
        return false;
    }

    $lockedUntil = (int)$entry['locked_until'];
    $now = time();

    if ($lockedUntil <= $now) {
        unset($data[$key]);
        saveLoginAttemptsData($data);
        return false;
    }

    $secondsRemaining = $lockedUntil - $now;
    return true;
}

function recordLoginFailure($username, $ip) {
    $data = getLoginAttemptsData();
    $key = loginAttemptKey($username, $ip);
    $now = time();

    $entry = $data[$key] ?? ['attempts' => [], 'locked_until' => 0];
    $attempts = is_array($entry['attempts']) ? $entry['attempts'] : [];

    $attempts = array_values(array_filter($attempts, function($timestamp) use ($now) {
        return (int)$timestamp >= ($now - LOGIN_WINDOW_SECONDS);
    }));

    $attempts[] = $now;
    $entry['attempts'] = $attempts;

    if (count($attempts) >= LOGIN_MAX_ATTEMPTS) {
        $entry['locked_until'] = $now + LOGIN_LOCK_SECONDS;
        $entry['attempts'] = [];
    } else {
        $entry['locked_until'] = 0;
    }

    $data[$key] = $entry;
    saveLoginAttemptsData($data);
}

function clearLoginAttempts($username, $ip) {
    $data = getLoginAttemptsData();
    $key = loginAttemptKey($username, $ip);
    if (isset($data[$key])) {
        unset($data[$key]);
        saveLoginAttemptsData($data);
    }
}

