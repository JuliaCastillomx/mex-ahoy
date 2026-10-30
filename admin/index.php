<?php
/**
 * Mex-Ahoy! - Panel Editorial Senior-Friendly
 * Diseñado especialmente para Don Ricardo Castillo y el equipo de redacción.
 */
session_start();

// --- CONFIGURACIÓN DE SEGURIDAD ---
// Contraseña sencilla para el editor (puede cambiarse aquí)
define('ADMIN_PASSWORD', 'mexahoy2026');

$dataFile = __DIR__ . '/../data/articles.json';
$jsDataFile = __DIR__ . '/../js/articles-data.js';
$uploadDir = __DIR__ . '/../assets/images/';
$backupDir = __DIR__ . '/../data/backups/';

// Crear carpetas si no existen
if (!file_exists(dirname($dataFile))) mkdir(dirname($dataFile), 0777, true);
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
if (!file_exists($backupDir)) mkdir($backupDir, 0777, true);

// Manejo de Cierre de Sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Manejo de Inicio de Sesión
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_pass'])) {
    if ($_POST['login_pass'] === ADMIN_PASSWORD) {
        $_SESSION['mex_admin_logged'] = true;
        header("Location: index.php");
        exit;
    } else {
        $loginError = 'La contraseña ingresada no es correcta. Por favor intente nuevamente.';
    }
}

// Verificar si está autenticado
$isLogged = isset($_SESSION['mex_admin_logged']) && $_SESSION['mex_admin_logged'] === true;

