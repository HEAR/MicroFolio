<?php
/**
 * Fichier de debug pour le routing
 */
echo "<h1>Debug Routing</h1>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'N/A') . "\n";
echo "GET params: " . print_r($_GET, true) . "\n";

require_once __DIR__ . '/includes/functions.php';
$basePath = defined('BASE_PATH') ? BASE_PATH : '';
echo "BASE_PATH: " . $basePath . "\n";

$slug = $_GET['slug'] ?? '';
echo "Slug from GET: " . $slug . "\n";

if (empty($slug)) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    echo "Request URI: " . $requestUri . "\n";
    
    if ($basePath && strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
        echo "Request URI after base path removal: " . $requestUri . "\n";
    }
    $requestUri = trim($requestUri, '/');
    echo "Request URI trimmed: " . $requestUri . "\n";
    
    $parts = explode('/', $requestUri);
    $slug = $parts[0] ?? '';
    echo "Extracted slug: " . $slug . "\n";
}

echo "</pre>";
?>
