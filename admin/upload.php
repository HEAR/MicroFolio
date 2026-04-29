<?php
/**
 * Endpoint pour l'upload d'images
 */
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun fichier fourni']);
    exit;
}

$result = uploadImage($_FILES['image']);

if (isset($result['error'])) {
    http_response_code(400);
    echo json_encode($result);
} else {
    echo json_encode($result);
}