// Si no está autenticado, mostrar pantalla de bienvenida simple y grande
if (!$isLogged) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso Redacción • Mex-Ahoy!</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Georgia, sans-serif;
      background-color: #f7f3e8;
      color: #1a221e;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
      box-sizing: border-box;
    }
    .login-box {
      background: #ffffff;
      border: 2px solid #084c37;
      border-radius: 8px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
      max-width: 480px;
      width: 100%;
      padding: 36px 32px;
      text-align: center;
    }
    .login-logo {
      max-height: 70px;
      margin-bottom: 12px;
    }
    h1 {
      font-family: Georgia, serif;
      font-size: 26px;
      color: #084c37;
      margin: 0 0 8px 0;
    }
    p {
      font-size: 17px;
      color: #555;
      margin: 0 0 24px 0;
      line-height: 1.4;
    }
    .form-group {
      text-align: left;
      margin-bottom: 20px;
    }
    label {
      display: block;
      font-weight: bold;
      font-size: 17px;
      margin-bottom: 8px;
      color: #222;
    }
    input[type="password"] {
      width: 100%;
      padding: 14px 16px;
      font-size: 19px;
      border: 2px solid #ccc;
      border-radius: 6px;
      box-sizing: border-box;
      outline: none;
    }
    input[type="password"]:focus {
      border-color: #084c37;
    }
    .btn-submit {
      width: 100%;
      background-color: #084c37;
      color: #ffffff;
      border: none;
      padding: 16px;
      font-size: 20px;
      font-weight: bold;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-submit:hover {
      background-color: #0d6b4f;
    }
    .error-msg {
      background-color: #ffebee;
      color: #c62828;
      border: 1px solid #ef9a9a;
      padding: 12px;
      border-radius: 6px;
      font-size: 16px;
      margin-bottom: 18px;
      font-weight: 500;
    }
    .hint-pwd {
      margin-top: 16px;
      font-size: 14px;
      color: #777;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <img src="../assets/images/mex_ahoy_logo_horizontal.png" alt="Mex-Ahoy!" class="login-logo" onerror="this.style.display='none'">
    <h1>Panel Editorial de Mex-Ahoy!</h1>
    <p>Bienvenido Don Ricardo. Por favor ingrese su contraseña de redactor para continuar.</p>

    <?php if ($loginError): ?>
      <div class="error-msg"><?php echo htmlspecialchars($loginError); ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php">
      <div class="form-group">
        <label for="login_pass">Contraseña:</label>
        <input type="password" id="login_pass" name="login_pass" required autofocus placeholder="Escriba su contraseña...">
      </div>
      <button type="submit" class="btn-submit">Entrar a la Redacción ➔</button>
      <div class="hint-pwd">Contraseña predeterminada: <strong>mexahoy2026</strong></div>
    </form>
  </div>
</body>
</html>
<?php
    exit;
}

// --- CARGAR DATOS DE ARTÍCULOS ---
$articlesData = [];
if (file_exists($dataFile)) {
    $jsonRaw = file_get_contents($dataFile);
    $articlesData = json_decode($jsonRaw, true);
}

if (!isset($articlesData['articles'])) {
    $articlesData['articles'] = [];
}

// --- PROCESAR GUARDADO DE ARTÍCULO ---
$msgSuccess = '';
$msgError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_article') {
    $id = trim($_POST['article_id']);
    if (empty($id)) {
        // Generar un ID limpio basado en el título
        $id = strtolower(trim($_POST['title']));
        $id = preg_replace('/[^a-z0-9]+/i', '-', $id);
        $id = trim($id, '-');
        if (empty($id)) $id = 'noticia-' . time();
    }

    // Manejar subida de foto
    $imagePath = isset($_POST['current_image']) ? $_POST['current_image'] : 'assets/images/hero_palacio.jpg';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $newFileName = 'foto_' . time() . '.' . $ext;
            $dest = $uploadDir . $newFileName;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $imagePath = 'assets/images/' . $newFileName;
            }
        }
    }

    // Crear copia de respaldo automática antes de guardar
    if (file_exists($dataFile)) {
        copy($dataFile, $backupDir . 'articles_backup_' . date('Y-m-d_H-i-s') . '.json');
    }

    // Armar el artículo
    $categoryKey = $_POST['category'];
    $categoryMap = [
        'POLITICS' => 'politics',
        'CULTURE & TRADITIONS' => 'culture',
        'LIVING IN MEXICO' => 'living',
        'TRAVEL' => 'travel',
        'FOOD & DRINK' => 'food',
        'OPINION' => 'opinion',
        'EXPAT LIFE' => 'expat',
        'HISTORY' => 'history',
        "THE EDITOR'S DESK" => 'editorial'
    ];
    $categoryClass = isset($categoryMap[$categoryKey]) ? $categoryMap[$categoryKey] : 'politics';

    $articlePayload = [
        'id' => $id,
        'category' => $categoryKey,
        'categoryClass' => $categoryClass,
        'tag' => trim($_POST['tag']),
        'pillar' => trim($_POST['pillar']),
        'title' => trim($_POST['title']),
        'subtitle' => trim($_POST['subtitle']),
        'author' => trim($_POST['author']),
        'authorRole' => trim($_POST['author_role']),
        'date' => trim($_POST['date']),
        'issue' => trim($_POST['issue']),
        'readTime' => trim($_POST['read_time']),
        'image' => $imagePath,
        'imageCaption' => trim($_POST['image_caption']),
        'lead' => trim($_POST['lead']),
        'body' => trim($_POST['body']),
        'relatedIds' => isset($articlesData['articles'][$id]['relatedIds']) ? $articlesData['articles'][$id]['relatedIds'] : []
    ];

    // Si es una noticia nueva o se marcó como portada principal, ponerla al inicio
    if (isset($_POST['is_hero']) && $_POST['is_hero'] === '1') {
        // Mover al principio del array
        $newArticles = [$id => $articlePayload];
        foreach ($articlesData['articles'] as $k => $v) {
            if ($k !== $id) $newArticles[$k] = $v;
        }
        $articlesData['articles'] = $newArticles;
    } else {
        $articlesData['articles'][$id] = $articlePayload;
    }

    // Guardar JSON
    file_put_contents($dataFile, json_encode($articlesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // También actualizar js/articles-data.js para mantener sincronización 100%
    $jsContent = "// Mex-Ahoy! Editorial Database (Auto-generado desde el Panel Editorial)\n";
    $jsContent .= "const ARTICLES = " . json_encode($articlesData['articles'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";\n\n";
    if (isset($articlesData['categories'])) {
        $jsContent .= "const CATEGORY_METADATA = " . json_encode($articlesData['categories'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";\n\n";
    }
    if (isset($articlesData['pastEditions'])) {
        $jsContent .= "const PAST_EDITIONS = " . json_encode($articlesData['pastEditions'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";\n\n";
    }
    if (isset($articlesData['regions'])) {
        $jsContent .= "const MEXICO_REGIONS = " . json_encode($articlesData['regions'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";\n";
    }
    file_put_contents($jsDataFile, $jsContent);

    $savedArticleJson = json_encode($articlePayload, JSON_UNESCAPED_UNICODE);
    $savedAsHero = (isset($_POST['is_hero']) && $_POST['is_hero'] === '1') ? 'true' : 'false';
    $msgSuccess = '¡Excelente, Don Ricardo! La noticia se guardó y publicó correctamente en el periódico y en Firebase.';
}

// Acción: Eliminar artículo
$deletedArticleId = '';
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = $_GET['id'];
    if (isset($articlesData['articles'][$delId])) {
        // Respaldo
        copy($dataFile, $backupDir . 'articles_backup_' . date('Y-m-d_H-i-s') . '.json');
        unset($articlesData['articles'][$delId]);
        file_put_contents($dataFile, json_encode($articlesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $deletedArticleId = $delId;
        $msgSuccess = 'La noticia ha sido retirada del periódico.';
    }
}

// Cargar suscriptores locales de respaldo (si existen)
$localSubsFile = __DIR__ . '/../data/subscribers.json';
$localSubscribers = [];
if (file_exists($localSubsFile)) {
    $decSubs = json_decode(file_get_contents($localSubsFile), true);
    if (is_array($decSubs)) $localSubscribers = array_values($decSubs);
}

// Determinar modo de vista (Lista, Suscriptores o Editor)
$viewMode = 'list';
if (isset($_GET['edit'])) {
    $viewMode = 'edit';
} elseif (isset($_GET['tab']) && $_GET['tab'] === 'subscribers') {
    $viewMode = 'subscribers';
}
$editingArticle = null;
if ($viewMode === 'edit') {
    $editId = $_GET['edit'];
    if ($editId === 'new') {
        $editingArticle = [
            'id' => '',
            'category' => 'POLITICS',
            'tag' => 'News Analysis',
            'pillar' => 'The Real Mexico',
            'title' => '',
            'subtitle' => '',
            'author' => 'Ricardo Castillo',
            'authorRole' => 'Senior Political Analyst & Editor-in-Chief',
            'date' => date('F j, Y'),
            'issue' => 'Vol. 1 • No. 01',
            'readTime' => '5 min read',
            'image' => 'assets/images/hero_palacio.jpg',
            'imageCaption' => '',
            'lead' => '',
            'body' => '<p class="drop-cap">Escriba aquí el primer párrafo de su reportaje...</p>'
        ];
    } elseif (isset($articlesData['articles'][$editId])) {
        $editingArticle = $articlesData['articles'][$editId];
    } else {
        $viewMode = 'list';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Redacción • Mex-Ahoy!</title>
  <style>
    /* Estilos diseñados con alta legibilidad, letras grandes y alto contraste */
    :root {
      --color-green: #084c37;
      --color-green-light: #e5f1ec;
      --color-red: #c52228;
      --color-gold: #c39637;
      --bg: #faf6ee;
      --text: #19201c;
      --border: #d4ccba;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Georgia, sans-serif;
      background-color: var(--bg);
      color: var(--text);
      margin: 0;
      padding: 0;
      line-height: 1.6;
      font-size: 18px;
    }
    .header-bar {
      background-color: var(--color-green);
      color: #ffffff;
      padding: 14px 28px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .header-brand {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .header-title {
      font-family: Georgia, serif;
      font-size: 24px;
      font-weight: bold;
      letter-spacing: 0.5px;
    }
    .header-subtitle {
      font-size: 15px;
      color: #ffd88a;
      margin-top: 2px;
    }
    .header-actions {
      display: flex;
      gap: 12px;
    }
    .btn-header {
      background-color: rgba(255,255,255,0.18);
      color: #fff;
      border: 1px solid rgba(255,255,255,0.3);
      padding: 8px 16px;
      font-size: 16px;
      font-weight: bold;
      border-radius: 4px;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn-header:hover { background-color: rgba(255,255,255,0.3); }
    .btn-header.gold {
      background-color: var(--color-gold);
      color: #1a201c;
      border: none;
    }

    .container {
      max-width: 1080px;
      margin: 28px auto 60px;
      padding: 0 20px;
    }

    .alert-success {
      background-color: #e8f5e9;
      color: #1b5e20;
      border: 2px solid #81c784;
      padding: 18px 24px;
      border-radius: 8px;
      font-size: 19px;
      font-weight: bold;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* VISTA DE LISTA (DASHBOARD) */
    .dashboard-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      border-bottom: 2px solid var(--border);
      padding-bottom: 16px;
    }
    .dashboard-title {
      font-family: Georgia, serif;
      font-size: 30px;
      color: var(--color-green);
      margin: 0;
    }
    .btn-new-article {
      background-color: var(--color-green);
      color: #ffffff;
      padding: 14px 24px;
      font-size: 19px;
      font-weight: bold;
      border-radius: 6px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }
    .btn-new-article:hover { background-color: #0d6b4f; }

    .articles-table {
      width: 100%;
      border-collapse: collapse;
      background: #ffffff;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid var(--border);
      box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }
    .articles-table th {
      background-color: #f1ebd9;
      color: #333;
      padding: 16px;
      text-align: left;
      font-size: 17px;
      border-bottom: 2px solid var(--border);
    }
    .articles-table td {
      padding: 18px 16px;
      border-bottom: 1px solid #eee;
      vertical-align: middle;
      font-size: 17px;
    }
    .articles-table tr:hover { background-color: #faf7f0; }

    .badge-hero {
      background-color: #ffebee;
      color: var(--color-red);
      font-weight: bold;
      font-size: 13px;
      padding: 3px 8px;
      border-radius: 4px;
      display: inline-block;
      margin-bottom: 4px;
      text-transform: uppercase;
    }
    .article-row-title {
      font-family: Georgia, serif;
      font-size: 20px;
      font-weight: bold;
      color: #1a201c;
      margin: 0 0 4px 0;
    }
    .article-row-sub {
      font-size: 15px;
      color: #666;
      margin: 0;
    }

    .btn-action-edit {
      background-color: var(--color-green-light);
      color: var(--color-green);
      border: 1px solid var(--color-green);
      padding: 8px 16px;
      border-radius: 4px;
      font-size: 16px;
      font-weight: bold;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .btn-action-edit:hover { background-color: var(--color-green); color: #fff; }

    .btn-action-del {
      color: #c62828;
      font-size: 15px;
      text-decoration: underline;
      margin-left: 12px;
      cursor: pointer;
    }

    /* VISTA DE EDICIÓN */
    .editor-card {
      background: #ffffff;
      border: 2px solid var(--border);
      border-radius: 8px;
      padding: 32px 36px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .editor-header {
      border-bottom: 2px solid var(--border);
      padding-bottom: 18px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .editor-header h2 {
      font-family: Georgia, serif;
      font-size: 28px;
      color: var(--color-green);
      margin: 0;
    }
    .form-row {
      margin-bottom: 24px;
    }
    .form-row label {
      display: block;
      font-weight: bold;
      font-size: 18px;
      margin-bottom: 8px;
      color: #1a221e;
    }
    .form-hint {
      font-size: 15px;
      color: #666;
      margin-top: 4px;
      display: block;
    }
    input[type="text"], select, textarea {
      width: 100%;
      padding: 14px 16px;
      font-size: 18px;
      border: 2px solid #ccc;
      border-radius: 6px;
      box-sizing: border-box;
      font-family: inherit;
    }
    input[type="text"]:focus, select:focus, textarea:focus {
      border-color: var(--color-green);
      outline: none;
    }
    .input-title {
      font-family: Georgia, serif;
      font-size: 24px !important;
      font-weight: bold;
    }
    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    /* Barra de herramientas para el contenido */
    .editor-toolbar {
      background: #f1ebd9;
      border: 1px solid #ccc;
      border-bottom: none;
      padding: 8px 12px;
      display: flex;
      gap: 8px;
      border-top-left-radius: 6px;
      border-top-right-radius: 6px;
    }
    .tool-btn {
      background: #ffffff;
      border: 1px solid #aaa;
      padding: 6px 12px;
      font-size: 15px;
      font-weight: bold;
      border-radius: 4px;
      cursor: pointer;
    }
    .tool-btn:hover { background: #e0d8c3; }
    .textarea-content {
      border-top-left-radius: 0 !important;
      border-top-right-radius: 0 !important;
      min-height: 280px;
      font-family: Georgia, serif;
      line-height: 1.6;
    }

    /* Imagen actual */
    .current-photo-preview {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-top: 10px;
      padding: 10px;
      background: #faf7ee;
      border: 1px solid var(--border);
      border-radius: 6px;
    }
    .current-photo-img {
      width: 120px;
      height: 80px;
      object-fit: cover;
      border-radius: 4px;
      border: 1px solid #ccc;
    }

    .btn-save-big {
      background-color: #084c37;
      color: #ffffff;
      padding: 18px 36px;
      font-size: 22px;
      font-weight: bold;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(8, 76, 55, 0.3);
      transition: background 0.2s;
    }
    .btn-save-big:hover { background-color: #0d6b4f; }
    .btn-cancel {
      font-size: 18px;
      color: #666;
      text-decoration: underline;
      margin-left: 20px;
      cursor: pointer;
    }
  </style>
</head>
<body>

  <!-- Barra Superior -->
  <header class="header-bar">
    <div class="header-brand">
      <div>
        <div class="header-title">Mex-Ahoy! • Redacción Editorial</div>
        <div class="header-subtitle">Panel de Control para Don Ricardo Castillo (Conectado a Firebase)</div>
      </div>
    </div>
    <div class="header-actions">
      <a href="index.php" class="btn-header" style="<?php echo ($viewMode !== 'subscribers') ? 'background:rgba(255,255,255,0.32);' : ''; ?>">📰 Noticias</a>
      <a href="index.php?tab=subscribers" class="btn-header" style="<?php echo ($viewMode === 'subscribers') ? 'background:rgba(255,255,255,0.32);' : ''; ?>">📬 Suscriptores</a>
      <a href="../index.html" target="_blank" class="btn-header gold">👀 Ver Periódico en Vivo ↗</a>
      <a href="index.php?logout=1" class="btn-header">Cerrar Sesión</a>
    </div>
  </header>

  <script src="../js/firebase-config.js"></script>
  <?php if (!empty($savedArticleJson)): ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (typeof window.syncArticleToFirestore === 'function') {
        window.syncArticleToFirestore(<?php echo $savedArticleJson; ?>, <?php echo $savedAsHero; ?>);
      }
    });
  </script>
  <?php endif; ?>
  <?php if (!empty($deletedArticleId)): ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (typeof window.deleteArticleFromFirestore === 'function') {
        window.deleteArticleFromFirestore(<?php echo json_encode($deletedArticleId); ?>);
      }
    });
  </script>
  <?php endif; ?>

  <div class="container">

    <?php if ($msgSuccess): ?>
      <div class="alert-success">
        <span>✅ <?php echo htmlspecialchars($msgSuccess); ?></span>
        <a href="../index.html" target="_blank" style="color:#1b5e20; text-decoration:underline;">Ver cómo quedó en el periódico ➔</a>
      </div>
    <?php endif; ?>

    <?php if ($viewMode === 'list'): ?>
      <!-- ========================================================
           VISTA: LISTA DE ARTÍCULOS (DASHBOARD)
           ======================================================== -->
      <div class="dashboard-top">
        <div>
          <h1 class="dashboard-title">Noticias y Reportajes Publicados</h1>
          <p style="font-size:16px; color:#555; margin:4px 0 0 0;">Haga clic en <strong>"Editar"</strong> junto a cualquier noticia para modificar su titular, texto o foto.</p>
        </div>
        <a href="index.php?edit=new" class="btn-new-article">➕ Redactar Nueva Noticia</a>
      </div>

      <table class="articles-table">
        <thead>
          <tr>
            <th style="width: 140px;">Sección</th>
            <th>Titular y Detalles</th>
            <th style="width: 160px;">Autor</th>
            <th style="width: 180px; text-align: center;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $first = true;
          foreach ($articlesData['articles'] as $artKey => $art): 
          ?>
            <tr>
              <td>
                <?php if ($first): ?>
                  <span class="badge-hero">⭐ PORTADA</span><br>
                <?php endif; ?>
                <strong style="color:var(--color-green); font-size:15px;"><?php echo htmlspecialchars($art['category']); ?></strong>
              </td>
              <td>
                <div class="article-row-title"><?php echo htmlspecialchars($art['title']); ?></div>
                <div class="article-row-sub"><?php echo htmlspecialchars(substr($art['subtitle'], 0, 95)) . '...'; ?></div>
              </td>
              <td>
                <?php echo htmlspecialchars($art['author']); ?><br>
                <span style="font-size:13px; color:#888;"><?php echo htmlspecialchars($art['date']); ?></span>
              </td>
              <td style="text-align: center;">
                <a href="index.php?edit=<?php echo urlencode($artKey); ?>" class="btn-action-edit">✏️ Editar</a>
                <?php if (!$first): ?>
                  <a href="index.php?action=delete&id=<?php echo urlencode($artKey); ?>" class="btn-action-del" onclick="return confirm('¿Está seguro de querer retirar esta noticia del periódico?');">Eliminar</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php 
            $first = false;
          endforeach; 
          ?>
        </tbody>
      </table>

    <?php elseif ($viewMode === 'subscribers'): ?>
      <!-- ========================================================
           VISTA: SUSCRIPTORES DEL BOLETÍN (FIREBASE FIRESTORE)
           ======================================================== -->
      <div class="dashboard-top">
        <div>
          <h1 class="dashboard-title">📬 Suscriptores del Boletín (Firebase)</h1>
          <p style="font-size:16px; color:#555; margin:4px 0 0 0;">
            Lista en tiempo real sincronizada con <strong>Google Firebase (mex-ahoy)</strong>. Total registrados: <strong id="subs-count-badge">Cargando...</strong>
          </p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <button type="button" onclick="copyAllSubscribers()" class="btn-action-edit" style="padding:12px 18px; font-size:16px; cursor:pointer;">📋 Copiar Correos</button>
          <button type="button" onclick="exportSubscribersCSV()" class="btn-new-article" style="padding:12px 20px; font-size:16px; border:none; cursor:pointer;">📥 Descargar Excel (CSV)</button>
        </div>
      </div>

      <div style="background:#fff; border:1px solid var(--border); border-radius:8px; padding:18px 20px; margin-bottom:20px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <strong style="font-size:16px; color:var(--color-green);">➕ Agregar suscriptor manualmente:</strong>
        <input type="email" id="manual-sub-email" placeholder="correo@ejemplo.com" style="max-width:320px; padding:10px 14px; font-size:16px; border:2px solid #ccc; border-radius:6px;">
        <button type="button" onclick="addManualSubscriber()" class="btn-action-edit" style="padding:10px 18px; cursor:pointer;">Guardar en Firebase</button>
        <span id="manual-sub-status" style="font-size:15px; color:#1b5e20; font-weight:bold;"></span>
      </div>

      <table class="articles-table">
        <thead>
          <tr>
            <th>Correo Electrónico</th>
            <th style="width: 220px;">Fecha de Registro</th>
            <th style="width: 180px;">Origen</th>
            <th style="width: 140px; text-align: center;">Estado</th>
          </tr>
        </thead>
        <tbody id="subscribers-table-body">
          <tr>
            <td colspan="4" style="text-align:center; padding:32px; color:#666;">Conectando con Firebase Firestore...</td>
          </tr>
        </tbody>
      </table>

      <script>
        const LOCAL_SUBSCRIBERS = <?php echo json_encode($localSubscribers, JSON_UNESCAPED_UNICODE); ?>;
        let ALL_SUBSCRIBERS = [];

        async function loadAllSubscribers() {
          const tbody = document.getElementById('subscribers-table-body');
          const badge = document.getElementById('subs-count-badge');
          const mergedMap = {};

          // 1. Local fallback subscribers
          for (const sub of LOCAL_SUBSCRIBERS) {
            if (sub && sub.email) {
              mergedMap[sub.email.toLowerCase()] = sub;
            }
          }

          // 2. Live Firebase Firestore subscribers
          if (typeof window.fetchFirestoreSubscribers === 'function') {
            const cloudSubs = await window.fetchFirestoreSubscribers();
            for (const sub of cloudSubs) {
              if (sub && sub.email) {
                mergedMap[sub.email.toLowerCase()] = sub;
              }
            }
          }

          ALL_SUBSCRIBERS = Object.values(mergedMap).sort((a, b) =>
            String(b.subscribedAt || '').localeCompare(String(a.subscribedAt || ''))
          );

          if (badge) badge.textContent = ALL_SUBSCRIBERS.length + ' suscriptores';

          if (ALL_SUBSCRIBERS.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:32px; color:#666;">Aún no hay suscriptores registrados. Cuando alguien se suscriba en mex-ahoy.com aparecerá aquí al instante.</td></tr>';
            return;
          }

          tbody.innerHTML = ALL_SUBSCRIBERS.map(sub => {
            const dateStr = sub.subscribedAt ? new Date(sub.subscribedAt).toLocaleString('es-MX') : 'Reciente';
            return `
              <tr>
                <td><strong style="font-size:18px; color:#1a201c;">${sub.email}</strong></td>
                <td>${dateStr}</td>
                <td><span style="font-size:14px; color:#555;">${sub.source || 'frontpage_newsletter'}</span></td>
                <td style="text-align:center;"><span class="badge-hero" style="background:#e8f5e9; color:#1b5e20;">ACTIVO</span></td>
              </tr>
            `;
          }).join('');
        }

        async function addManualSubscriber() {
          const input = document.getElementById('manual-sub-email');
          const status = document.getElementById('manual-sub-status');
          if (!input || !input.value.trim()) return;
          try {
            status.textContent = 'Guardando...';
            await window.subscribeToMexAhoy(input.value.trim(), 'panel_editorial');
            input.value = '';
            status.textContent = '¡Guardado en Firebase!';
            await loadAllSubscribers();
            setTimeout(() => { status.textContent = ''; }, 3000);
          } catch (e) {
            alert(e.message || 'Correo inválido');
            status.textContent = '';
          }
        }

        function copyAllSubscribers() {
          if (ALL_SUBSCRIBERS.length === 0) {
            alert('Aún no hay correos para copiar.');
            return;
          }
          const emails = ALL_SUBSCRIBERS.map(s => s.email).join(', ');
          navigator.clipboard.writeText(emails).then(() => {
            alert('¡Se copiaron ' + ALL_SUBSCRIBERS.length + ' correos al portapapeles!');
          });
        }

        function exportSubscribersCSV() {
          if (ALL_SUBSCRIBERS.length === 0) {
            alert('Aún no hay suscriptores para exportar.');
            return;
          }
          const rows = [['Email', 'Fecha de Suscripcion', 'Origen', 'Estado']];
          ALL_SUBSCRIBERS.forEach(s => {
            rows.push([s.email || '', s.subscribedAt || '', s.source || 'web', s.status || 'active']);
          });
          const csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n');
          const link = document.createElement('a');
          link.setAttribute('href', encodeURI(csvContent));
          link.setAttribute('download', 'suscriptores_mex_ahoy_' + new Date().toISOString().slice(0,10) + '.csv');
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        }

        document.addEventListener('DOMContentLoaded', loadAllSubscribers);
      </script>

    <?php else: ?>
      <!-- ========================================================
           VISTA: EDITOR DE ARTÍCULO
           ======================================================== -->
      <div class="editor-card">
        <div class="editor-header">
          <h2><?php echo empty($editingArticle['id']) ? 'Redactar Nueva Noticia' : 'Modificar Noticia: ' . htmlspecialchars($editingArticle['title']); ?></h2>
          <a href="index.php" style="font-size:16px; color:#666;">&larr; Volver al listado</a>
        </div>

        <form method="POST" action="index.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save_article">
          <input type="hidden" name="article_id" value="<?php echo htmlspecialchars($editingArticle['id']); ?>">
          <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($editingArticle['image']); ?>">

          <div class="form-row">
            <label for="title">Titular Principal:</label>
            <input type="text" id="title" name="title" required class="input-title" value="<?php echo htmlspecialchars($editingArticle['title']); ?>" placeholder="Escriba aquí el titular de la noticia...">
            <span class="form-hint">Este es el título grande que verán los lectores en portada.</span>
          </div>

          <div class="form-row">
            <label for="subtitle">Bajada / Subtítulo explicativo:</label>
            <textarea id="subtitle" name="subtitle" rows="2" placeholder="Resumen breve o subtítulo de la nota..."><?php echo htmlspecialchars($editingArticle['subtitle']); ?></textarea>
          </div>

          <div class="grid-2">
            <div class="form-row">
              <label for="author">Autor de la nota:</label>
              <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($editingArticle['author']); ?>">
            </div>
            <div class="form-row">
              <label for="author_role">Cargo o rol del autor:</label>
              <input type="text" id="author_role" name="author_role" value="<?php echo htmlspecialchars($editingArticle['authorRole']); ?>">
            </div>
          </div>

          <div class="grid-2">
            <div class="form-row">
              <label for="category">Sección del periódico:</label>
              <select id="category" name="category">
                <?php
                $cats = [
                    'POLITICS' => 'POLITICS (Política Nacional e Internacional)',
                    "THE EDITOR'S DESK" => "THE EDITOR'S DESK (Columna Editorial)",
                    'CULTURE & TRADITIONS' => 'CULTURE (Cultura y Tradiciones)',
                    'LIVING IN MEXICO' => 'LIVING IN MEXICO (Vivir en México / Guías)',
                    'TRAVEL' => 'TRAVEL (Viajes y Expediciones)',
                    'FOOD & DRINK' => 'FOOD & DRINK (Gastronomía y Agaves)',
                    'OPINION' => 'OPINION (Columnas de Opinión)',
                    'EXPAT LIFE' => 'EXPAT LIFE (Vida de Expatriados y Leyes)',
                    'HISTORY' => 'HISTORY (Crónicas Históricas)'
                ];
                foreach ($cats as $cKey => $cLabel) {
                    $selected = ($editingArticle['category'] === $cKey) ? 'selected' : '';
                    echo "<option value=\"$cKey\" $selected>$cLabel</option>";
                }
                ?>
              </select>
            </div>

            <div class="form-row">
              <label for="date">Fecha de publicación:</label>
              <input type="text" id="date" name="date" value="<?php echo htmlspecialchars($editingArticle['date']); ?>">
            </div>
          </div>

          <div class="grid-2">
            <div class="form-row">
              <label for="tag">Etiqueta de cabecera (Eyebrow):</label>
              <input type="text" id="tag" name="tag" value="<?php echo htmlspecialchars($editingArticle['tag']); ?>" placeholder="Ej: News Analysis • Diplomatic Affairs">
            </div>
            <div class="form-row">
              <label for="pillar">Pilar Editorial:</label>
              <select id="pillar" name="pillar">
                <option value="The Real Mexico" <?php if ($editingArticle['pillar'] === 'The Real Mexico') echo 'selected'; ?>>The Real Mexico</option>
                <option value="People" <?php if ($editingArticle['pillar'] === 'People') echo 'selected'; ?>>People</option>
                <option value="Places" <?php if ($editingArticle['pillar'] === 'Places') echo 'selected'; ?>>Places</option>
                <option value="Culture" <?php if ($editingArticle['pillar'] === 'Culture') echo 'selected'; ?>>Culture</option>
              </select>
            </div>
          </div>

          <!-- Selección de Fotografía -->
          <div class="form-row">
            <label for="photo">Fotografía de la noticia:</label>
            <input type="file" id="photo" name="photo" accept="image/*">
            <span class="form-hint">Seleccione una imagen de su computadora (JPG o PNG). Si no elige ninguna, se conservará la foto actual.</span>
            <?php if (!empty($editingArticle['image'])): ?>
              <div class="current-photo-preview">
                <img src="../<?php echo htmlspecialchars($editingArticle['image']); ?>" alt="Foto actual" class="current-photo-img" onerror="this.src='../assets/images/hero_palacio.jpg'">
                <div>
                  <strong>Foto actual:</strong> <?php echo htmlspecialchars($editingArticle['image']); ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <div class="form-row">
            <label for="image_caption">Pie de foto / Crédito fotográfico:</label>
            <input type="text" id="image_caption" name="image_caption" value="<?php echo htmlspecialchars($editingArticle['imageCaption']); ?>" placeholder="Descripción de la imagen y fotógrafo...">
          </div>

          <!-- Texto de la Noticia -->
          <div class="form-row">
            <label for="body">Texto completo de la noticia:</label>
            <div class="editor-toolbar">
              <button type="button" class="tool-btn" onclick="insertTag('strong')"><strong>Negrita (B)</strong></button>
              <button type="button" class="tool-btn" onclick="insertTag('em')"><em>Cursiva (I)</em></button>
              <button type="button" class="tool-btn" onclick="insertBlock('h3')">Subtítulo</button>
              <button type="button" class="tool-btn" onclick="insertBlock('p')">Nuevo Párrafo</button>
              <button type="button" class="tool-btn" onclick="insertQuote()">Cita Destacada</button>
            </div>
            <textarea id="body" name="body" class="textarea-content" required rows="14"><?php echo htmlspecialchars($editingArticle['body']); ?></textarea>
            <span class="form-hint">Puede escribir los párrafos con naturalidad. Para añadir subtítulos o negritas use los botones superiores.</span>
          </div>

          <div class="form-row" style="background:#f9f6ef; padding:16px; border-radius:6px; border:1px solid #e0d8c3;">
            <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
              <input type="checkbox" name="is_hero" value="1" style="width:22px; height:22px;">
              <span style="font-size:18px; font-weight:bold; color:var(--color-red);">Colocar como NOTICIA PRINCIPAL DE PORTADA (Arriba a la izquierda)</span>
            </label>
          </div>

          <!-- Botones de Acción -->
          <div style="margin-top: 32px; display:flex; align-items:center;">
            <button type="submit" class="btn-save-big">💾 GUARDAR Y PUBLICAR NOTICIA</button>
            <a href="index.php" class="btn-cancel">Cancelar y Volver</a>
          </div>
        </form>
      </div>

      <script>
        function insertTag(tag) {
          const textarea = document.getElementById('body');
          const start = textarea.selectionStart;
          const end = textarea.selectionEnd;
          const selectedText = textarea.value.substring(start, end) || 'texto';
          const replacement = `<${tag}>${selectedText}</${tag}>`;
          textarea.setRangeText(replacement, start, end, 'select');
          textarea.focus();
        }
        function insertBlock(tag) {
          const textarea = document.getElementById('body');
          const start = textarea.selectionStart;
          const end = textarea.selectionEnd;
          const selectedText = textarea.value.substring(start, end) || 'Subtítulo aquí';
          const replacement = `\n<${tag}>${selectedText}</${tag}>\n`;
          textarea.setRangeText(replacement, start, end, 'select');
          textarea.focus();
        }
        function insertQuote() {
          const textarea = document.getElementById('body');
          const start = textarea.selectionStart;
          const end = textarea.selectionEnd;
          const selectedText = textarea.value.substring(start, end) || '“Escriba aquí la cita destacada...”';
          const replacement = `\n<blockquote class="newspaper-quote">\n  <p>${selectedText}</p>\n  <cite>— Nombre de la fuente o institución</cite>\n</blockquote>\n`;
          textarea.setRangeText(replacement, start, end, 'select');
          textarea.focus();
        }
      </script>
    <?php endif; ?>

  </div>

</body>
</html>
