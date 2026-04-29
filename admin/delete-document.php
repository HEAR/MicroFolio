<?php
/**
 * Endpoint pour supprimer un document PDF
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

$documentUrl = $_POST['url'] ?? '';
if (empty($documentUrl)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL du document manquante']);
    exit;
}

if (deleteDocument($documentUrl)) {
    echo json_encode(['success' => true, 'message' => 'Document supprimé avec succès']);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Erreur lors de la suppression du document']);
}
