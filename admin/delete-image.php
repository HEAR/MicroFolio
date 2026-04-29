<?php
/**
 * Endpoint pour supprimer une image
 */
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF invalide']);
    exit;
}

$imageUrl = $_POST['url'] ?? '';

if (empty($imageUrl)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL d\'image manquante']);
    exit;
}

if (deleteImage($imageUrl)) {
    echo json_encode(['success' => true, 'message' => 'Image supprimée avec succès']);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Erreur lors de la suppression de l\'image']);
}
