<?php
/**
 * Endpoint pour sauvegarder immediatement la liste des images d'une page
 */
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode non autorisee']);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF invalide']);
    exit;
}

$id = trim($_POST['id'] ?? '');
$imagesRaw = $_POST['images'] ?? '[]';
$images = json_decode($imagesRaw, true);

if ($id === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Identifiant de page manquant']);
    exit;
}

if (!is_array($images)) {
    http_response_code(400);
    echo json_encode(['error' => 'Format des images invalide']);
    exit;
}

if (updateRubriqueImages($id, $images)) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Impossible de sauvegarder les images']);
}
