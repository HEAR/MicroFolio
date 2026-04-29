<?php
require_once __DIR__ . '/../includes/auth.php';
handleLogout();
requireAuth();

$message = null;
$error = null;

// Traitement de la sauvegarde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session invalide, veuillez recharger la page';
    } elseif (isset($_POST['save_css'])) {
        $css = $_POST['css'] ?? '';
        if (file_put_contents(CUSTOM_CSS_FILE, $css) !== false) {
            $message = 'CSS personnalisé sauvegardé avec succès';
        } else {
            $error = 'Erreur lors de la sauvegarde du CSS';
        }
    } elseif (isset($_POST['save_js'])) {
        $js = $_POST['js'] ?? '';
        if (file_put_contents(CUSTOM_JS_FILE, $js) !== false) {
            $message = 'JavaScript personnalisé sauvegardé avec succès';
        } else {
            $error = 'Erreur lors de la sauvegarde du JavaScript';
        }
    } elseif (isset($_POST['save_config'])) {
        $external_css = array_filter(array_map('trim', explode("\n", $_POST['external_css'] ?? '')));
        $external_js = array_filter(array_map('trim', explode("\n", $_POST['external_js'] ?? '')));
        
        if (updateConfig([
            'external_css' => array_values($external_css),
            'external_js' => array_values($external_js)
        ])) {
            $message = 'Configuration sauvegardée avec succès';
        } else {
            $error = 'Erreur lors de la sauvegarde de la configuration';
        }
    }
}

$custom_css = file_get_contents(CUSTOM_CSS_FILE);
$custom_js = file_get_contents(CUSTOM_JS_FILE);
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnalisation - MicroFolio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">MicroFolio</a>
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
                            <i class="bi bi-folder"></i> Pages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="custom.php">
                            <i class="bi bi-code-slash"></i> Personnalisation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i> Paramètres
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
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

        <div class="admin-page-header">
            <h1 class="admin-page-title">Personnalisation du site</h1>
            <a href="<?= BASE_PATH ?>/index.php" target="_blank" class="btn btn-outline-secondary">
                <i class="bi bi-eye"></i> Voir le site
            </a>
        </div>

        <ul class="nav nav-tabs mb-4" id="customTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="css-tab" data-bs-toggle="tab" data-bs-target="#css" type="button">
                    <i class="bi bi-filetype-css"></i> CSS personnalisé
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="js-tab" data-bs-toggle="tab" data-bs-target="#js" type="button">
                    <i class="bi bi-filetype-js"></i> JavaScript personnalisé
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="external-tab" data-bs-toggle="tab" data-bs-target="#external" type="button">
                    <i class="bi bi-link-45deg"></i> Librairies externes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="structure-tab" data-bs-toggle="tab" data-bs-target="#structure" type="button">
                    <i class="bi bi-diagram-3"></i> Structure HTML
                </button>
            </li>
        </ul>

        <div class="tab-content" id="customTabsContent">
            <!-- CSS personnalisé -->
            <div class="tab-pane fade show active" id="css" role="tabpanel">
                <div class="card admin-section-card">
                    <div class="card-header">
                        <h5 class="mb-0">CSS personnalisé</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="save_css" value="1">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <div class="mb-3">
                                <label class="form-label">Éditez votre CSS personnalisé</label>
                                <textarea id="cssEditor" name="css" class="form-control" rows="20"><?= htmlspecialchars($custom_css) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Sauvegarder le CSS
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- JavaScript personnalisé -->
            <div class="tab-pane fade" id="js" role="tabpanel">
                <div class="card admin-section-card">
                    <div class="card-header">
                        <h5 class="mb-0">JavaScript personnalisé</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="save_js" value="1">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <div class="mb-3">
                                <label class="form-label">Éditez votre JavaScript personnalisé</label>
                                <textarea id="jsEditor" name="js" class="form-control" rows="20"><?= htmlspecialchars($custom_js) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Sauvegarder le JavaScript
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Librairies externes -->
            <div class="tab-pane fade" id="external" role="tabpanel">
                <div class="card admin-section-card">
                    <div class="card-header">
                        <h5 class="mb-0">Librairies CSS et JavaScript externes</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="save_config" value="1">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <div class="mb-3">
                                <label for="external_css" class="form-label">URLs des librairies CSS (une par ligne)</label>
                                <textarea id="external_css" name="external_css" class="form-control" rows="5" 
                                          placeholder="https://cdn.example.com/library.css&#10;https://cdn.example.com/another.css"><?= htmlspecialchars(implode("\n", $config['external_css'] ?? [])) ?></textarea>
                                <small class="form-text text-muted">Exemple: https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css</small>
                            </div>
                            <div class="mb-3">
                                <label for="external_js" class="form-label">URLs des librairies JavaScript (une par ligne)</label>
                                <textarea id="external_js" name="external_js" class="form-control" rows="5" 
                                          placeholder="https://cdn.example.com/library.js&#10;https://cdn.example.com/another.js"><?= htmlspecialchars(implode("\n", $config['external_js'] ?? [])) ?></textarea>
                                <small class="form-text text-muted">Exemple: https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js</small>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Sauvegarder la configuration
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Structure HTML -->
            <div class="tab-pane fade" id="structure" role="tabpanel">
                <div class="card admin-section-card">
                    <div class="card-header">
                        <h5 class="mb-0">Résumé de la structure front</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Ce résumé aide un intégrateur à repérer rapidement les balises, classes et zones principales du front office.
                        </p>

                        <h6 class="mt-3">Structure globale commune</h6>
