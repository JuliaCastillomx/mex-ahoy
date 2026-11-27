<?php
/**
 * Mex-Ahoy! — Panel Editorial Senior-Friendly con Asistente de Redacción IA
 * Diseñado especialmente para Don Ricardo Castillo y el equipo de Mex-Ahoy!
 * Conectado a Firebase Cloud Firestore + Firebase AI Logic (Gemini 4 Carbón / Neón).
 */
session_start();

// --- CONFIGURACIÓN DE SEGURIDAD ---
define('ADMIN_PASSWORD', 'Paulino1942');

$dataFile   = __DIR__ . '/../data/articles.json';
$jsDataFile = __DIR__ . '/../js/articles-data.js';
$uploadDir  = __DIR__ . '/../assets/images/';
$backupDir  = __DIR__ . '/../data/backups/';

if (!file_exists(dirname($dataFile))) mkdir(dirname($dataFile), 0777, true);
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
if (!file_exists($backupDir)) mkdir($backupDir, 0777, true);

// Helper: escribir articles.json y sincronizar js/articles-data.js con respaldo automático
function persistArticlesDatabase($articlesData, $dataFile, $jsDataFile, $backupDir) {
    if (file_exists($dataFile)) {
        @copy($dataFile, $backupDir . 'articles_backup_' . date('Y-m-d_H-i-s') . '.json');
    }

    file_put_contents(
        $dataFile,
        json_encode($articlesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    $jsContent  = "// Mex-Ahoy! Editorial Database (Auto-generado desde el Panel Editorial)\n";
    $jsContent .= "const ARTICLES = " . json_encode($articlesData['articles'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n\n";
    if (isset($articlesData['categories'])) {
        $jsContent .= "const CATEGORY_METADATA = " . json_encode($articlesData['categories'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n\n";
    }
    if (isset($articlesData['pastEditions'])) {
        $jsContent .= "const PAST_EDITIONS = " . json_encode($articlesData['pastEditions'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n\n";
    }
    if (isset($articlesData['regions'])) {
        $jsContent .= "const MEXICO_REGIONS = " . json_encode($articlesData['regions'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
    }
    file_put_contents($jsDataFile, $jsContent);
}

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

$isLogged = isset($_SESSION['mex_admin_logged']) && $_SESSION['mex_admin_logged'] === true;

// Pantalla de Acceso Senior-Friendly
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
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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
      border: 3px solid #084c37;
      border-radius: 14px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.09);
      max-width: 500px;
      width: 100%;
      padding: 40px 34px;
      text-align: center;
    }
    .login-logo { max-height: 74px; margin-bottom: 14px; }
    h1 { font-family: Georgia, serif; font-size: 28px; color: #084c37; margin: 0 0 10px 0; }
    p { font-size: 18px; color: #4b5550; margin: 0 0 26px 0; line-height: 1.5; }
    .form-group { text-align: left; margin-bottom: 22px; }
    label { display: block; font-weight: bold; font-size: 18px; margin-bottom: 8px; color: #18201c; }
    input[type="password"] {
      width: 100%; padding: 16px 18px; font-size: 20px;
      border: 2px solid #bfb5a1; border-radius: 10px; box-sizing: border-box; outline: none;
    }
    input[type="password"]:focus { border-color: #084c37; }
    .btn-submit {
      width: 100%; background-color: #084c37; color: #ffffff; border: none;
      padding: 18px; font-size: 21px; font-weight: bold; border-radius: 10px;
      cursor: pointer; transition: background 0.2s;
    }
    .btn-submit:hover { background-color: #0d6b4f; }
    .error-msg {
      background-color: #fdecee; color: #b82026; border: 2px solid #f5b5b8;
      padding: 14px; border-radius: 8px; font-size: 17px; margin-bottom: 20px; font-weight: 600;
    }
    .hint-pwd { margin-top: 18px; font-size: 15px; color: #666; }
  </style>
</head>
<body>
  <div class="login-box">
    <img src="../assets/images/mex_ahoy_logo_horizontal.png" alt="Mex-Ahoy!" class="login-logo" onerror="this.style.display='none'">
    <h1>Mesa de Redacción • Mex-Ahoy!</h1>
    <p>Bienvenido, Don Ricardo. Por favor escriba su contraseña de editor para abrir sus artículos y su asistente.</p>

    <?php if ($loginError): ?>
      <div class="error-msg"><?php echo htmlspecialchars($loginError); ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php">
      <div class="form-group">
        <label for="login_pass">Contraseña:</label>
        <input type="password" id="login_pass" name="login_pass" required autofocus placeholder="Escriba su contraseña...">
        <label style="display:flex; align-items:center; gap:8px; margin-top:10px; font-weight:normal; font-size:16px; color:#4b5550; cursor:pointer;">
          <input type="checkbox" style="width:19px; height:19px;" onchange="document.getElementById('login_pass').type = this.checked ? 'text' : 'password';">
          <span>👁️ Mostrar contraseña mientras escribo</span>
        </label>
      </div>
      <button type="submit" class="btn-submit">Entrar a la Redacción ➔</button>
      <div class="hint-pwd">Acceso privado para Don Ricardo Castillo y la redacción de Mex-Ahoy!</div>
    </form>
  </div>
</body>
</html>
<?php
    exit;
}

// --- CARGAR BASE DE ARTÍCULOS ---
$articlesData = [];
if (file_exists($dataFile)) {
    $jsonRaw = file_get_contents($dataFile);
    $articlesData = json_decode($jsonRaw, true);
}
if (!is_array($articlesData)) $articlesData = [];
if (!isset($articlesData['articles']) || !is_array($articlesData['articles'])) {
    $articlesData['articles'] = [];
}

// --- ENDPOINTS AJAX (JSON) PARA ORDENAR, GUARDAR, CAMBIAR ESTADO Y ELIMINAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $act = $_POST['ajax_action'];

    // 1. Reordenar artículos (Subir / Bajar / Hacer Portada)
    if ($act === 'reorder_articles') {
        $orderedIds = json_decode($_POST['ordered_ids'] ?? '[]', true);
        if (!is_array($orderedIds)) {
            echo json_encode(['ok' => false, 'error' => 'Orden inválido']);
            exit;
        }
        $reordered = [];
        $pos = 0;
        foreach ($orderedIds as $oid) {
            if (isset($articlesData['articles'][$oid])) {
                $art = $articlesData['articles'][$oid];
                $art['sortOrder'] = $pos;
                $art['isHero'] = ($pos === 0);
                $reordered[$oid] = $art;
                $pos++;
            }
        }
        // Conservar cualquier artículo no listado al final por seguridad
        foreach ($articlesData['articles'] as $k => $v) {
            if (!isset($reordered[$k])) {
                $v['sortOrder'] = $pos;
                $v['isHero'] = ($pos === 0);
                $reordered[$k] = $v;
                $pos++;
            }
        }
        $articlesData['articles'] = $reordered;
        persistArticlesDatabase($articlesData, $dataFile, $jsDataFile, $backupDir);
        echo json_encode(['ok' => true, 'orderedIds' => array_keys($reordered)]);
        exit;
    }

    // 2. Cambiar estado editorial (pending / done)
    if ($act === 'toggle_status') {
        $artId = trim($_POST['article_id'] ?? '');
        $status = ($_POST['status'] ?? 'pending') === 'done' ? 'done' : 'pending';
        if ($artId && isset($articlesData['articles'][$artId])) {
            $articlesData['articles'][$artId]['editorialStatus'] = $status;
            persistArticlesDatabase($articlesData, $dataFile, $jsDataFile, $backupDir);
            echo json_encode(['ok' => true, 'article_id' => $artId, 'status' => $status]);
            exit;
        }
        echo json_encode(['ok' => false, 'error' => 'Artículo no encontrado']);
        exit;
    }

    // 3. Guardar o crear artículo (soporta subida de fotografía)
    if ($act === 'save_article') {
        $id = trim($_POST['article_id'] ?? '');
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            echo json_encode(['ok' => false, 'error' => 'Por favor escriba un titular antes de guardar.']);
            exit;
        }
        if ($id === '') {
            $id = strtolower($title);
            $id = preg_replace('/[^a-z0-9]+/i', '-', $id);
            $id = trim($id, '-');
            if ($id === '') $id = 'noticia-' . time();
            if (isset($articlesData['articles'][$id])) {
                $id .= '-' . time();
            }
        }

        $existing = $articlesData['articles'][$id] ?? [];
        $imagePath = trim($_POST['current_image'] ?? ($existing['image'] ?? 'assets/images/hero_palacio.jpg'));
        if ($imagePath === '') $imagePath = 'assets/images/hero_palacio.jpg';

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

        $categoryKey = trim($_POST['category'] ?? 'POLITICS');
        $categoryMap = [
            'POLITICS'             => 'politics',
            'CULTURE & TRADITIONS' => 'culture',
            'LIVING IN MEXICO'     => 'living',
            'TRAVEL'               => 'travel',
            'FOOD & DRINK'         => 'food',
            'OPINION'              => 'opinion',
            'EXPAT LIFE'           => 'expat',
            'HISTORY'              => 'history',
            "THE EDITOR'S DESK"    => 'editorial'
        ];
        $categoryClass = $categoryMap[$categoryKey] ?? 'politics';

        $bodyHtml = trim($_POST['body_html'] ?? '');
        $wordCount = str_word_count(strip_tags($bodyHtml));
        $estMinutes = max(1, (int)ceil($wordCount / 200));

        $leadText = trim($_POST['lead'] ?? '');
        if ($leadText === '') {
            $leadText = trim($_POST['subtitle'] ?? '');
        }

        $editorialStatus = ($_POST['editorial_status'] ?? ($existing['editorialStatus'] ?? 'pending')) === 'done' ? 'done' : 'pending';

        $articlePayload = [
            'id'              => $id,
            'category'        => $categoryKey,
            'categoryClass'   => $categoryClass,
            'tag'             => trim($_POST['tag'] ?? ($existing['tag'] ?? 'News Analysis')),
            'pillar'          => trim($_POST['pillar'] ?? ($existing['pillar'] ?? 'The Real Mexico')),
            'title'           => $title,
            'subtitle'        => trim($_POST['subtitle'] ?? ''),
            'author'          => trim($_POST['author'] ?? 'Ricardo Castillo'),
            'authorRole'      => trim($_POST['author_role'] ?? 'Senior Political Analyst & Editor-in-Chief'),
            'date'            => trim($_POST['date'] ?? date('F j, Y')),
            'issue'           => trim($_POST['issue'] ?? ($existing['issue'] ?? 'Vol. 1 • No. 01')),
            'readTime'        => $estMinutes . ' min read',
            'image'           => $imagePath,
            'imageCaption'    => trim($_POST['image_caption'] ?? ''),
            'lead'            => $leadText,
            'body'            => $bodyHtml,
            'editorialStatus' => $editorialStatus,
            'relatedIds'      => $existing['relatedIds'] ?? []
        ];

        $isHero = isset($_POST['is_hero']) && $_POST['is_hero'] === '1';
        if ($isHero) {
            $newArticles = [$id => $articlePayload];
            foreach ($articlesData['articles'] as $k => $v) {
                if ($k !== $id) $newArticles[$k] = $v;
            }
            $articlesData['articles'] = $newArticles;
        } else {
            $articlesData['articles'][$id] = $articlePayload;
        }

        // Recalcular sortOrder e isHero
        $pos = 0;
        foreach ($articlesData['articles'] as $k => &$artRef) {
            $artRef['sortOrder'] = $pos;
            $artRef['isHero'] = ($pos === 0);
            $pos++;
        }
        unset($artRef);

        persistArticlesDatabase($articlesData, $dataFile, $jsDataFile, $backupDir);
        echo json_encode([
            'ok'         => true,
            'article'    => $articlesData['articles'][$id],
            'isHero'     => $articlesData['articles'][$id]['isHero'],
            'orderedIds' => array_keys($articlesData['articles'])
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // 4. Eliminar artículo
    if ($act === 'delete_article') {
        $delId = trim($_POST['article_id'] ?? '');
        if ($delId && isset($articlesData['articles'][$delId])) {
            unset($articlesData['articles'][$delId]);
            $pos = 0;
            foreach ($articlesData['articles'] as $k => &$artRef) {
                $artRef['sortOrder'] = $pos;
                $artRef['isHero'] = ($pos === 0);
                $pos++;
            }
            unset($artRef);
            persistArticlesDatabase($articlesData, $dataFile, $jsDataFile, $backupDir);
            echo json_encode(['ok' => true, 'deletedId' => $delId]);
            exit;
        }
        echo json_encode(['ok' => false, 'error' => 'Artículo no encontrado']);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Acción desconocida']);
    exit;
}

// Cargar suscriptores locales de respaldo (si existen)
$localSubsFile = __DIR__ . '/../data/subscribers.json';
$localSubscribers = [];
if (file_exists($localSubsFile)) {
    $decSubs = json_decode(file_get_contents($localSubsFile), true);
    if (is_array($decSubs)) $localSubscribers = array_values($decSubs);
}

$activeTab = (isset($_GET['tab']) && $_GET['tab'] === 'subscribers') ? 'subscribers' : 'articles';
$initialArticlesList = array_values($articlesData['articles']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mesa de Redacción • Mex-Ahoy!</title>
  <style>
    :root {
      --mex-green: #084c37;
      --mex-green-hover: #0e664b;
      --mex-green-soft: #e6f2ed;
      --mex-gold: #c39637;
      --mex-gold-soft: #fbf4e2;
      --mex-red: #b82026;
      --mex-red-soft: #fdecee;
      --mex-paper: #faf6ee;
      --mex-paper-alt: #f1eadc;
      --mex-ink: #18201c;
      --mex-muted: #4b5550;
      --mex-border: #d5ccba;
      --scale: 1;
    }

    *, *::before, *::after { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 0;
      background-color: var(--mex-paper);
      color: var(--mex-ink);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      font-size: calc(17px * var(--scale));
      line-height: 1.55;
    }

    .serif { font-family: Georgia, "Times New Roman", serif; }
    .hidden { display: none !important; }

    /* HEADER SUPERIOR */
    .top-header {
      background-color: var(--mex-green);
      border-bottom: 4px solid var(--mex-gold);
      color: #ffffff;
      padding: 14px 24px;
      position: sticky;
      top: 0;
      z-index: 40;
      box-shadow: 0 3px 12px rgba(0,0,0,0.16);
    }
    .header-inner {
      max-width: 1380px;
      margin: 0 auto;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
    }
    .brand-group { display: flex; align-items: center; gap: 14px; }
    .brand-badge {
      width: 48px; height: 48px; border-radius: 10px;
      background-color: var(--mex-gold); color: #16221c;
      display: flex; align-items: center; justify-content: center;
      font-family: Georgia, serif; font-size: 26px; font-weight: bold;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      flex-shrink: 0;
    }
    .brand-title { font-family: Georgia, serif; font-size: calc(23px * var(--scale)); font-weight: bold; line-height: 1.15; }
    .brand-sub { font-size: calc(14px * var(--scale)); color: #ffe3a3; margin-top: 2px; }

    .header-controls { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .scale-box {
      display: inline-flex; align-items: center; gap: 4px;
      background: rgba(0,0,0,0.25); padding: 4px 6px; border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.28);
    }
    .scale-label { font-size: 13px; color: #ffe3a3; font-weight: 700; padding: 0 6px; }
    .scale-btn {
      border: none; background: transparent; color: #fff; font-weight: 700;
      padding: 5px 10px; border-radius: 7px; cursor: pointer;
    }
    .scale-btn.active { background: #ffffff; color: var(--mex-green); }

    .h-btn {
      background: rgba(255,255,255,0.16); color: #ffffff;
      border: 1px solid rgba(255,255,255,0.34);
      padding: 9px 14px; border-radius: 9px; font-weight: 700;
      font-size: calc(15px * var(--scale)); cursor: pointer; text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .h-btn:hover { background: rgba(255,255,255,0.28); }
    .h-btn.active { background: rgba(255,255,255,0.34); border-color: #ffe3a3; }
    .h-btn.gold { background: var(--mex-gold); color: #16221c; border: none; }
    .h-btn.gold:hover { background: #d6a642; }

    /* CONTENEDOR PRINCIPAL */
    .main-wrap {
      max-width: 1380px;
      margin: 0 auto;
      padding: 24px 20px 70px;
    }

    /* TARJETAS Y PANELES */
    .card {
      background: #ffffff;
      border: 2px solid var(--mex-border);
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 3px 12px rgba(0,0,0,0.04);
    }

    /* BOTONES GENERALES SENIOR-FRIENDLY */
    .btn {
      border: 2px solid transparent;
      border-radius: 12px;
      padding: 12px 20px;
      font-weight: 700;
      font-size: calc(16px * var(--scale));
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: transform 0.12s ease, background-color 0.15s ease, box-shadow 0.15s ease;
      text-decoration: none;
      font-family: inherit;
    }
    .btn:active { transform: scale(0.98); }
    .btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .btn-primary { background: var(--mex-green); color: #ffffff; box-shadow: 0 3px 10px rgba(8,76,55,0.22); }
    .btn-primary:hover:not(:disabled) { background: var(--mex-green-hover); }
    .btn-gold { background: var(--mex-gold-soft); color: #5c4008; border-color: var(--mex-gold); }
    .btn-gold:hover:not(:disabled) { background: #f6e7c1; }
    .btn-word { background: #e8f1fb; color: #15437d; border-color: #6b9fd8; }
    .btn-word:hover:not(:disabled) { background: #d4e5f8; }
    .btn-soft { background: var(--mex-paper); color: var(--mex-ink); border-color: var(--mex-border); }
    .btn-soft:hover:not(:disabled) { background: var(--mex-paper-alt); }
    .btn-danger-outline { background: #ffffff; color: var(--mex-red); border-color: #ebb0b3; }
    .btn-danger-outline:hover:not(:disabled) { background: var(--mex-red-soft); }
    .btn-sm { padding: 8px 13px; font-size: calc(14.5px * var(--scale)); border-radius: 10px; }
    .btn-lg { padding: 15px 24px; font-size: calc(18px * var(--scale)); }

    /* DASHBOARD SUPERIOR */
    .welcome-row {
      display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px;
    }
    .welcome-title {
      font-family: Georgia, serif; font-size: calc(28px * var(--scale)); color: var(--mex-green);
      margin: 0 0 6px 0;
    }
    .welcome-desc { margin: 0; color: var(--mex-muted); font-size: calc(17px * var(--scale)); max-width: 720px; }
    .welcome-actions { display: flex; flex-wrap: wrap; gap: 12px; }

    /* PROGRESO + FILTROS DE ESTADO */
    .progress-bar-row {
      margin-top: 22px; padding-top: 18px; border-top: 2px solid #ece5d6;
      display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;
    }
    .progress-left { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; }
    .progress-label { font-weight: 700; color: var(--mex-green); font-size: calc(17px * var(--scale)); }
    .progress-track {
      width: 200px; height: 16px; background: #e9e2d2; border-radius: 99px;
      overflow: hidden; border: 1px solid var(--mex-border);
    }
    .progress-fill { height: 100%; background: var(--mex-green); transition: width 0.3s ease; }

    .status-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .pill-btn {
      padding: 9px 16px; border-radius: 10px; border: 2px solid var(--mex-border);
      background: #ffffff; color: var(--mex-ink); font-weight: 700;
      font-size: calc(15px * var(--scale)); cursor: pointer;
    }
    .pill-btn.active { background: var(--mex-green); color: #ffffff; border-color: var(--mex-green); }

    /* SECCIÓN DE CATEGORÍAS DEBAJO DEL PROGRESO */
    .categories-box {
      margin-top: 18px;
      padding-top: 18px;
      border-top: 2px dashed #e2d9c6;
    }
    .categories-heading {
      font-weight: 700;
      color: var(--mex-ink);
      font-size: calc(16px * var(--scale));
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .categories-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .cat-btn {
      padding: 10px 16px;
      border-radius: 12px;
      border: 2px solid var(--mex-border);
      background: #fffdf8;
      color: var(--mex-ink);
      font-weight: 700;
      font-size: calc(15px * var(--scale));
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.15s ease;
    }
    .cat-btn:hover { border-color: var(--mex-green); background: var(--mex-green-soft); }
    .cat-btn.active {
      background: var(--mex-green);
      color: #ffffff;
      border-color: var(--mex-green);
      box-shadow: 0 3px 8px rgba(8,76,55,0.2);
    }
    .cat-count {
      background: rgba(0,0,0,0.08);
      padding: 2px 8px;
      border-radius: 99px;
      font-size: calc(13px * var(--scale));
    }
    .cat-btn.active .cat-count { background: rgba(255,255,255,0.22); color: #ffffff; }

    /* CUADRÍCULA DE TARJETAS GRANDES DE ARTÍCULOS */
    .articles-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 22px;
      margin-top: 24px;
    }
    @media (max-width: 960px) {
      .articles-grid { grid-template-columns: 1fr; }
    }
    .art-card {
      background: #ffffff;
      border: 2px solid var(--mex-border);
      border-radius: 16px;
      padding: 22px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 16px;
      transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }
    .art-card.hero-card { border-color: var(--mex-gold); background: #fffefb; }
    .art-card:hover { border-color: var(--mex-green); box-shadow: 0 8px 24px rgba(8,76,55,0.11); }

    .art-top-meta { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 12px; }
    .pos-badge {
      padding: 5px 12px; border-radius: 8px; font-weight: 800; color: #fff;
      background: var(--mex-green); font-size: calc(14.5px * var(--scale));
    }
    .pos-badge.hero { background: var(--mex-red); }
    .sec-badge {
      padding: 5px 11px; border-radius: 8px; font-weight: 700;
      background: var(--mex-paper-alt); color: var(--mex-green); font-size: calc(13px * var(--scale));
    }
    .status-badge {
      padding: 5px 12px; border-radius: 99px; font-weight: 700; font-size: calc(13.5px * var(--scale));
      border: 1px solid #f3d078; background: #fef7e6; color: #854d0e;
    }
    .status-badge.done { border-color: #86efac; background: #e7f6ec; color: #14532d; }

    .art-body-preview { display: flex; gap: 14px; align-items: flex-start; }
    .art-thumb {
      width: 96px; height: 76px; object-fit: cover; border-radius: 10px;
      border: 1px solid var(--mex-border); flex-shrink: 0; background: #eee;
    }
    .art-title {
      font-family: Georgia, serif; font-weight: 700; font-size: calc(20px * var(--scale));
      color: var(--mex-ink); margin: 0 0 6px 0; line-height: 1.28; cursor: pointer;
    }
    .art-title:hover { color: var(--mex-green); text-decoration: underline; }
    .art-sub { margin: 0; color: var(--mex-muted); font-size: calc(15.5px * var(--scale)); line-height: 1.45; }

    .assistant-chip {
      display: inline-flex; align-items: center; gap: 7px; margin-top: 10px;
      padding: 6px 12px; border-radius: 9px; background: var(--mex-green-soft);
      color: var(--mex-green); font-size: calc(13.5px * var(--scale)); font-weight: 700;
    }

    .art-card-footer {
      padding-top: 14px; border-top: 2px solid #efe9dc;
      display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px;
    }
    .order-btns { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }

    /* VISTA 2: ESPACIO DE TRABAJO UNO POR UNO */
    .guided-bar {
      background: #ffffff; border: 2px solid var(--mex-border); border-radius: 16px;
      padding: 16px 20px; margin-bottom: 20px;
      display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
    }
    .workspace-grid {
      display: grid;
      grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
      gap: 22px;
      align-items: start;
    }
    @media (max-width: 1060px) {
      .workspace-grid { grid-template-columns: 1fr; }
    }

    /* FORMULARIO DE EDICIÓN */
    .form-group { margin-bottom: 18px; }
    .form-group label {
      display: block; font-weight: 700; margin-bottom: 7px;
      font-size: calc(16.5px * var(--scale)); color: var(--mex-ink);
    }
    .field-input, .field-select, .field-textarea {
      width: 100%; padding: 13px 15px; border: 2px solid #c7bea8; border-radius: 12px;
      font-size: calc(17px * var(--scale)); font-family: inherit; background: #fffdf9;
      color: var(--mex-ink); outline: none;
    }
    .field-input:focus, .field-select:focus, .field-textarea:focus { border-color: var(--mex-green); background: #ffffff; }
    .field-title { font-family: Georgia, serif; font-weight: 700; font-size: calc(21px * var(--scale)); }
    .field-body {
      font-family: Georgia, serif; line-height: 1.68; min-height: 360px;
      border-top-left-radius: 0; border-top-right-radius: 0; background: #ffffff;
      font-size: calc(18px * var(--scale));
    }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }

    .editor-toolbar {
      display: flex; flex-wrap: wrap; gap: 8px; padding: 10px 12px;
      background: var(--mex-paper-alt); border: 2px solid #c7bea8; border-bottom: none;
      border-top-left-radius: 12px; border-top-right-radius: 12px;
    }
    .t-btn {
      background: #ffffff; border: 1px solid #b5aa94; border-radius: 8px;
      padding: 8px 13px; font-weight: 700; font-size: calc(14.5px * var(--scale));
      cursor: pointer; color: var(--mex-ink);
    }
    .t-btn:hover { background: var(--mex-green-soft); border-color: var(--mex-green); }

    /* PANEL PEGAR DESDE WORD */
    .word-box {
      background: #f0f6fd;
      border: 2px solid #6b9fd8;
      border-radius: 14px;
      padding: 18px;
      margin-bottom: 18px;
    }
    .word-paste-area {
      width: 100%;
      min-height: 130px;
      max-height: 220px;
      overflow-y: auto;
      background: #ffffff;
      border: 2px dashed #4a83c4;
      border-radius: 10px;
      padding: 14px;
      font-size: calc(16px * var(--scale));
      color: var(--mex-ink);
      outline: none;
      margin: 10px 0;
    }
    .word-paste-area:empty::before {
      content: "Haga clic aquí y pegue su artículo de Word (Ctrl + V)...";
      color: #6b7d93;
      font-style: italic;
    }

    /* PANEL DETECTOR DE CLICHÉS */
    .cliche-box {
      background: #fffaf0;
      border: 2px solid var(--mex-gold);
      border-radius: 14px;
      padding: 18px;
      margin-bottom: 18px;
    }
    .cliche-item {
      background: #ffffff;
      border: 1px solid #e4d4a9;
      border-left: 5px solid var(--mex-red);
      border-radius: 10px;
      padding: 12px 14px;
      margin-top: 10px;
    }
    .cliche-item.low { border-left-color: var(--mex-gold); }

    /* COLUMNA DERECHA: ASISTENTE DEDICADO POR ARTÍCULO */
    .assistant-panel {
      background: #ffffff;
      border: 2px solid var(--mex-green);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 6px 22px rgba(8,76,55,0.12);
      position: sticky;
      top: 96px;
    }
    .assistant-head {
      background: linear-gradient(135deg, #084c37 0%, #0f6b4e 100%);
      color: #ffffff;
      padding: 18px 20px;
    }
    .assistant-focus-box {
      margin-top: 10px;
      padding: 10px 14px;
      border-radius: 10px;
      background: rgba(0,0,0,0.24);
      border: 1px solid rgba(255,255,255,0.22);
    }
    .quick-actions {
      background: var(--mex-green-soft);
      border-bottom: 2px solid #cce3d9;
      padding: 14px 16px;
    }
    .quick-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 9px;
      margin-top: 10px;
    }
    .q-btn {
      text-align: left;
      background: #ffffff;
      border: 2px solid #9ecbb8;
      border-radius: 11px;
      padding: 11px 13px;
      font-weight: 700;
      font-size: calc(14.5px * var(--scale));
      color: #143328;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      font-family: inherit;
    }
    .q-btn:hover { background: #fffdf5; border-color: var(--mex-green); }
    .q-btn.full { grid-column: span 2; border-color: #d8b566; color: #5c4008; }

    .chat-thread {
      background: #fcfaf5;
      padding: 16px;
      min-height: 320px;
      max-height: 470px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .msg-user {
      margin-left: 28px;
      background: var(--mex-gold-soft);
      border: 1px solid #e4cb8e;
      color: #3f2b06;
      padding: 12px 15px;
      border-radius: 14px 14px 4px 14px;
      font-size: calc(15.5px * var(--scale));
    }
    .msg-ai {
      background: #ffffff;
      border: 2px solid #c9dfd5;
      padding: 15px 16px;
      border-radius: 14px 14px 14px 4px;
      font-size: calc(15.5px * var(--scale));
      box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    .msg-insert-preview {
      margin-top: 12px;
      padding: 13px 14px;
      background: #f5fbf8;
      border: 2px solid #9ecbb8;
      border-radius: 11px;
      font-family: Georgia, serif;
      color: #102a20;
      white-space: pre-line;
      line-height: 1.55;
    }
    .msg-sources {
      margin-top: 10px;
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }
    .source-link {
      font-size: 13px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 99px;
      background: #edf2f7;
      color: #1e4e8c;
      text-decoration: none;
      border: 1px solid #cbd5e1;
    }
    .source-link:hover { background: #dbeafe; }

    .chat-input-area {
      background: #ffffff;
      border-top: 2px solid var(--mex-border);
      padding: 14px 16px;
    }
    .chat-input-row { display: flex; gap: 8px; margin-top: 8px; }

    /* TOAST Y MODALES */
    .toast {
      position: fixed; bottom: 24px; right: 24px; z-index: 80;
      background: #ecfdf5; color: #14532d; border: 2px solid #166534;
      border-radius: 14px; padding: 16px 22px; font-weight: 700;
      font-size: calc(16.5px * var(--scale));
      box-shadow: 0 12px 30px rgba(0,0,0,0.18);
      display: flex; align-items: center; gap: 10px; max-width: 480px;
    }
    .modal-backdrop {
      position: fixed; inset: 0; z-index: 90;
      background: rgba(0,0,0,0.58);
      display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .modal-box {
      background: var(--mex-paper);
      border: 4px solid var(--mex-green);
      border-radius: 18px;
      max-width: 680px;
      width: 100%;
      padding: 28px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 18px 45px rgba(0,0,0,0.3);
    }

    /* TABLA DE SUSCRIPTORES (CONSERVADA) */
    .subs-table {
      width: 100%; border-collapse: collapse; background: #fff;
      border-radius: 12px; overflow: hidden; border: 2px solid var(--mex-border);
    }
    .subs-table th {
      background: var(--mex-paper-alt); padding: 15px; text-align: left;
      border-bottom: 2px solid var(--mex-border); font-size: calc(16px * var(--scale));
    }
    .subs-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: calc(16px * var(--scale)); }

    .flash-ok { animation: flashBg 1.3s ease; }
    @keyframes flashBg {
      0% { background-color: #dcfce7; }
      100% { background-color: #ffffff; }
    }
    .pulse-mic { animation: pulseRing 1.1s infinite; }
    @keyframes pulseRing {
      0% { box-shadow: 0 0 0 0 rgba(184,32,38,0.45); }
      70% { box-shadow: 0 0 0 12px rgba(184,32,38,0); }
      100% { box-shadow: 0 0 0 0 rgba(184,32,38,0); }
    }
  </style>
</head>
<body>

  <!-- BARRA SUPERIOR -->
  <header class="top-header">
    <div class="header-inner">
      <div class="brand-group">
        <div class="brand-badge">M</div>
        <div>
          <div class="brand-title">Mex-Ahoy! • Mesa de Redacción</div>
          <div class="brand-sub">Bienvenido, Don Ricardo Castillo • Firebase + Asistente IA</div>
        </div>
      </div>

      <div class="header-controls">
        <div class="scale-box" aria-label="Tamaño de letra">
          <span class="scale-label">Letra:</span>
          <button type="button" class="scale-btn active" id="scale-1" onclick="setTextScale(1)">A</button>
          <button type="button" class="scale-btn" id="scale-2" onclick="setTextScale(1.12)">A+</button>
          <button type="button" class="scale-btn" id="scale-3" onclick="setTextScale(1.24)">A++</button>
        </div>

        <button type="button" class="h-btn" onclick="openSettingsModal()" title="Elegir modelo de IA">
          <span>⚙️</span> <span id="header-model-badge">IA: Carbón</span>
        </button>

        <button type="button" class="h-btn" onclick="toggleModal('help-modal', true)">❓ Cómo usar</button>

        <a href="index.php" class="h-btn <?php echo $activeTab === 'articles' ? 'active' : ''; ?>">📰 Artículos</a>
        <a href="index.php?tab=subscribers" class="h-btn <?php echo $activeTab === 'subscribers' ? 'active' : ''; ?>">📬 Suscriptores</a>
        <a href="../index.html" target="_blank" class="h-btn gold">👀 Ver Periódico ↗</a>
        <a href="index.php?logout=1" class="h-btn">Salir</a>
      </div>
    </div>
  </header>

  <script src="../js/firebase-config.js?v=20261126d"></script>
  <script type="module" src="editorial-assistant.js?v=20261126d"></script>

  <div class="main-wrap">

    <?php if ($activeTab === 'subscribers'): ?>
      <!-- ========================================================
           PESTAÑA: SUSCRIPTORES DEL BOLETÍN (FIREBASE FIRESTORE)
           ======================================================== -->
      <div class="card" style="margin-bottom:22px;">
        <div class="welcome-row">
          <div>
            <h1 class="welcome-title">📬 Suscriptores del Boletín (Firebase)</h1>
            <p class="welcome-desc">
              Lista sincronizada en tiempo real con <strong>Google Firebase (mex-ahoy)</strong>.
              Total registrados: <strong id="subs-count-badge">Cargando...</strong>
            </p>
          </div>
          <div class="welcome-actions">
            <button type="button" onclick="copyAllSubscribers()" class="btn btn-soft">📋 Copiar Correos</button>
            <button type="button" onclick="exportSubscribersCSV()" class="btn btn-primary">📥 Descargar Excel (CSV)</button>
          </div>
        </div>

        <div style="margin-top:18px; padding-top:16px; border-top:2px solid #ece5d6; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
          <strong>➕ Agregar suscriptor manualmente:</strong>
          <input type="email" id="manual-sub-email" class="field-input" style="max-width:340px;" placeholder="correo@ejemplo.com">
          <button type="button" onclick="addManualSubscriber()" class="btn btn-gold">Guardar en Firebase</button>
          <span id="manual-sub-status" style="color:#14532d; font-weight:700;"></span>
        </div>
      </div>

      <table class="subs-table">
        <thead>
          <tr>
            <th>Correo Electrónico</th>
            <th style="width:230px;">Fecha de Registro</th>
            <th style="width:190px;">Origen</th>
            <th style="width:130px; text-align:center;">Estado</th>
          </tr>
        </thead>
        <tbody id="subscribers-table-body">
          <tr><td colspan="4" style="text-align:center; padding:32px; color:#666;">Conectando con Firebase Firestore...</td></tr>
        </tbody>
      </table>

      <script>
        const LOCAL_SUBSCRIBERS = <?php echo json_encode($localSubscribers, JSON_UNESCAPED_UNICODE); ?>;
        let ALL_SUBSCRIBERS = [];

        async function loadAllSubscribers() {
          const tbody = document.getElementById('subscribers-table-body');
          const badge = document.getElementById('subs-count-badge');
          const mergedMap = {};
          for (const sub of LOCAL_SUBSCRIBERS) {
            if (sub && sub.email) mergedMap[sub.email.toLowerCase()] = sub;
          }
          if (typeof window.fetchFirestoreSubscribers === 'function') {
            const cloudSubs = await window.fetchFirestoreSubscribers();
            for (const sub of cloudSubs) {
              if (sub && sub.email) mergedMap[sub.email.toLowerCase()] = sub;
            }
          }
          ALL_SUBSCRIBERS = Object.values(mergedMap).sort((a, b) =>
            String(b.subscribedAt || '').localeCompare(String(a.subscribedAt || ''))
          );
          if (badge) badge.textContent = ALL_SUBSCRIBERS.length + ' suscriptores';
          if (ALL_SUBSCRIBERS.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:32px; color:#666;">Aún no hay suscriptores registrados.</td></tr>';
            return;
          }
          tbody.innerHTML = ALL_SUBSCRIBERS.map(sub => {
            const dateStr = sub.subscribedAt ? new Date(sub.subscribedAt).toLocaleString('es-MX') : 'Reciente';
            return `<tr>
              <td><strong>${sub.email}</strong></td>
              <td>${dateStr}</td>
              <td>${sub.source || 'frontpage_newsletter'}</td>
              <td style="text-align:center;"><span class="status-badge done">ACTIVO</span></td>
            </tr>`;
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
          if (ALL_SUBSCRIBERS.length === 0) return alert('Aún no hay correos para copiar.');
          navigator.clipboard.writeText(ALL_SUBSCRIBERS.map(s => s.email).join(', ')).then(() => {
            alert('¡Se copiaron ' + ALL_SUBSCRIBERS.length + ' correos al portapapeles!');
          });
        }

        function exportSubscribersCSV() {
          if (ALL_SUBSCRIBERS.length === 0) return alert('Aún no hay suscriptores para exportar.');
          const rows = [['Email', 'Fecha de Suscripcion', 'Origen', 'Estado']];
          ALL_SUBSCRIBERS.forEach(s => rows.push([s.email || '', s.subscribedAt || '', s.source || 'web', s.status || 'active']));
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
           VISTA 1: MESA DE REDACCIÓN (BOTONES GRANDES + CATEGORÍAS)
           ======================================================== -->
      <section id="view-dashboard">
        <div class="card">
          <div class="welcome-row">
            <div>
              <h1 class="welcome-title">¿En qué artículo trabajamos hoy, Don Ricardo?</h1>
              <p class="welcome-desc">
                Elija una sección abajo, cambie el orden con <strong>⬆️ Subir / ⬇️ Bajar</strong> o abra cualquier artículo para editarlo junto a su asistente dedicado.
              </p>
            </div>
            <div class="welcome-actions">
              <button type="button" class="btn btn-primary btn-lg" onclick="startSequentialMode()">▶ Atender Uno por Uno</button>
              <button type="button" class="btn btn-word btn-lg" onclick="createNewFromWord()">📄 Pegar Artículo de Word</button>
              <button type="button" class="btn btn-gold btn-lg" onclick="createNewArticle(false)">➕ Nuevo Artículo</button>
            </div>
          </div>

          <!-- Progreso de la edición + Filtros de Estado -->
          <div class="progress-bar-row">
            <div class="progress-left">
              <span class="progress-label">Progreso de la edición: <span id="progress-text">0 de 0 listos</span></span>
              <div class="progress-track"><div class="progress-fill" id="progress-fill" style="width:0%;"></div></div>
            </div>
            <div class="status-pills">
              <button type="button" class="pill-btn active" id="filter-status-all" onclick="setStatusFilter('all')">Todos (<span id="count-all">0</span>)</button>
              <button type="button" class="pill-btn" id="filter-status-pending" onclick="setStatusFilter('pending')">⏳ Pendientes (<span id="count-pending">0</span>)</button>
              <button type="button" class="pill-btn" id="filter-status-done" onclick="setStatusFilter('done')">✅ Listos (<span id="count-done">0</span>)</button>
            </div>
          </div>

          <!-- CATEGORÍAS / TEMAS PARA TRABAJAR HOY (Debajo del progreso) -->
          <div class="categories-box">
            <div class="categories-heading">
              <span>📂</span>
              <span>Elija sobre qué categoría o tema desea trabajar hoy:</span>
            </div>
            <div class="categories-grid" id="categories-filter-grid">
              <!-- Renderizado dinámicamente -->
            </div>
          </div>
        </div>

        <!-- Cuadrícula de Tarjetas Grandes -->
        <div class="articles-grid" id="articles-grid"></div>
      </section>

      <!-- ========================================================
           VISTA 2: EDITOR + ASISTENTE DEDICADO A ESTE ARTÍCULO
           ======================================================== -->
      <section id="view-workspace" class="hidden">

        <!-- Barra de Navegación Uno-por-Uno -->
        <div class="guided-bar">
          <button type="button" class="btn btn-soft" onclick="goHome()">⬅ Volver a los Artículos</button>

          <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px;">
            <span class="pos-badge" id="ws-position-pill">Artículo 1 de 1</span>
            <span class="sec-badge" id="ws-category-pill">POLITICS</span>
            <span class="status-badge" id="ws-status-pill">⏳ Pendiente</span>
          </div>

          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button" class="btn btn-soft" id="btn-prev-art" onclick="navigateArticle(-1)">◀ Anterior</button>
            <button type="button" class="btn btn-primary" id="btn-next-art" onclick="navigateArticle(1)">Siguiente Artículo ▶</button>
          </div>
        </div>

        <!-- Espacio Dividido: Izquierda Editor / Derecha Asistente -->
        <div class="workspace-grid">

          <!-- COLUMNA IZQUIERDA: EDITOR DEL ARTÍCULO -->
          <div class="card">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding-bottom:16px; margin-bottom:18px; border-bottom:2px solid #ece5d6;">
              <h2 class="serif" style="margin:0; color:var(--mex-green); font-size:calc(23px * var(--scale));">✍️ Espacio de Edición</h2>
              <div style="display:flex; flex-wrap:wrap; gap:8px;">
                <button type="button" class="btn btn-word btn-sm" onclick="toggleWordPanel()">📄 Pegar desde Word</button>
                <button type="button" class="btn btn-gold btn-sm" onclick="runClicheDetector()">🔎 Detector de Clichés IA</button>
                <button type="button" class="btn btn-soft btn-sm" id="btn-ws-toggle-status" onclick="toggleCurrentStatus()">✅ Marcar como Listo</button>
              </div>
            </div>

            <!-- CAJA DESPLEGABLE: PEGAR DESDE MICROSOFT WORD -->
            <div id="word-panel" class="word-box hidden">
              <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:6px;">
                <strong style="color:#15437d; font-size:calc(18px * var(--scale));">📄 Copiar y Pegar su Artículo desde Microsoft Word</strong>
                <button type="button" class="btn btn-soft btn-sm" onclick="toggleWordPanel(false)">✕ Cerrar</button>
              </div>
              <p style="margin:0 0 8px 0; color:#2b4b73; font-size:calc(15px * var(--scale));">
                Abra su documento en Word, copie su texto (<strong>Ctrl + C</strong>) y péguelo dentro del recuadro azul (<strong>Ctrl + V</strong>). El sistema limpiará automáticamente el formato oculto de Word conservando sus párrafos.
              </p>

              <div id="word-paste-area" class="word-paste-area" contenteditable="true"></div>

              <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin:8px 0 12px;">
                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:700; color:#15437d; font-size:calc(15px * var(--scale));">
                  <input type="checkbox" id="word-remove-dashes" checked style="width:20px; height:20px;">
                  <span>Convertir guiones largos (—) a paréntesis o comas automáticamente</span>
                </label>
                <span id="word-stats" style="font-weight:700; color:#15437d; font-size:calc(14.5px * var(--scale));"></span>
              </div>

              <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <button type="button" class="btn btn-primary btn-sm" onclick="applyWordText('replace', false)">📥 Cargar en este artículo (Reemplazar)</button>
                <button type="button" class="btn btn-soft btn-sm" onclick="applyWordText('append', false)">➕ Agregar al final del texto</button>
                <button type="button" class="btn btn-gold btn-sm" onclick="applyWordText('replace', true)">✨ Cargar y revisar inglés con el Asistente</button>
              </div>
            </div>

            <!-- CAJA DESPLEGABLE: DETECTOR DE CLICHÉS DE IA (WP:AISIGNS + CERO GUIONES) -->
            <div id="cliche-panel" class="cliche-box hidden">
              <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                <strong style="color:#6e4c09; font-size:calc(18px * var(--scale));">🔎 Detector de Clichés de IA (Reglas de Wikipedia + Cero Guiones)</strong>
                <button type="button" class="btn btn-soft btn-sm" onclick="document.getElementById('cliche-panel').classList.add('hidden')">✕ Cerrar</button>
              </div>
              <div id="cliche-results" style="margin-top:10px;"></div>
            </div>

            <!-- FORMULARIO PRINCIPAL DEL ARTÍCULO -->
            <form id="article-form" onsubmit="event.preventDefault(); saveCurrentArticle(false);">
              <input type="hidden" id="ed-id" value="">
              <input type="hidden" id="ed-current-image" value="">

              <div class="form-group">
                <label for="ed-title">Titular Principal (en inglés):</label>
                <input type="text" id="ed-title" class="field-input field-title" required oninput="onEditorFieldChanged()" placeholder="Write the main headline here...">
              </div>

              <div class="form-group">
                <label for="ed-subtitle">Subtítulo / Bajada explicativa:</label>
                <textarea id="ed-subtitle" class="field-textarea" rows="2" oninput="onEditorFieldChanged()" placeholder="Short deck / summary for the front page..."></textarea>
              </div>

              <div class="grid-2">
                <div class="form-group">
                  <label for="ed-category">Sección del periódico:</label>
                  <select id="ed-category" class="field-select" onchange="onEditorFieldChanged()">
                    <option value="POLITICS">POLITICS (Política)</option>
                    <option value="THE EDITOR'S DESK">THE EDITOR'S DESK (Editorial)</option>
                    <option value="HISTORY">HISTORY (Historia)</option>
                    <option value="CULTURE & TRADITIONS">CULTURE & TRADITIONS (Cultura)</option>
                    <option value="LIVING IN MEXICO">LIVING IN MEXICO (Vivir en México)</option>
                    <option value="TRAVEL">TRAVEL (Viajes)</option>
                    <option value="FOOD & DRINK">FOOD & DRINK (Gastronomía)</option>
                    <option value="OPINION">OPINION (Opinión)</option>
                    <option value="EXPAT LIFE">EXPAT LIFE (Expatriados)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="ed-date">Fecha de publicación:</label>
                  <input type="text" id="ed-date" class="field-input" oninput="onEditorFieldChanged()">
                </div>
              </div>

              <div class="grid-2">
                <div class="form-group">
                  <label for="ed-author">Autor:</label>
                  <input type="text" id="ed-author" class="field-input" oninput="onEditorFieldChanged()">
                </div>
                <div class="form-group">
                  <label for="ed-author-role">Cargo del autor:</label>
                  <input type="text" id="ed-author-role" class="field-input" oninput="onEditorFieldChanged()">
                </div>
              </div>

              <!-- Fotografía -->
              <div class="form-group">
                <label for="ed-photo-file">Fotografía del artículo:</label>
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px; background:var(--mex-paper); padding:12px; border-radius:12px; border:1px solid var(--mex-border);">
                  <img id="ed-photo-preview" src="../assets/images/hero_palacio.jpg" alt="Foto actual" style="width:110px; height:76px; object-fit:cover; border-radius:8px; border:1px solid #ccc;">
                  <div style="flex:1; min-width:220px;">
                    <input type="file" id="ed-photo-file" accept="image/*" onchange="previewNewPhoto(this)" style="font-size:15px;">
                    <div id="ed-photo-name" style="font-size:14px; color:var(--mex-muted); margin-top:4px;"></div>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label for="ed-image-caption">Pie de foto / Crédito:</label>
                <input type="text" id="ed-image-caption" class="field-input" oninput="onEditorFieldChanged()" placeholder="Photo description and credit...">
              </div>

              <!-- Cuerpo limpio sin etiquetas HTML confusas -->
              <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:7px; flex-wrap:wrap; gap:8px;">
                  <label for="ed-body" style="margin:0;">Texto completo del artículo:</label>
                  <span id="ed-word-count" style="font-size:14px; font-weight:700; padding:4px 12px; border-radius:99px; background:var(--mex-paper-alt); color:var(--mex-muted);">0 palabras</span>
                </div>

                <div class="editor-toolbar">
                  <button type="button" class="t-btn" onclick="insertEditorBlock('\n\n### Nuevo Subtítulo\n')">📌 Subtítulo</button>
                  <button type="button" class="t-btn" onclick="insertEditorBlock('\n\n> &quot;Escriba aquí la cita destacada...&quot; (Fuente)\n')">❝ Cita Destacada</button>
                  <button type="button" class="t-btn" id="btn-dictate-editor" onclick="startVoiceDictation('editor')" style="color:var(--mex-red);">🎙️ Dictar texto</button>
                  <button type="button" class="t-btn" id="btn-speak-article" onclick="toggleSpeakArticle()" style="color:var(--mex-green); margin-left:auto;">🔊 Escuchar artículo</button>
                </div>
                <textarea id="ed-body" class="field-textarea field-body" required oninput="onEditorFieldChanged()" placeholder="Escriba aquí su artículo o use el botón 'Pegar desde Word' arriba..."></textarea>
                <div style="font-size:14px; color:var(--mex-muted); margin-top:6px;">
                  💡 Escriba con naturalidad dejando una línea en blanco entre párrafos. Para subtítulos use <strong>📌 Subtítulo</strong> (se verá con el diseño elegante de Mex-Ahoy! al publicar).
                </div>
              </div>

              <div class="form-group" style="background:#fffdf4; border:2px solid #e6cf92; border-radius:12px; padding:14px 16px;">
                <label style="display:flex; align-items:center; gap:10px; margin:0; cursor:pointer; color:var(--mex-red);">
                  <input type="checkbox" id="ed-is-hero" style="width:22px; height:22px;">
                  <span>⭐ Colocar como NOTICIA PRINCIPAL DE PORTADA (#1 en Mex-Ahoy!)</span>
                </label>
              </div>

              <!-- Botones grandes de guardado -->
              <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding-top:16px; border-top:2px solid #ece5d6;">
                <button type="submit" id="btn-save-article" class="btn btn-primary btn-lg">💾 Guardar y Publicar Artículo</button>
                <button type="button" class="btn btn-gold btn-lg" onclick="saveCurrentArticle(true)">Guardar e ir al Siguiente ➔</button>
              </div>
            </form>
          </div>

          <!-- COLUMNA DERECHA: ASISTENTE DEDICADO EXCLUSIVAMENTE A ESTE ARTÍCULO -->
          <aside class="assistant-panel">
            <div class="assistant-head">
              <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                <div style="display:flex; align-items:center; gap:9px;">
                  <span style="font-size:24px;">🧭</span>
                  <h2 class="serif" style="margin:0; font-size:calc(20px * var(--scale));">Asistente de Este Artículo</h2>
                </div>
                <span id="asst-model-pill" style="background:var(--mex-gold); color:#16221c; padding:4px 10px; border-radius:99px; font-size:12.5px; font-weight:800;">
                  Carbón • Google Search ✓
                </span>
              </div>

              <div class="assistant-focus-box">
                <div style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#ffe3a3; font-weight:700;">
                  Enfocado exclusivamente en:
                </div>
                <div id="asst-focus-title" style="font-weight:700; font-size:calc(16px * var(--scale)); margin-top:2px; line-height:1.3;">
                  Cargando artículo...
                </div>
              </div>
            </div>

            <!-- Botones de 1 Clic -->
            <div class="quick-actions">
              <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                <strong style="color:var(--mex-green); font-size:calc(15px * var(--scale));">👇 Pulse un botón para investigar o redactar:</strong>
                <button type="button" onclick="clearCurrentArticleChat()" style="background:none; border:none; color:#53665d; font-size:13px; text-decoration:underline; cursor:pointer;">Limpiar chat</button>
              </div>

              <div class="quick-grid">
                <button type="button" class="q-btn" onclick="triggerAssistant('historical')">🏛️ Contexto Histórico</button>
                <button type="button" class="q-btn" onclick="triggerAssistant('political')">🗳️ Contexto Político</button>
                <button type="button" class="q-btn" onclick="triggerAssistant('facts')">🔍 Datos y Fechas</button>
                <button type="button" class="q-btn" onclick="triggerAssistant('polish')">✨ Pulir Inglés (mi voz)</button>
                <button type="button" class="q-btn full" onclick="triggerAssistant('headlines')">📰 Sugerir 3 Titulares Periodísticos Naturales</button>
              </div>
            </div>

            <!-- Historial Propio de Este Artículo -->
            <div id="assistant-thread" class="chat-thread"></div>

            <!-- Cuadro de Pregunta Libre + Voz -->
            <div class="chat-input-area">
              <label for="asst-input" style="display:block; font-weight:700; font-size:calc(15px * var(--scale));">
                ¿Quiere preguntarle algo más sobre este artículo?
              </label>
              <div class="chat-input-row">
                <input type="text" id="asst-input" class="field-input" style="padding:11px 14px;" onkeydown="if(event.key==='Enter'){ event.preventDefault(); sendCustomQuestion(); }" placeholder="Ej: ¿Qué dice la Constitución mexicana sobre esto?">
                <button type="button" id="btn-dictate-assistant" class="btn btn-soft btn-sm" onclick="startVoiceDictation('assistant')" title="Dictar pregunta con voz">🎙️</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="sendCustomQuestion()">Enviar</button>
              </div>
            </div>
          </aside>
        </div>
      </section>
    <?php endif; ?>
  </div>

  <!-- NOTIFICACIÓN AMIGABLE (TOAST) -->
  <div id="toast" class="toast hidden">
    <span style="font-size:22px;">✅</span>
    <span id="toast-text">Listo</span>
  </div>

  <!-- MODAL: GUÍA RÁPIDA PARA DON RICARDO -->
  <div id="help-modal" class="modal-backdrop hidden">
    <div class="modal-box">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
        <h3 class="serif" style="margin:0; color:var(--mex-green); font-size:25px;">📖 Guía Rápida para Don Ricardo</h3>
        <button type="button" class="btn btn-soft btn-sm" onclick="toggleModal('help-modal', false)">✕ Cerrar</button>
      </div>
      <div style="display:flex; flex-direction:column; gap:14px; font-size:17px;">
        <div class="card" style="padding:16px;">
          <strong>1. Elija el tema y el orden:</strong> Debajo de "Progreso de la edición", pulse la categoría que quiera trabajar hoy (como <em>POLITICS</em> o <em>HISTORY</em>) y use <strong>⬆️ Subir / ⬇️ Bajar</strong> para ordenar las noticias.
        </div>
        <div class="card" style="padding:16px;">
          <strong>2. Pegue su artículo de Word o abra uno existente:</strong> Si escribió su texto en Word, pulse el botón azul <strong>📄 Pegar desde Word</strong> y el sistema lo acomoda limpio automáticamente.
        </div>
        <div class="card" style="padding:16px;">
          <strong>3. Use su Asistente propio en cada artículo:</strong> A la derecha solo pulse <strong>🏛️ Contexto Histórico</strong> o <strong>🗳️ Contexto Político</strong>. El asistente busca información real en Google y redacta en inglés humano (sin clichés de IA y sin guiones largos). Si le gusta un párrafo, pulse <strong>➕ Insertar en mi artículo</strong>.
        </div>
        <div class="card" style="padding:16px;">
          <strong>4. Revise y guarde:</strong> Pulse <strong>🔎 Detector de Clichés IA</strong> si quiere verificar el texto, luego <strong>💾 Guardar y Publicar</strong> o <strong>Guardar e ir al Siguiente ➔</strong>.
        </div>
      </div>
      <div style="margin-top:20px; text-align:right;">
        <button type="button" class="btn btn-primary" onclick="toggleModal('help-modal', false)">¡Entendido, gracias!</button>
      </div>
    </div>
  </div>

  <!-- MODAL: CONFIGURACIÓN DEL MODELO DE IA (CARBÓN / NEÓN) -->
  <div id="settings-modal" class="modal-backdrop hidden">
    <div class="modal-box">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 class="serif" style="margin:0; color:var(--mex-green); font-size:24px;">⚙️ Configuración del Asistente IA (Firebase)</h3>
        <button type="button" class="btn btn-soft btn-sm" onclick="toggleModal('settings-modal', false)">✕ Cerrar</button>
      </div>

      <p style="margin-top:0; color:var(--mex-muted);">
        El asistente está conectado al proyecto de Firebase <strong>mex-ahoy</strong> mediante <strong>Firebase AI Logic</strong> con búsqueda en Google en tiempo real y reglas estrictas Anti-IA en inglés (cero guiones largos <code>—</code>).
      </p>

      <div class="form-group">
        <label for="ai-model-select">Modelo de Gemini activo:</label>
        <select id="ai-model-select" class="field-select" onchange="changeAiModel(this.value)">
          <option value="gemini-4-carbon">Gemini 4 Carbón (Recomendado • Mayor profundidad editorial)</option>
          <option value="gemini-4-neon">Gemini 4 Neón (Más rápido • Consultas ágiles)</option>
        </select>
      </div>

      <div class="card" style="padding:16px; background:#fffdf7; margin-bottom:16px;">
        <strong>📌 Reglas activas de redacción (editables en <code>admin/editorial-rules.js</code>):</strong>
        <ul style="margin:8px 0 0 18px; padding:0; line-height:1.6;">
          <li>Redacción de periódico 100% en inglés natural respetando la voz de Don Ricardo.</li>
          <li><strong>Guiones largos (—) y medios (–) completamente prohibidos:</strong> solo paréntesis <code>(...)</code>, comas o puntos.</li>
          <li>Filtro automático contra todos los clichés de <em>Wikipedia: Signs of AI writing</em>.</li>
        </ul>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <a href="https://console.firebase.google.com/project/mex-ahoy/ailogic" target="_blank" class="btn btn-soft btn-sm">🔗 Abrir Firebase AI Logic (mex-ahoy) ↗</a>
        <button type="button" class="btn btn-primary" onclick="toggleModal('settings-modal', false)">Guardar y Cerrar</button>
      </div>
    </div>
  </div>

  <?php if ($activeTab === 'articles'): ?>
  <script>
    // =========================================================================
    // ESTADO PRINCIPAL DEL PANEL EDITORIAL
    // =========================================================================
    let ARTICLES_LIST = <?php echo json_encode($initialArticlesList, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    const CATEGORY_LABELS = {
      'ALL':                  { icon: '📚', label: 'Todas las categorías' },
      'POLITICS':             { icon: '🏛️', label: 'Política (Politics)' },
      'HISTORY':              { icon: '📜', label: 'Historia (History)' },
      'CULTURE & TRADITIONS': { icon: '🎭', label: 'Cultura (Culture)' },
      'LIVING IN MEXICO':     { icon: '🏡', label: 'Vivir en México' },
      'TRAVEL':               { icon: '✈️', label: 'Viajes (Travel)' },
      'FOOD & DRINK':         { icon: '🍽️', label: 'Gastronomía' },
      'OPINION':              { icon: '💬', label: 'Opinión' },
      'EXPAT LIFE':           { icon: '🌎', label: 'Expatriados' },
      "THE EDITOR'S DESK":    { icon: '✒️', label: 'Editorial' }
    };

    let activeStatusFilter = 'all';
    let activeCategoryFilter = 'ALL';
    let currentArticleId = null;
    let isNewUnsavedDraft = false;
    let isAssistantBusy = false;
    let toastTimer = null;
    let lastWordCleanedText = '';
    let isSpeaking = false;

    // =========================================================================
    // INICIALIZACIÓN Y ACCESIBILIDAD
    // =========================================================================
    document.addEventListener('DOMContentLoaded', () => {
      const savedScale = parseFloat(localStorage.getItem('mexahoy_text_scale') || '1');
      setTextScale(savedScale, false);
      syncModelBadges();
      setupWordPasteListener();
      renderDashboard();
    });

    function setTextScale(scale, notify = true) {
      document.documentElement.style.setProperty('--scale', scale);
      localStorage.setItem('mexahoy_text_scale', String(scale));
      [1, 1.12, 1.24].forEach((val, i) => {
        const el = document.getElementById('scale-' + (i + 1));
        if (el) el.classList.toggle('active', Math.abs(val - scale) < 0.01);
      });
      if (notify) {
        showToast(scale === 1 ? 'Tamaño de letra: Normal' : scale < 1.2 ? 'Tamaño de letra: Grande' : 'Tamaño de letra: Extra Grande');
      }
    }

    function showToast(msg) {
      const el = document.getElementById('toast');
      document.getElementById('toast-text').textContent = msg;
      el.classList.remove('hidden');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => el.classList.add('hidden'), 3600);
    }

    function toggleModal(id, show) {
      document.getElementById(id).classList.toggle('hidden', !show);
    }

    function openSettingsModal() {
      const sel = document.getElementById('ai-model-select');
      if (sel && window.MexAhoyAI) sel.value = window.MexAhoyAI.getSelectedModelId();
      toggleModal('settings-modal', true);
    }

    function changeAiModel(modelId) {
      if (window.MexAhoyAI) window.MexAhoyAI.setSelectedModelId(modelId);
      syncModelBadges();
      showToast(modelId === 'gemini-4-carbon' ? 'Modelo cambiado a Gemini 4 Carbón' : 'Modelo cambiado a Gemini 4 Neón');
    }

    function syncModelBadges() {
      const id = (window.MexAhoyAI && window.MexAhoyAI.getSelectedModelId()) || localStorage.getItem('mexahoy_ai_model') || 'gemini-4-carbon';
      const shortName = id === 'gemini-4-neon' ? 'Neón' : 'Carbón';
      const hBadge = document.getElementById('header-model-badge');
      const aBadge = document.getElementById('asst-model-pill');
      if (hBadge) hBadge.textContent = 'IA: ' + shortName;
      if (aBadge) aBadge.textContent = shortName + ' • Google Search ✓';
    }

    // =========================================================================
    // VISTA 1: DASHBOARD, CATEGORÍAS Y ORDENAMIENTO PERSISTENTE
    // =========================================================================
    function getFilteredArticles() {
      return ARTICLES_LIST.filter(art => {
        const st = art.editorialStatus === 'done' ? 'done' : 'pending';
        const matchStatus = activeStatusFilter === 'all' || st === activeStatusFilter;
        const matchCat = activeCategoryFilter === 'ALL' || (art.category || 'POLITICS') === activeCategoryFilter;
        return matchStatus && matchCat;
      });
    }

    function setStatusFilter(status) {
      activeStatusFilter = status;
      ['all', 'pending', 'done'].forEach(k => {
        const el = document.getElementById('filter-status-' + k);
        if (el) el.classList.toggle('active', k === status);
      });
      renderDashboard();
    }

    function setCategoryFilter(catKey) {
      activeCategoryFilter = catKey;
      renderDashboard();
    }

    function renderCategoriesBar() {
      const container = document.getElementById('categories-filter-grid');
      if (!container) return;

      const counts = { ALL: ARTICLES_LIST.length };
      ARTICLES_LIST.forEach(art => {
        const c = art.category || 'POLITICS';
        counts[c] = (counts[c] || 0) + 1;
      });

      // Mostrar "Todas" + todas las categorías presentes en los artículos
      const keys = ['ALL', ...Object.keys(CATEGORY_LABELS).filter(k => k !== 'ALL' && counts[k] > 0)];
      Object.keys(counts).forEach(k => {
        if (!keys.includes(k)) keys.push(k);
      });

      container.innerHTML = keys.map(key => {
        const meta = CATEGORY_LABELS[key] || { icon: '📰', label: key };
        const isActive = activeCategoryFilter === key;
        return `
          <button type="button" class="cat-btn ${isActive ? 'active' : ''}" onclick="setCategoryFilter(${JSON.stringify(key).replace(/"/g, '&quot;')})">
            <span>${meta.icon}</span>
            <span>${escapeHtml(meta.label)}</span>
            <span class="cat-count">${counts[key] || 0}</span>
          </button>`;
      }).join('');
    }

    function renderDashboard() {
      const doneCount = ARTICLES_LIST.filter(a => a.editorialStatus === 'done').length;
      const pendingCount = ARTICLES_LIST.length - doneCount;
      document.getElementById('count-all').textContent = ARTICLES_LIST.length;
      document.getElementById('count-pending').textContent = pendingCount;
      document.getElementById('count-done').textContent = doneCount;
      document.getElementById('progress-text').textContent = `${doneCount} de ${ARTICLES_LIST.length} listos`;
      const pct = ARTICLES_LIST.length ? Math.round((doneCount / ARTICLES_LIST.length) * 100) : 0;
      document.getElementById('progress-fill').style.width = pct + '%';

      renderCategoriesBar();

      const grid = document.getElementById('articles-grid');
      const visible = getFilteredArticles();

      if (visible.length === 0) {
        grid.innerHTML = `
          <div class="card" style="grid-column: 1 / -1; text-align:center; padding:42px;">
            <div style="font-size:40px; margin-bottom:8px;">📂</div>
            <h3 class="serif" style="margin:0 0 8px; color:var(--mex-green); font-size:24px;">No hay artículos con este filtro</h3>
            <p style="color:var(--mex-muted); margin:0 0 16px;">Pulse "Todas las categorías" o "Todos" para ver la edición completa.</p>
            <button type="button" class="btn btn-primary" onclick="activeStatusFilter='all'; activeCategoryFilter='ALL'; setStatusFilter('all');">Ver todos los artículos</button>
          </div>`;
        return;
      }

      grid.innerHTML = visible.map(art => {
        const globalIdx = ARTICLES_LIST.findIndex(x => x.id === art.id);
        const isHero = globalIdx === 0;
        const isDone = art.editorialStatus === 'done';
        const savedHistory = loadArticleHistory(art.id);
        const imgSrc = art.image ? '../' + art.image : '../assets/images/hero_palacio.jpg';

        return `
          <article class="art-card ${isHero ? 'hero-card' : ''}">
            <div>
              <div class="art-top-meta">
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                  <span class="pos-badge ${isHero ? 'hero' : ''}">#${globalIdx + 1}${isHero ? ' ⭐ PORTADA' : ''}</span>
                  <span class="sec-badge">${escapeHtml(art.category || 'POLITICS')}</span>
                </div>
                <span class="status-badge ${isDone ? 'done' : ''}">${isDone ? '✅ Listo' : '⏳ Pendiente'}</span>
              </div>

              <div class="art-body-preview">
                <img class="art-thumb" src="${escapeHtml(imgSrc)}" alt="" onerror="this.src='../assets/images/hero_palacio.jpg'">
                <div style="flex:1; min-width:0;">
                  <h3 class="art-title" onclick="openArticleById('${escapeJs(art.id)}')">${escapeHtml(art.title)}</h3>
                  <p class="art-sub">${escapeHtml((art.subtitle || '').slice(0, 140))}${(art.subtitle || '').length > 140 ? '...' : ''}</p>
                </div>
              </div>

              <div class="assistant-chip">
                <span>🧭</span>
                <span>Asistente propio listo${savedHistory.length > 0 ? ` • ${savedHistory.length} nota(s) guardadas` : ' • Enfocado en este artículo'}</span>
              </div>
            </div>

            <div class="art-card-footer">
              <div class="order-btns">
                <button type="button" class="btn btn-soft btn-sm" onclick="reorderArticle('${escapeJs(art.id)}', -1)" ${globalIdx === 0 ? 'disabled' : ''}>⬆️ Subir</button>
                <button type="button" class="btn btn-soft btn-sm" onclick="reorderArticle('${escapeJs(art.id)}', 1)" ${globalIdx === ARTICLES_LIST.length - 1 ? 'disabled' : ''}>⬇️ Bajar</button>
                ${!isHero ? `<button type="button" class="btn btn-gold btn-sm" onclick="makeArticleHero('${escapeJs(art.id)}')">⭐ Portada</button>` : ''}
                ${!isHero ? `<button type="button" class="btn btn-danger-outline btn-sm" onclick="deleteArticle('${escapeJs(art.id)}')" title="Retirar artículo">🗑️</button>` : ''}
              </div>
              <button type="button" class="btn btn-primary btn-sm" onclick="openArticleById('${escapeJs(art.id)}')">✏️ Abrir y Editar ➔</button>
            </div>
          </article>`;
      }).join('');
    }

    async function persistOrderToServer() {
      const orderedIds = ARTICLES_LIST.map(a => a.id);
      const fd = new FormData();
      fd.append('ajax_action', 'reorder_articles');
      fd.append('ordered_ids', JSON.stringify(orderedIds));

      try {
        const res = await fetch('index.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok && typeof window.syncArticlesOrderToFirestore === 'function') {
          window.syncArticlesOrderToFirestore(orderedIds);
        }
      } catch (e) {
        console.warn('Error guardando orden:', e);
      }
    }

    async function reorderArticle(id, delta) {
      const idx = ARTICLES_LIST.findIndex(a => a.id === id);
      const target = idx + delta;
      if (idx < 0 || target < 0 || target >= ARTICLES_LIST.length) return;
      const tmp = ARTICLES_LIST[idx];
      ARTICLES_LIST[idx] = ARTICLES_LIST[target];
      ARTICLES_LIST[target] = tmp;
      renderDashboard();
      await persistOrderToServer();
      showToast(`Artículo movido al lugar #${target + 1} y guardado.`);
    }

    async function makeArticleHero(id) {
      const idx = ARTICLES_LIST.findIndex(a => a.id === id);
      if (idx <= 0) return;
      const [item] = ARTICLES_LIST.splice(idx, 1);
      ARTICLES_LIST.unshift(item);
      renderDashboard();
      await persistOrderToServer();
      showToast('⭐ ¡Colocado como Noticia Principal de Portada (#1)!');
    }

    async function deleteArticle(id) {
      const art = ARTICLES_LIST.find(a => a.id === id);
      if (!art) return;
      if (!confirm(`¿Está seguro de retirar del periódico el artículo "${art.title}"? (Se guardará un respaldo automático)`)) return;

      const fd = new FormData();
      fd.append('ajax_action', 'delete_article');
      fd.append('article_id', id);
      const res = await fetch('index.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.ok) {
        ARTICLES_LIST = ARTICLES_LIST.filter(a => a.id !== id);
        if (typeof window.deleteArticleFromFirestore === 'function') {
          window.deleteArticleFromFirestore(id);
        }
        renderDashboard();
        showToast('Artículo retirado correctamente.');
      }
    }

    function startSequentialMode() {
      const visible = getFilteredArticles();
      if (visible.length === 0) return createNewArticle(false);
      const firstPending = visible.find(a => a.editorialStatus !== 'done') || visible[0];
      openArticleById(firstPending.id);
      showToast('Modo Uno por Uno activado. Empiece con este artículo.');
    }

    function createNewFromWord() {
      createNewArticle(true);
    }

    function createNewArticle(openWordRightAway = false) {
      isNewUnsavedDraft = true;
      currentArticleId = '__new__';

      const defaultCat = activeCategoryFilter !== 'ALL' ? activeCategoryFilter : 'POLITICS';
      const todayStr = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

      const draft = {
        id: '',
        category: defaultCat,
        title: '',
        subtitle: '',
        author: 'Ricardo Castillo',
        authorRole: 'Senior Political Analyst & Editor-in-Chief',
        date: todayStr,
        image: 'assets/images/hero_palacio.jpg',
        imageCaption: '',
        body: '',
        editorialStatus: 'pending',
        isHero: false
      };

      openEditorWithData(draft, -1, 1);
      if (openWordRightAway) {
        toggleWordPanel(true);
        setTimeout(() => document.getElementById('word-paste-area').focus(), 120);
      }
    }

    // =========================================================================
    // VISTA 2: EDITOR UNO-POR-UNO + ASISTENTE DEDICADO
    // =========================================================================
    function goHome() {
      stopSpeaking();
      document.getElementById('view-workspace').classList.add('hidden');
      document.getElementById('view-dashboard').classList.remove('hidden');
      document.getElementById('word-panel').classList.add('hidden');
      document.getElementById('cliche-panel').classList.add('hidden');
      renderDashboard();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function openArticleById(id) {
      const visible = getFilteredArticles();
      const idxInVisible = visible.findIndex(a => a.id === id);
      const art = ARTICLES_LIST.find(a => a.id === id);
      if (!art) return;

      isNewUnsavedDraft = false;
      currentArticleId = art.id;
      openEditorWithData(art, idxInVisible >= 0 ? idxInVisible : 0, Math.max(1, visible.length));
    }

    function openEditorWithData(art, idxInVisible, totalVisible) {
      stopSpeaking();
      document.getElementById('view-dashboard').classList.add('hidden');
      document.getElementById('view-workspace').classList.remove('hidden');
      document.getElementById('word-panel').classList.add('hidden');
      document.getElementById('cliche-panel').classList.add('hidden');

      document.getElementById('ed-id').value = art.id || '';
      document.getElementById('ed-title').value = art.title || '';
      document.getElementById('ed-subtitle').value = art.subtitle || '';
      document.getElementById('ed-category').value = art.category || 'POLITICS';
      document.getElementById('ed-date').value = art.date || '';
      document.getElementById('ed-author').value = art.author || 'Ricardo Castillo';
      document.getElementById('ed-author-role').value = art.authorRole || 'Senior Political Analyst & Editor-in-Chief';
      document.getElementById('ed-current-image').value = art.image || 'assets/images/hero_palacio.jpg';
      document.getElementById('ed-image-caption').value = art.imageCaption || '';
      document.getElementById('ed-photo-file').value = '';

      const imgSrc = art.image ? '../' + art.image : '../assets/images/hero_palacio.jpg';
      document.getElementById('ed-photo-preview').src = imgSrc;
      document.getElementById('ed-photo-name').textContent = 'Foto actual: ' + (art.image || 'hero_palacio.jpg');

      // Convertir HTML a texto limpio legible para Don Ricardo
      const cleanBody = window.MexAhoyAI
        ? window.MexAhoyAI.htmlBodyToCleanText(art.body || '')
        : (art.body || '');
      document.getElementById('ed-body').value = cleanBody;

      const globalIdx = ARTICLES_LIST.findIndex(x => x.id === art.id);
      document.getElementById('ed-is-hero').checked = (globalIdx === 0);

      // Navegación Uno-por-Uno
      const posPill = document.getElementById('ws-position-pill');
      if (isNewUnsavedDraft) {
        posPill.textContent = '✨ Artículo Nuevo';
        document.getElementById('btn-prev-art').disabled = true;
        document.getElementById('btn-next-art').disabled = true;
      } else {
        const catSuffix = activeCategoryFilter !== 'ALL' ? ` (${activeCategoryFilter})` : '';
        posPill.textContent = `Artículo ${idxInVisible + 1} de ${totalVisible}${catSuffix}`;
        document.getElementById('btn-prev-art').disabled = (idxInVisible <= 0);
        document.getElementById('btn-next-art').disabled = (idxInVisible >= totalVisible - 1);
      }

      document.getElementById('ws-category-pill').textContent = art.category || 'POLITICS';
      updateWorkspaceStatusUI(art.editorialStatus === 'done' ? 'done' : 'pending');
      updateWordCounter();

      // Enfocar asistente exclusivamente en ESTE artículo
      document.getElementById('asst-focus-title').textContent = art.title || 'Nuevo Artículo (escriba un título o pegue desde Word)';
      syncModelBadges();
      renderAssistantThread();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function navigateArticle(step) {
      if (isNewUnsavedDraft) return;
      const visible = getFilteredArticles();
      const idx = visible.findIndex(a => a.id === currentArticleId);
      const nextIdx = idx + step;
      if (nextIdx >= 0 && nextIdx < visible.length) {
        openArticleById(visible[nextIdx].id);
      }
    }

    function onEditorFieldChanged() {
      const title = document.getElementById('ed-title').value.trim();
      const cat = document.getElementById('ed-category').value;
      document.getElementById('asst-focus-title').textContent = title || 'Artículo en borrador';
      document.getElementById('ws-category-pill').textContent = cat;
      updateWordCounter();
    }

    function updateWordCounter() {
      const txt = document.getElementById('ed-body').value.trim();
      const words = txt ? txt.split(/\s+/).filter(Boolean).length : 0;
      document.getElementById('ed-word-count').textContent = `${words} palabras`;
    }

    function updateWorkspaceStatusUI(status) {
      const pill = document.getElementById('ws-status-pill');
      const btn = document.getElementById('btn-ws-toggle-status');
      const isDone = status === 'done';
      pill.textContent = isDone ? '✅ Listo para publicar' : '⏳ Pendiente';
      pill.classList.toggle('done', isDone);
      btn.textContent = isDone ? '↩️ Volver a Pendiente' : '✅ Marcar como Listo';
    }

    async function toggleCurrentStatus() {
      if (isNewUnsavedDraft) {
        showToast('Guarde primero el artículo nuevo para marcarlo como listo.');
        return;
      }
      const art = ARTICLES_LIST.find(a => a.id === currentArticleId);
      if (!art) return;
      const newStatus = art.editorialStatus === 'done' ? 'pending' : 'done';
      art.editorialStatus = newStatus;
      updateWorkspaceStatusUI(newStatus);

      const fd = new FormData();
      fd.append('ajax_action', 'toggle_status');
      fd.append('article_id', art.id);
      fd.append('status', newStatus);
      await fetch('index.php', { method: 'POST', body: fd });
      showToast(newStatus === 'done' ? '✅ ¡Artículo marcado como LISTO!' : 'Artículo marcado como Pendiente');
    }

    function previewNewPhoto(input) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
          document.getElementById('ed-photo-preview').src = e.target.result;
          document.getElementById('ed-photo-name').textContent = 'Nueva foto lista para subir: ' + input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    function insertEditorBlock(snippet) {
      const ta = document.getElementById('ed-body');
      const start = ta.selectionStart ?? ta.value.length;
      const end = ta.selectionEnd ?? ta.value.length;
      ta.value = ta.value.slice(0, start) + snippet + ta.value.slice(end);
      flashElement(ta);
      onEditorFieldChanged();
      ta.focus();
    }

    async function saveCurrentArticle(goNextAfter = false) {
      const title = document.getElementById('ed-title').value.trim();
      const bodyClean = document.getElementById('ed-body').value.trim();
      if (!title) {
        alert('Por favor escriba un Titular Principal antes de guardar.');
        document.getElementById('ed-title').focus();
        return;
      }

      const btn = document.getElementById('btn-save-article');
      btn.disabled = true;
      btn.textContent = '⏳ Guardando y publicando...';

      const bodyHtml = window.MexAhoyAI
        ? window.MexAhoyAI.cleanTextToNewspaperHtml(bodyClean)
        : bodyClean;

      const existingArt = ARTICLES_LIST.find(a => a.id === currentArticleId);
      const nextStatus = goNextAfter ? 'done' : (existingArt?.editorialStatus || 'pending');

      const fd = new FormData();
      fd.append('ajax_action', 'save_article');
      fd.append('article_id', document.getElementById('ed-id').value.trim());
      fd.append('title', title);
      fd.append('subtitle', document.getElementById('ed-subtitle').value.trim());
      fd.append('category', document.getElementById('ed-category').value);
      fd.append('date', document.getElementById('ed-date').value.trim());
      fd.append('author', document.getElementById('ed-author').value.trim());
      fd.append('author_role', document.getElementById('ed-author-role').value.trim());
      fd.append('current_image', document.getElementById('ed-current-image').value.trim());
      fd.append('image_caption', document.getElementById('ed-image-caption').value.trim());
      fd.append('body_html', bodyHtml);
      fd.append('editorial_status', nextStatus);
      if (document.getElementById('ed-is-hero').checked) {
        fd.append('is_hero', '1');
      }
      const photoFile = document.getElementById('ed-photo-file').files[0];
      if (photoFile) fd.append('photo', photoFile);

      try {
        const res = await fetch('index.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.ok) {
          alert(data.error || 'Ocurrió un problema al guardar.');
          return;
        }

        const saved = data.article;
        // Actualizar memoria local respetando orden
        const existingIdx = ARTICLES_LIST.findIndex(a => a.id === saved.id);
        if (existingIdx >= 0) {
          ARTICLES_LIST[existingIdx] = saved;
        } else {
          ARTICLES_LIST.push(saved);
        }
        if (data.orderedIds && Array.isArray(data.orderedIds)) {
          const map = {};
          ARTICLES_LIST.forEach(a => { map[a.id] = a; });
          ARTICLES_LIST = data.orderedIds.map(id => map[id]).filter(Boolean);
        }

        // Si era borrador nuevo y tenía chat en __new__, migrar historial a su ID real
        if (isNewUnsavedDraft) {
          const draftHist = loadArticleHistory('__new__');
          if (draftHist.length > 0) {
            saveArticleHistory(saved.id, draftHist);
            localStorage.removeItem('mexahoy_asst_hist___new__');
          }
        }

        currentArticleId = saved.id;
        isNewUnsavedDraft = false;
        document.getElementById('ed-id').value = saved.id;
        document.getElementById('ed-current-image').value = saved.image;
        document.getElementById('ed-photo-preview').src = '../' + saved.image;
        updateWorkspaceStatusUI(saved.editorialStatus);

        // Sincronizar con Firebase Firestore
        if (typeof window.syncArticleToFirestore === 'function') {
          window.syncArticleToFirestore(saved, !!saved.isHero);
        }

        showToast('💾 ¡Excelente, Don Ricardo! Guardado en el periódico y en Firebase.');

        if (goNextAfter) {
          const visible = getFilteredArticles();
          const idx = visible.findIndex(a => a.id === saved.id);
          if (idx >= 0 && idx < visible.length - 1) {
            openArticleById(visible[idx + 1].id);
          } else {
            goHome();
            showToast('🎉 ¡Felicidades, Don Ricardo! Terminó esta sección.');
          }
        }
      } catch (err) {
        alert('Error de conexión al guardar: ' + err.message);
      } finally {
        btn.disabled = false;
        btn.textContent = '💾 Guardar y Publicar Artículo';
      }
    }

    // =========================================================================
    // HERRAMIENTA 1: PEGAR DESDE MICROSOFT WORD
    // =========================================================================
    function toggleWordPanel(forceState) {
      const panel = document.getElementById('word-panel');
      const show = typeof forceState === 'boolean' ? forceState : panel.classList.contains('hidden');
      panel.classList.toggle('hidden', !show);
      if (show) {
        document.getElementById('word-paste-area').innerHTML = '';
        document.getElementById('word-stats').textContent = '';
        lastWordCleanedText = '';
        document.getElementById('word-paste-area').focus();
      }
    }

    function setupWordPasteListener() {
      const area = document.getElementById('word-paste-area');
      if (!area) return;

      area.addEventListener('paste', (e) => {
        e.preventDefault();
        const htmlData = e.clipboardData ? e.clipboardData.getData('text/html') : '';
        const plainData = e.clipboardData ? e.clipboardData.getData('text/plain') : '';
        const removeDashes = document.getElementById('word-remove-dashes').checked;

        const cleaned = window.MexAhoyAI
          ? window.MexAhoyAI.cleanWordContent(htmlData, plainData, removeDashes)
          : { cleanedText: plainData, paragraphCount: 1, wordCount: plainData.split(/\s+/).length };

        lastWordCleanedText = cleaned.cleanedText;
        area.innerText = cleaned.cleanedText;
        document.getElementById('word-stats').textContent =
          `✅ Limpio: ${cleaned.paragraphCount} párrafo(s) • ${cleaned.wordCount} palabras`;
        showToast('📄 Texto de Word limpiado y listo para cargar.');
      });

      area.addEventListener('input', () => {
        lastWordCleanedText = area.innerText.trim();
      });
    }

    function applyWordText(mode = 'replace', askAssistantToReview = false) {
      const area = document.getElementById('word-paste-area');
      let text = (lastWordCleanedText || area.innerText || '').trim();
      if (!text) {
        alert('Primero pegue el texto de Word dentro del recuadro azul (Ctrl + V).');
        return;
      }

      if (document.getElementById('word-remove-dashes').checked && window.MexAhoyAI) {
        text = text.split('\n\n').map(p => window.MexAhoyAI.enforceNoDashes(p)).join('\n\n');
      }

      const bodyTa = document.getElementById('ed-body');
      const titleInput = document.getElementById('ed-title');

      // Si el titular está vacío y el primer párrafo de Word es corto, ofrecer usarlo como titular
      const paragraphs = text.split('\n\n').filter(Boolean);
      if (!titleInput.value.trim() && paragraphs.length > 1 && paragraphs[0].length < 130) {
        titleInput.value = paragraphs.shift().replace(/^###\s*/, '');
        text = paragraphs.join('\n\n');
      }

      if (mode === 'append' && bodyTa.value.trim()) {
        bodyTa.value = bodyTa.value.trimEnd() + '\n\n' + text;
      } else {
        bodyTa.value = text;
      }

      flashElement(bodyTa);
      onEditorFieldChanged();
      toggleWordPanel(false);
      showToast('📥 ¡Texto de Word cargado limpiamente en el artículo!');

      if (askAssistantToReview) {
        triggerAssistant('polish');
      }
    }

    // =========================================================================
    // HERRAMIENTA 2: DETECTOR DE CLICHÉS DE IA (WP:AISIGNS + CERO GUIONES)
    // =========================================================================
    function runClicheDetector() {
      const panel = document.getElementById('cliche-panel');
      const resultsEl = document.getElementById('cliche-results');
      panel.classList.remove('hidden');

      const title = document.getElementById('ed-title').value;
      const subtitle = document.getElementById('ed-subtitle').value;
      const body = document.getElementById('ed-body').value;

      const findings = window.MexAhoyAI
        ? window.MexAhoyAI.scanArticleCliches(title, subtitle, body)
        : [];

      if (findings.length === 0) {
        resultsEl.innerHTML = `
          <div style="background:#e7f6ec; border:2px solid #86efac; color:#14532d; padding:16px; border-radius:12px; font-weight:700;">
            ✅ ¡Excelente trabajo, Don Ricardo! No se detectó ningún guion largo (—) ni clichés típicos de IA de Wikipedia. Su artículo suena completamente natural y humano.
          </div>`;
        return;
      }

      const hasDashes = findings.some(f => f.found.includes('Guion'));
      const summaryForAi = findings.map(f => `- ${f.found} (${f.count}x): ${f.tip}`).join('\n');

      resultsEl.innerHTML = `
        <div style="font-weight:700; color:#854d0e; margin-bottom:10px;">
          Se encontraron <strong>${findings.length}</strong> detalle(s) que conviene pulir para evitar clichés de IA:
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
          ${hasDashes ? `<button type="button" class="btn btn-primary btn-sm" onclick="stripAllDashesNow()">🧹 Quitar todos los guiones (—) ahora (1 clic)</button>` : ''}
          <button type="button" class="btn btn-gold btn-sm" onclick="fixClichesWithAssistant()">✨ Pedir al Asistente que limpie estos clichés</button>
        </div>
        ${findings.map(f => `
          <div class="cliche-item ${f.severity}">
            <div style="font-weight:700; color:#18201c;">
              ${escapeHtml(f.found)} <span style="color:var(--mex-red);">(${f.count} vez/veces)</span>
            </div>
            <div style="font-size:14.5px; color:var(--mex-muted); margin-top:3px;">
              <strong>Sugerencia:</strong> ${escapeHtml(f.tip)}
            </div>
          </div>
        `).join('')}
      `;

      resultsEl.dataset.aiSummary = summaryForAi;
    }

    function stripAllDashesNow() {
      if (!window.MexAhoyAI) return;
      ['ed-title', 'ed-subtitle', 'ed-body'].forEach(id => {
        const el = document.getElementById(id);
        const lines = el.value.split('\n\n').map(p => window.MexAhoyAI.enforceNoDashes(p));
        el.value = lines.join('\n\n');
        flashElement(el);
      });
      onEditorFieldChanged();
      runClicheDetector();
      showToast('🧹 ¡Todos los guiones largos fueron convertidos a paréntesis o comas!');
    }

    function fixClichesWithAssistant() {
      const summary = document.getElementById('cliche-results')?.dataset?.aiSummary || '';
      triggerAssistant('fix_cliches', summary);
    }

    // =========================================================================
    // MOTOR DEL ASISTENTE DEDICADO POR ARTÍCULO (FIREBASE AI LOGIC REAL)
    // =========================================================================
    function historyStorageKey(artId) {
      return 'mexahoy_asst_hist_' + (artId || '__new__');
    }

    function loadArticleHistory(artId) {
      try {
        const raw = localStorage.getItem(historyStorageKey(artId));
        const arr = JSON.parse(raw || '[]');
        return Array.isArray(arr) ? arr : [];
      } catch (e) {
        return [];
      }
    }

    function saveArticleHistory(artId, list) {
      try {
        localStorage.setItem(historyStorageKey(artId), JSON.stringify(list.slice(-20)));
      } catch (e) {}
    }

    function clearCurrentArticleChat() {
      if (!confirm('¿Limpiar el historial del asistente para este artículo?')) return;
      saveArticleHistory(currentArticleId, []);
      renderAssistantThread();
    }

    function renderAssistantThread() {
      const thread = document.getElementById('assistant-thread');
      const history = loadArticleHistory(currentArticleId);
      const artTitle = document.getElementById('ed-title').value.trim() || 'este artículo';

      if (history.length === 0) {
        thread.innerHTML = `
          <div class="msg-ai">
            <strong style="color:var(--mex-green);">👋 Listo para trabajar en este artículo</strong>
            <p style="margin:8px 0 0; line-height:1.55;">
              Don Ricardo, estoy enfocado únicamente en <strong>"${escapeHtml(artTitle)}"</strong>.<br><br>
              Pulse cualquiera de los botones verdes de arriba para que investigue en Google el contexto histórico, el contexto político o pula el inglés respetando su estilo (sin clichés de IA ni guiones largos).
            </p>
          </div>`;
        return;
      }

      thread.innerHTML = history.map((m, idx) => {
        if (m.role === 'user') {
          return `<div class="msg-user"><div style="font-size:12px; font-weight:800; opacity:0.7; margin-bottom:3px;">USTED PIDIÓ:</div>${escapeHtml(m.displayText || m.rawText)}</div>`;
        }

        if (m.headlines && m.headlines.length > 0) {
          return `
            <div class="msg-ai">
              <strong style="color:var(--mex-green);">📰 3 Propuestas de Titular Natural (Inglés):</strong>
              <div style="display:flex; flex-direction:column; gap:10px; margin-top:10px;">
                ${m.headlines.map((h, hIdx) => `
                  <div style="background:var(--mex-paper); border:1px solid var(--mex-border); border-radius:10px; padding:11px 13px;">
                    <div class="serif" style="font-weight:700; margin-bottom:8px;">${escapeHtml(h)}</div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="applyAssistantHeadline(${idx}, ${hIdx})">✓ Usar este titular</button>
                  </div>
                `).join('')}
                ${m.suggestedSubtitle ? `
                  <div style="background:#fffdf4; border:1px solid var(--mex-gold); border-radius:10px; padding:11px 13px;">
                    <div style="font-size:13px; font-weight:700; color:#6e4c09; margin-bottom:4px;">Subtítulo sugerido:</div>
                    <div style="margin-bottom:8px;">${escapeHtml(m.suggestedSubtitle)}</div>
                    <button type="button" class="btn btn-gold btn-sm" onclick="applyAssistantSubtitle(${idx})">✓ Usar este subtítulo</button>
                  </div>` : ''}
              </div>
              ${renderSourcesPills(m.sources)}
            </div>`;
        }

        return `
          <div class="msg-ai">
            <div style="white-space:pre-line; line-height:1.58;">${escapeHtml(m.explanation || m.rawText)}</div>
            ${m.insertText ? `
              <div class="msg-insert-preview">
                <div style="font-family:sans-serif; font-size:12px; font-weight:800; color:var(--mex-green); text-transform:uppercase; margin-bottom:6px;">
                  🇬🇧 Texto listo en inglés (sin guiones ni clichés):
                </div>
                ${escapeHtml(m.insertText)}
              </div>
              <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;">
                <button type="button" class="btn btn-primary btn-sm" onclick="insertFromAssistant(${idx}, 'append')">➕ Insertar al final</button>
                <button type="button" class="btn btn-gold btn-sm" onclick="insertFromAssistant(${idx}, 'replace')">🔄 Reemplazar texto</button>
                <button type="button" class="btn btn-soft btn-sm" onclick="speakAssistantMsg(${idx})">🔊 Escuchar</button>
                <button type="button" class="btn btn-soft btn-sm" onclick="copyAssistantMsg(${idx})">📋 Copiar</button>
              </div>
            ` : `
              <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;">
                <button type="button" class="btn btn-soft btn-sm" onclick="speakAssistantMsg(${idx})">🔊 Escuchar</button>
                <button type="button" class="btn btn-soft btn-sm" onclick="copyAssistantMsg(${idx})">📋 Copiar</button>
              </div>
            `}
            ${renderSourcesPills(m.sources)}
          </div>`;
      }).join('');

      thread.scrollTop = thread.scrollHeight;
    }

    function renderSourcesPills(sources) {
      if (!sources || !sources.length) return '';
      return `
        <div class="msg-sources">
          <span style="font-size:12.5px; font-weight:700; color:var(--mex-muted); width:100%;">🌐 Fuentes consultadas en Google:</span>
          ${sources.slice(0, 5).map(s => `<a class="source-link" href="${escapeHtml(s.url)}" target="_blank" rel="noopener">↗ ${escapeHtml(s.title)}</a>`).join('')}
        </div>`;
    }

    async function triggerAssistant(actionType, customText = '') {
      if (isAssistantBusy) {
        showToast('Espere un momento, el asistente está terminando su consulta...');
        return;
      }
      if (!window.MexAhoyAI) {
        alert('El módulo de Firebase AI Logic todavía se está cargando. Intente de nuevo en un segundo.');
        return;
      }

      const labels = {
        historical: '🏛️ Investigar contexto histórico de este artículo',
        political: '🗳️ Investigar contexto político actual de este artículo',
        facts: '🔍 Verificar datos, fechas y leyes clave',
        polish: '✨ Pulir redacción en inglés conservando mi voz',
        headlines: '📰 Sugerir 3 titulares naturales para este artículo',
        fix_cliches: '🧹 Limpiar clichés de IA y guiones largos de este artículo',
        custom: customText
      };

      const history = loadArticleHistory(currentArticleId);
      history.push({
        role: 'user',
        displayText: labels[actionType] || customText,
        rawText: customText || labels[actionType]
      });
      saveArticleHistory(currentArticleId, history);
      renderAssistantThread();

      // Burbuja de streaming en vivo
      const thread = document.getElementById('assistant-thread');
      const liveBubble = document.createElement('div');
      liveBubble.className = 'msg-ai';
      liveBubble.innerHTML = `<strong style="color:var(--mex-green);">🔎 Investigando con Gemini (${window.MexAhoyAI.getSelectedModelId() === 'gemini-4-neon' ? 'Neón' : 'Carbón'}) y Google Search...</strong><div id="live-stream-text" style="margin-top:8px; white-space:pre-line; color:#334;"></div>`;
      thread.appendChild(liveBubble);
      thread.scrollTop = thread.scrollHeight;

      isAssistantBusy = true;
      try {
        const currentArticleSnapshot = {
          id: currentArticleId,
          title: document.getElementById('ed-title').value.trim(),
          subtitle: document.getElementById('ed-subtitle').value.trim(),
          category: document.getElementById('ed-category').value,
          author: document.getElementById('ed-author').value.trim(),
          date: document.getElementById('ed-date').value.trim(),
          body: document.getElementById('ed-body').value.trim()
        };

        const result = await window.MexAhoyAI.askArticleAssistant({
          article: currentArticleSnapshot,
          actionType,
          userQuestion: customText,
          history: history.slice(0, -1),
          onStreamUpdate: (partial) => {
            const box = document.getElementById('live-stream-text');
            if (box) {
              box.textContent = partial;
              thread.scrollTop = thread.scrollHeight;
            }
          }
        });

        history.push({
          role: 'assistant',
          rawText: result.rawText,
          explanation: result.explanation,
          insertText: result.insertText,
          headlines: result.headlines,
          suggestedSubtitle: result.suggestedSubtitle,
          sources: result.sources || []
        });
        saveArticleHistory(currentArticleId, history);
        renderAssistantThread();
      } catch (err) {
        console.error('Firebase AI Logic error:', err);
        const msg = String(err && err.message ? err.message : err);
        liveBubble.style.borderColor = '#e57373';
        liveBubble.style.background = '#fff5f5';
        liveBubble.innerHTML = `
          <strong style="color:var(--mex-red);">⚠️ No se pudo conectar todavía con Firebase AI Logic</strong>
          <p style="margin:8px 0; font-size:15px; line-height:1.5;">
            Detalle técnico: <code>${escapeHtml(msg)}</code>
          </p>
          <p style="margin:8px 0; font-size:15px; line-height:1.5;">
            Si es la primera vez que usa el asistente, asegúrese de activar <strong>AI Logic (Gemini Developer API)</strong> en su proyecto <strong>mex-ahoy</strong>:
          </p>
          <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;">
            <a href="https://console.firebase.google.com/project/mex-ahoy/ailogic" target="_blank" class="btn btn-primary btn-sm">🔗 Activar Firebase AI Logic ↗</a>
            <button type="button" class="btn btn-gold btn-sm" onclick="changeAiModel('gemini-4-neon'); showToast('Cambiado a Gemini 4 Neón. Intente de nuevo.');">⚡ Probar con Gemini 4 Neón</button>
          </div>`;
      } finally {
        isAssistantBusy = false;
      }
    }

    function sendCustomQuestion() {
      const inp = document.getElementById('asst-input');
      const q = inp.value.trim();
      if (!q) return;
      inp.value = '';
      triggerAssistant('custom', q);
    }

    function insertFromAssistant(msgIdx, mode) {
      const history = loadArticleHistory(currentArticleId);
      const m = history[msgIdx];
      if (!m || !m.insertText) return;
      const clean = window.MexAhoyAI ? window.MexAhoyAI.enforceNoDashes(m.insertText) : m.insertText;
      const ta = document.getElementById('ed-body');
      if (mode === 'replace') {
        if (!confirm('¿Reemplazar el texto actual del artículo con esta versión pulida en inglés?')) return;
        ta.value = clean;
      } else {
        ta.value = ta.value.trim() ? ta.value.trimEnd() + '\n\n' + clean : clean;
      }
      flashElement(ta);
      ta.scrollTop = ta.scrollHeight;
      onEditorFieldChanged();
      showToast(mode === 'replace' ? '🔄 Texto actualizado en el editor.' : '➕ Párrafo insertado al final de su artículo.');
    }

    function applyAssistantHeadline(msgIdx, hIdx) {
      const m = loadArticleHistory(currentArticleId)[msgIdx];
      if (!m || !m.headlines || !m.headlines[hIdx]) return;
      const inp = document.getElementById('ed-title');
      inp.value = m.headlines[hIdx];
      flashElement(inp);
      onEditorFieldChanged();
      showToast('📰 ¡Titular aplicado a su artículo!');
    }

    function applyAssistantSubtitle(msgIdx) {
      const m = loadArticleHistory(currentArticleId)[msgIdx];
      if (!m || !m.suggestedSubtitle) return;
      const inp = document.getElementById('ed-subtitle');
      inp.value = m.suggestedSubtitle;
      flashElement(inp);
      onEditorFieldChanged();
      showToast('📝 ¡Subtítulo aplicado a su artículo!');
    }

    function speakAssistantMsg(msgIdx) {
      const m = loadArticleHistory(currentArticleId)[msgIdx];
      if (!m) return;
      speakText(m.explanation || m.insertText || m.rawText);
    }

    function copyAssistantMsg(msgIdx) {
      const m = loadArticleHistory(currentArticleId)[msgIdx];
      if (!m) return;
      const txt = m.insertText || m.explanation || m.rawText;
      navigator.clipboard.writeText(txt).then(() => showToast('📋 Copiado al portapapeles.'));
    }

    // =========================================================================
    // VOZ: LECTURA EN VOZ ALTA Y DICTADO POR MICRÓFONO
    // =========================================================================
    function speakText(text) {
      if (!('speechSynthesis' in window)) {
        showToast('Este navegador no soporta lectura en voz alta.');
        return;
      }
      window.speechSynthesis.cancel();
      const utter = new SpeechSynthesisUtterance(String(text || '').slice(0, 1800));
      utter.lang = /[áéíóúñ¿¡]/i.test(text) ? 'es-MX' : 'en-US';
      utter.rate = 0.92;
      utter.onend = () => { isSpeaking = false; updateSpeakBtn(); };
      isSpeaking = true;
      updateSpeakBtn();
      window.speechSynthesis.speak(utter);
      showToast('🔊 Leyendo en voz alta...');
    }

    function stopSpeaking() {
      if ('speechSynthesis' in window) window.speechSynthesis.cancel();
      isSpeaking = false;
      updateSpeakBtn();
    }

    function toggleSpeakArticle() {
      if (isSpeaking) {
        stopSpeaking();
        showToast('⏹️ Lectura detenida.');
      } else {
        const text = document.getElementById('ed-body').value.trim();
        if (!text) return showToast('El artículo aún no tiene texto.');
        speakText(text);
      }
    }

    function updateSpeakBtn() {
      const btn = document.getElementById('btn-speak-article');
      if (btn) btn.textContent = isSpeaking ? '⏹️ Detener lectura' : '🔊 Escuchar artículo';
    }

    function startVoiceDictation(target) {
      const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
      if (!SpeechRec) {
        alert('El dictado por micrófono funciona mejor en Google Chrome o Microsoft Edge.');
        return;
      }
      const btn = document.getElementById(target === 'editor' ? 'btn-dictate-editor' : 'btn-dictate-assistant');
      const rec = new SpeechRec();
      rec.lang = target === 'editor' ? 'en-US' : 'es-MX';
      rec.interimResults = false;
      rec.maxAlternatives = 1;

      btn.classList.add('pulse-mic');
      showToast('🎙️ Hable ahora... le estoy escuchando.');

      rec.onresult = (event) => {
        const transcript = event.results[0][0].transcript;
        if (target === 'editor') {
          insertEditorBlock((document.getElementById('ed-body').value.trim() ? '\n\n' : '') + transcript);
          showToast('✅ Dictado agregado al artículo.');
        } else {
          document.getElementById('asst-input').value = transcript;
          sendCustomQuestion();
        }
      };
      rec.onerror = () => showToast('No se detectó voz. Intente nuevamente.');
      rec.onend = () => btn.classList.remove('pulse-mic');
      rec.start();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================
    function flashElement(el) {
      el.classList.remove('flash-ok');
      void el.offsetWidth;
      el.classList.add('flash-ok');
    }

    function escapeHtml(str) {
      return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function escapeJs(str) {
      return String(str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    }
  </script>
  <?php endif; ?>
</body>
</html>
