<?php
/**
 * Fichier de diagnostic pour vérifier la configuration
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic - Portfolio CMS</title>
    <style>
        body {
            font-family: monospace;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
        }
        .success {
            border-left-color: #28a745;
            color: #28a745;
        }
        .error {
            border-left-color: #dc3545;
            color: #dc3545;
        }
        .warning {
            border-left-color: #ffc107;
            color: #856404;
        }
        h1 {
            color: #333;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic Portfolio CMS</h1>
    
    <?php
    $tests = [];
    
    // Test 1: Version PHP
    $phpVersion = phpversion();
    $tests[] = [
        'name' => 'Version PHP',
        'status' => version_compare($phpVersion, '7.4.0', '>=') ? 'success' : 'error',
        'message' => "Version PHP: $phpVersion " . (version_compare($phpVersion, '7.4.0', '>=') ? '✓' : '✗ (Minimum 7.4 requis)')
    ];
    
    // Test 2: Extensions PHP
    $requiredExtensions = ['json', 'session'];
    foreach ($requiredExtensions as $ext) {
        $loaded = extension_loaded($ext);
        $tests[] = [
            'name' => "Extension PHP: $ext",
            'status' => $loaded ? 'success' : 'error',
            'message' => $loaded ? "Extension $ext chargée ✓" : "Extension $ext manquante ✗"
        ];
    }
    
    // Test 3: Permissions du dossier data
    $dataDir = __DIR__ . '/data';
    $writable = is_writable($dataDir) || (!file_exists($dataDir) && is_writable(dirname($dataDir)));
    $tests[] = [
        'name' => 'Permissions dossier data',
        'status' => $writable ? 'success' : 'error',
        'message' => $writable ? 'Le dossier data est accessible en écriture ✓' : 'Le dossier data n\'est pas accessible en écriture ✗'
    ];
    
    // Test 4: Création du dossier data
    if (!file_exists($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
    $exists = file_exists($dataDir);
    $tests[] = [
        'name' => 'Dossier data existe',
        'status' => $exists ? 'success' : 'error',
        'message' => $exists ? 'Le dossier data existe ✓' : 'Impossible de créer le dossier data ✗'
    ];
    
    // Test 5: Fichiers de configuration
    $configFile = $dataDir . '/config.json';
    $canCreate = is_writable($dataDir);
    $tests[] = [
        'name' => 'Création fichiers de config',
        'status' => $canCreate ? 'success' : 'error',
        'message' => $canCreate ? 'Les fichiers de configuration peuvent être créés ✓' : 'Impossible de créer les fichiers de configuration ✗'
    ];
    
    // Test 6: Module mod_rewrite
    $rewriteEnabled = function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : 'inconnu';
    $tests[] = [
        'name' => 'Module mod_rewrite',
        'status' => $rewriteEnabled === true ? 'success' : ($rewriteEnabled === 'inconnu' ? 'warning' : 'error'),
        'message' => $rewriteEnabled === true ? 'mod_rewrite est activé ✓' : ($rewriteEnabled === 'inconnu' ? 'Impossible de vérifier mod_rewrite (peut être normal)' : 'mod_rewrite n\'est pas activé ✗')
    ];
    
    // Test 7: .htaccess
    $htaccessExists = file_exists(__DIR__ . '/.htaccess');
    $tests[] = [
        'name' => 'Fichier .htaccess',
        'status' => $htaccessExists ? 'success' : 'warning',
        'message' => $htaccessExists ? 'Le fichier .htaccess existe ✓' : 'Le fichier .htaccess n\'existe pas (optionnel)'
    ];
    
    // Afficher les résultats
    foreach ($tests as $test) {
        echo '<div class="test ' . $test['status'] . '">';
        echo '<strong>' . htmlspecialchars($test['name']) . '</strong><br>';
        echo htmlspecialchars($test['message']);
        echo '</div>';
    }
    
    // Résumé
    $successCount = count(array_filter($tests, fn($t) => $t['status'] === 'success'));
    $errorCount = count(array_filter($tests, fn($t) => $t['status'] === 'error'));
    ?>
    
    <div class="test <?= $errorCount === 0 ? 'success' : 'error' ?>">
        <strong>Résumé</strong><br>
        <?= $successCount ?> test(s) réussi(s), <?= $errorCount ?> erreur(s)
        <?php if ($errorCount === 0): ?>
            <br><br>✅ Tous les tests sont passés ! Le CMS devrait fonctionner correctement.
            <br><a href="index.php">→ Accéder au site</a> | <a href="admin/">→ Accéder à l'administration</a>
        <?php else: ?>
            <br><br>⚠️ Certains tests ont échoué. Vérifiez les erreurs ci-dessus.
        <?php endif; ?>
    </div>
    
    <div class="test">
        <strong>Informations système</strong><br>
        Serveur: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu' ?><br>
        Document Root: <code><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Inconnu' ?></code><br>
        Script Path: <code><?= __DIR__ ?></code><br>
        URL actuelle: <code><?= $_SERVER['REQUEST_URI'] ?? 'Inconnu' ?></code>
    </div>
</body>
</html>