<pre class="bg-light p-3 border rounded"><code class="language-html">&lt;body&gt;
  &lt;div&gt;Bandeau admin (uniquement en maintenance + admin connecté)&lt;/div&gt;
  &lt;header class="site-header"&gt;
    &lt;div class="container"&gt;
      &lt;h1 class="site-title"&gt;...&lt;/h1&gt;
      &lt;nav class="site-nav"&gt;
        &lt;a class="nav-link"&gt;...&lt;/a&gt;
      &lt;/nav&gt;
    &lt;/div&gt;
  &lt;/header&gt;

  &lt;main class="site-main"&gt;...&lt;/main&gt;

  &lt;footer class="site-footer"&gt;
    &lt;div class="container"&gt;...&lt;/div&gt;
  &lt;/footer&gt;
&lt;/body&gt;</code></pre>

                        <h6 class="mt-4">Page d'accueil (`index.php`)</h6>
<pre class="bg-light p-3 border rounded"><code class="language-html">&lt;main class="site-main"&gt;
  (si page d'accueil définie)
  &lt;section class="rubrique-section"&gt;
    &lt;div class="container"&gt;
      &lt;h2 class="rubrique-title"&gt;...&lt;/h2&gt;
      &lt;div class="rubrique-images"&gt;
        &lt;figure class="rubrique-image-wrapper image-size-{sixth|quarter|small|medium|large|full}"&gt;
          &lt;a class="rubrique-image-link" data-group="gallery"&gt;
            &lt;img class="rubrique-image"&gt;
          &lt;/a&gt;
          &lt;figcaption class="rubrique-image-caption"&gt;...&lt;/figcaption&gt;
        &lt;/figure&gt;
      &lt;/div&gt;
      &lt;div class="rubrique-content"&gt;...&lt;/div&gt;
    &lt;/div&gt;
  &lt;/section&gt;

  (sinon grille)
  &lt;section class="rubriques-grid"&gt;
    &lt;div class="container"&gt;
      &lt;div class="rubriques-flex"&gt;
        &lt;a class="rubrique-card"&gt;
          &lt;img class="rubrique-card-image"&gt;
          &lt;div class="rubrique-card-content"&gt;
            &lt;h3 class="rubrique-card-title"&gt;...&lt;/h3&gt;
          &lt;/div&gt;
        &lt;/a&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/section&gt;
&lt;/main&gt;</code></pre>

                        <h6 class="mt-4">Page rubrique (`rubrique.php`)</h6>
<pre class="bg-light p-3 border rounded"><code class="language-html">&lt;main class="site-main"&gt;
  &lt;section class="rubrique-section"&gt;
    &lt;div class="container"&gt;
      &lt;h2 class="rubrique-title"&gt;...&lt;/h2&gt;
      &lt;div class="rubrique-images"&gt;...&lt;/div&gt;
      &lt;div class="rubrique-content"&gt;...&lt;/div&gt;
    &lt;/div&gt;
  &lt;/section&gt;
&lt;/main&gt;</code></pre>

                        <h6 class="mt-4">Classes CSS clés</h6>
                        <ul class="mb-0">
                            <li><code>.site-header</code>, <code>.site-title</code>, <code>.site-nav</code>, <code>.nav-link</code></li>
                            <li><code>.site-main</code>, <code>.rubrique-section</code>, <code>.rubrique-title</code>, <code>.rubrique-content</code></li>
                            <li><code>.rubrique-images</code>, <code>.rubrique-image-wrapper</code>, <code>.rubrique-image</code>, <code>.rubrique-image-caption</code></li>
                            <li><code>.rubriques-grid</code>, <code>.rubriques-flex</code>, <code>.rubrique-card</code>, <code>.rubrique-card-image</code>, <code>.rubrique-card-title</code></li>
                            <li><code>.site-footer</code>, <code>.empty-state</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>
        document.querySelectorAll('#structure pre code').forEach((block) => {
            hljs.highlightElement(block);
        });

        // Initialiser CodeMirror pour CSS
        const cssEditor = CodeMirror.fromTextArea(document.getElementById('cssEditor'), {
            lineNumbers: true,
            mode: 'css',
            theme: 'monokai',
            indentUnit: 2,
            indentWithTabs: false
        });

        // Initialiser CodeMirror pour JavaScript
        const jsEditor = CodeMirror.fromTextArea(document.getElementById('jsEditor'), {
            lineNumbers: true,
            mode: 'javascript',
            theme: 'monokai',
            indentUnit: 2,
            indentWithTabs: false
        });

        // Corriger le rendu de CodeMirror dans les onglets Bootstrap
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function() {
                cssEditor.refresh();
                jsEditor.refresh();
            });
        });
        setTimeout(() => {
            cssEditor.refresh();
            jsEditor.refresh();
        }, 0);

        // Sauvegarder le contenu de CodeMirror avant soumission
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                cssEditor.save();
                jsEditor.save();
            });
        });
    </script>
</body>
</html>

