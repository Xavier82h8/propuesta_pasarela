<?php
session_start();

// Habilitar errores para depuración (eliminar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'functions.php';
// Requerir la librería de Stripe
require 'vendor/autoload.php';
use Stripe\Stripe;

// Configuración de claves
$stripeLiveSecretKey = getenv('STRIPE_LIVE_SECRET_KEY') ?: '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';
$stripeLivePublishableKey = getenv('STRIPE_LIVE_PUBLISHABLE_KEY') ?: 'pk_live_51R76cwH0kpgdEo6UfUYi7RSqtUEdXjmQENykmNIVb9M5wdDpNQQWYoOgnqZKSqb2YbSTLbqKZ0ooy6A2RxVrKMHP00aXXfV7EG';

Stripe::setApiKey($stripeLiveSecretKey);

$priceIds = [
    'basico' => ['mensual' => 'price_1S6GZtH0kpgdEo6U0tTbmK07', 'anual' => 'price_1RGekOH0kpgdEo6UTK8saFZ2'],
    'profesional' => ['mensual' => 'price_1S6GaKH0kpgdEo6UGdIKRfQc', 'anual' => 'price_1S6GfpH0kpgdEo6U3WQkT6mS'],
    'empresarial' => ['mensual' => 'price_1S6GalH0kpgdEo6UVlBbV3ka', 'anual' => 'price_1S6GgmH0kpgdEo6U0oz70klC']
];

$plan = strtolower($_GET['plan'] ?? '');
$period = strtolower($_GET['period'] ?? 'mensual');
$currency = strtolower($_GET['currency'] ?? 'mxn');

// Normalizar período para consistencia
if ($period === 'monthly') {
    $period = 'mensual';
} elseif ($period === 'annual') {
    $period = 'anual';
}

// Si es plan Básico, redirigir a activación gratis
if ($plan === 'basico') {
    header("Location: https://yopracticando.com/activate_free_plan.php?plan=$plan&period=$period&user_id=" . urlencode($_SESSION['usuario_id'] ?? 'user123') . "&email=" . urlencode($_SESSION['usuario_email'] ?? ''));
    exit();
}

if (!array_key_exists($plan, $priceIds) || !in_array($period, ['mensual', 'anual']) || $currency !== 'mxn') {
    header("Location: https://yopracticando.com/planes.php?error=1");
    exit();
}

// Obtener precio dinámico desde Stripe
$priceId = $priceIds[$plan][$period];
$price = \Stripe\Price::retrieve($priceId);
$displayPrice = '$' . number_format($price->unit_amount / 100, 2) . ' MXN';
$periodText = ($period === 'anual') ? 'anual' : 'mensual';
$planName = ucfirst(str_replace(['basico', 'profesional', 'empresarial'], ['Básico', 'Profesional', 'Empresarial'], $plan));

// Guardar datos en sesión
$_SESSION['usuario_nombre'] = $_SESSION['usuario_nombre'] ?? '';
$_SESSION['usuario_email'] = $_SESSION['usuario_email'] ?? '';
$_SESSION['usuario_telefono'] = $_SESSION['usuario_telefono'] ?? '';
$_SESSION['usuario_compania'] = $_SESSION['usuario_compania'] ?? '';
$_SESSION['usuario_direccion'] = $_SESSION['usuario_direccion'] ?? '';
$_SESSION['usuario_pais'] = $_SESSION['usuario_pais'] ?? '';
$_SESSION['plan'] = $plan;
$_SESSION['period'] = $period;
$_SESSION['price'] = $displayPrice;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>YoPracticando - Pago Seguro</title>
  <script src="https://js.stripe.com/v3/"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="icon" href="./assets/Fav-Icon/Icon_YoPracticando.png" type="image/png">
  <style>
  /* =========================
     VARIABLES DE LAYOUT / THEME
     ========================= */
  :root{
    /* Sidebar (mismo diseño que “Ajustes”) */
    --sb-lg: 250px;          /* ancho expandido desktop */
    --sb-sm: 70px;           /* ancho colapsado */

    /* Userbar fija */
    --ub-h-desktop: 72px;    /* altura userbar en desktop */
    --ub-h-mobile: 56px;     /* altura userbar en móvil */
    --ub-h: var(--ub-h-desktop);

    /* Paleta / sombras */
    --primary-gradient: linear-gradient(135deg, #0066ff 0%, #00c6ff 100%);
    --accent-gradient:  linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --premium-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --dark-gradient:    linear-gradient(135deg, #2a2a5a 0%, #1a1a3a 100%);
    --error-gradient:   linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    --shadow-light: 0 8px 32px rgba(0, 0, 0, 0.1);
    --shadow-heavy: 0 20px 60px rgba(0, 0, 0, 0.15);
  }

  /* =========================
     RESETEOS BÁSICOS
     ========================= */
  *{ margin:0; padding:0; box-sizing:border-box; }
  body{
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #f5f7ff 0%, #e8ecff 100%);
    min-height: 100vh;
    color: #333;
    line-height: 1.6;
    overflow-x: hidden;
  }

  /* =====================================================
     CONTENEDOR QUE RESPETA SIDEBAR + USERBAR (patrón AJUSTES)
     - Soporta dos clases para no tocar HTML: .contenedor-principal y .main-with-sidebar
     ===================================================== */
  .contenedor-principal,
  .main-with-sidebar{
    position: absolute;
    left: var(--sb-sm);   /* 70px por defecto */
    right: 0;
    top: 0;
    min-height: 100vh;
    transition: left .3s ease-in-out;
    width: auto;
    padding: 20px;
    /* Hueco para NO quedar bajo la userbar fija */
    padding-top: calc(var(--ub-h) + 16px);
  }

  /* Sidebar expandida por hover (como en Ajustes) */
  .sidebar:hover + .contenedor-principal{ left: var(--sb-lg); }
  /* Si hay nodos entre medias o usas otra clase envolvente */
  .sidebar:hover ~ .main-with-sidebar{ left: var(--sb-lg); }

  /* Si tu sidebar se abre con clase, descomenta:
  .sidebar.is-open + .contenedor-principal,
  .sidebar.is-open ~ .main-with-sidebar{ left: var(--sb-lg); }
  */

  /* Tablet: mantener colapsada */
  @media (max-width: 1024px){
    .contenedor-principal,
    .main-with-sidebar{ left: var(--sb-sm); }
  }

  /* Móvil: sidebar overlay y userbar más baja */
  @media (max-width: 640px){
    :root{ --ub-h: var(--ub-h-mobile); }
    .contenedor-principal,
    .main-with-sidebar{
      position: static;
      padding-top: calc(var(--ub-h) + 12px);
      padding-left: 16px; padding-right: 16px;
    }
  }

  /* Wrapper interior (ancho de contenido) */
  .container{ max-width: 1200px; margin: 0 auto; padding: 0; }

  /* =========================
     GRID PRINCIPAL DE LA PÁGINA
     ========================= */
  .main-container{
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: flex-start;
    min-height: calc(100vh - 40px);
  }

  .left-section, .right-section{
    background: #fff;
    border-radius: 24px;
    padding: 40px;
    box-shadow: var(--shadow-heavy);
    border: 2px solid rgba(0, 102, 255, 0.08);
    transition: border-color .3s ease;
  }
  .left-section:hover, .right-section:hover{ border-color: rgba(0, 102, 255, 0.15); }
  .left-section{ min-height: 600px; }

  /* Resumen fijo en columna derecha respetando userbar */
  .right-section{
    position: sticky;
    top: calc(var(--ub-h) + 20px);
    height: fit-content;
    min-height: 400px;
  }

  /* =========================
     IDENTIDAD / ENCABEZADOS
     ========================= */
  .logo{ display:flex; align-items:center; gap:12px; margin-bottom:40px; }
  .logo-icon{
    width:48px; height:48px; background: var(--primary-gradient); border-radius:14px;
    display:flex; align-items:center; justify-content:center; color:#fff; font-weight:900; font-size:24px;
    box-shadow: 0 8px 24px rgba(0,102,255,.35); position:relative; overflow:hidden;
  }
  .logo-icon::before{
    content:''; position:absolute; top:0; left:-100%; width:100%; height:100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.4), transparent);
    animation: shine 3s infinite;
  }
  @keyframes shine{ 0%{left:-100%;} 100%{left:100%;} }

  .logo-text{
    font-size: 26px; font-weight: 900;
    background: linear-gradient(135deg, #0066ff 0%, #00c6ff 50%, #4facfe 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip:text; letter-spacing:-.5px;
  }

  .main-title{ font-size: clamp(26px, 4vw, 34px); font-weight: 800; color:#2a2a5a; margin-bottom:12px; line-height:1.2; letter-spacing:-.5px; }
  .subtitle{ font-size:16px; color:#666; margin-bottom:40px; line-height:1.7; }

  /* =========================
     PROGRESS BAR
     ========================= */
  .progress-bar{
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius:20px; padding:28px; margin-bottom:36px; box-shadow: 0 4px 16px rgba(0,0,0,.06);
    border:2px solid rgba(0,102,255,.08);
  }
  .progress-title{ font-size:18px; font-weight:700; margin-bottom:24px; text-align:center; color:#2a2a5a; }
  .progress-steps{ display:flex; justify-content:space-between; align-items:center; position:relative; padding:0 10px; }
  .progress-line{ position:absolute; top:20px; left:50px; right:50px; height:4px; background:#e5e7eb; border-radius:2px; }
  .progress-line-fill{ height:100%; background: var(--primary-gradient); border-radius:2px; width:0; transition: width .6s cubic-bezier(.4,0,.2,1); box-shadow: 0 0 12px rgba(0,102,255,.5); }
  .step{
    background:#fff; border:3px solid #e5e7eb; border-radius:50%; width:44px; height:44px; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:16px; color:#9ca3af; position:relative; z-index:2; transition: all .4s cubic-bezier(.4,0,.2,1);
  }
  .step.active{ border-color:#0066ff; background: var(--primary-gradient); color:#fff; box-shadow: 0 6px 16px rgba(0,102,255,.35); transform: scale(1.1); }
  .step.completed{ border-color:#11998e; background: var(--success-gradient); color:#fff; box-shadow: 0 6px 16px rgba(17,153,142,.35); }
  .step-label{ position:absolute; top:56px; left:50%; transform: translateX(-50%); font-size:13px; font-weight:600; color:#6b7280; white-space:nowrap; transition: color .3s ease; }

  /* =========================
     SECCIONES / FORM
     ========================= */
  .section-header{ display:flex; align-items:center; gap:14px; margin-bottom:28px; margin-top:48px; }
  .section-header:first-of-type{ margin-top:0; }
  .section-icon{ width:40px; height:40px; background: var(--primary-gradient); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; box-shadow: 0 6px 16px rgba(0,102,255,.3); }
  .section-title{ font-size:22px; font-weight:700; color:#2a2a5a; }

  .form-grid{ display:grid; grid-template-columns: 1fr 1fr; gap:24px; margin-bottom:24px; }
  .form-grid.full{ grid-template-columns: 1fr; }
  .input-group{ position:relative; }
  .input-label{ display:block; font-size:14px; font-weight:600; color:#374151; margin-bottom:10px; letter-spacing:.2px; }
  .input-field{
    width:100%; padding:16px 18px; border:2px solid #e5e7eb; border-radius:14px; font-size:16px; font-family:inherit; transition: all .3s cubic-bezier(.4,0,.2,1); background:#f9fafb;
  }
  textarea.input-field{ min-height:110px; resize:vertical; line-height:1.6; }
  .input-field:focus{ outline:none; border-color:#0066ff; background:#fff; box-shadow: 0 0 0 4px rgba(0,102,255,.1); transform: translateY(-1px); }
  .input-field.error{ border-color:#ef4444; background:#fef2f2; }
  .error-message{ color:#ef4444; font-size:13px; margin-top:8px; display:flex; align-items:center; gap:6px; font-weight:500; opacity:0; animation: fadeIn .3s ease forwards; }
  @keyframes fadeIn{ to{ opacity:1; } }

  /* =========================
     SELECT PERSONALIZADO (PAÍS)
     ========================= */
  .custom-select{ position:relative; display:block; width:100%; }
  .custom-select-trigger{
    width:100%; padding:16px 18px; border:2px solid #e5e7eb; border-radius:14px; font-size:16px; background:#f9fafb; cursor:pointer;
    display:flex; align-items:center; justify-content:space-between; transition: all .3s cubic-bezier(.4,0,.2,1);
  }
  .custom-select-trigger:hover{ border-color:#0066ff; background:#fff; }
  .custom-select-trigger.open{ border-color:#0066ff; background:#fff; box-shadow: 0 0 0 4px rgba(0,102,255,.1); transform: translateY(-1px); }
  .selected-country{ display:flex; align-items:center; gap:10px; font-size:16px; }
  .custom-select-options{
    position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:2px solid #0066ff; border-radius:14px; max-height:240px; overflow-y:auto; z-index:100;
    opacity:0; transform: translateY(-10px); visibility:hidden; transition: all .3s cubic-bezier(.25,.8,.25,1); box-shadow: 0 12px 32px rgba(0,0,0,.12);
  }
  .custom-select-options.open{ opacity:1; transform: translateY(0); visibility: visible; }
  .custom-select-option{ display:flex; align-items:center; gap:10px; padding:14px 18px; cursor:pointer; transition: all .2s ease; border-bottom:1px solid #f3f4f6; font-size:15px; }
  .custom-select-option:hover{ background: linear-gradient(90deg, #f0f9ff 0%, #e0f2fe 100%); padding-left:22px; }
  .custom-select-option:last-child{ border-bottom:none; }

  /* =========================
     STRIPE CARD
     ========================= */
  .card-container{ background: linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); border:2px solid #e5e7eb; border-radius:14px; padding:24px; margin-bottom:24px; transition: all .3s ease; }
  .card-container.focused{ border-color:#0066ff; background:#fff; box-shadow: 0 0 0 4px rgba(0,102,255,.1); transform: translateY(-2px); }
  #card-element{ padding:16px 0; border:none; font-size:16px; color:#1a1a1a; }

  /* =========================
     RESUMEN / PAGO
     ========================= */
  .payment-summary{
    background: var(--primary-gradient); border-radius:20px; padding:32px; margin-bottom:32px; color:#fff; box-shadow: 0 16px 48px rgba(0,102,255,.35); position:relative; overflow:hidden;
  }
  .payment-summary::before{
    content:''; position:absolute; inset:0; background: linear-gradient(135deg, rgba(255,255,255,.15) 0%, transparent 100%); pointer-events:none;
  }
  .summary-title{ font-size:22px; font-weight:700; color:#fff; margin-bottom:24px; position:relative; }
  .summary-item{ display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; font-size:16px; color:rgba(255,255,255,.95); }
  .summary-item.total{ border-top:2px solid rgba(255,255,255,.35); padding-top:20px; margin-top:20px; font-size:24px; font-weight:800; color:#fff; }

  .btn{
    width:100%; padding:20px 28px; border:none; border-radius:14px; font-size:17px; font-weight:700; cursor:pointer; transition: all .3s cubic-bezier(.4,0,.2,1);
    display:flex; align-items:center; justify-content:center; gap:10px; font-family:inherit; text-decoration:none; margin-bottom:16px; position:relative; overflow:hidden;
  }
  .btn-primary{ background: var(--primary-gradient); color:#fff; box-shadow: 0 10px 28px rgba(0,102,255,.4); }
  .btn-primary::before{ content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent); transition:left .5s ease; }
  .btn-primary:hover:not(:disabled){ transform: translateY(-3px); box-shadow: 0 16px 40px rgba(0,102,255,.5); }
  .btn-primary:hover:not(:disabled)::before{ left:100%; }
  .btn-primary:active:not(:disabled){ transform: translateY(-1px); }
  .btn:disabled{ opacity:.65; cursor:not-allowed; transform:none !important; }

  .success-message{ background: var(--success-gradient); border-radius:16px; padding:24px; margin-bottom:28px; display:none; color:#fff; box-shadow: 0 10px 32px rgba(17,153,142,.4); }
  .success-message.active{ display:block; animation: slideUp .5s cubic-bezier(.4,0,.2,1); }
  @keyframes slideUp{ from{ transform: translateY(30px); opacity:0; } to{ transform: translateY(0); opacity:1; } }
  .success-title{ display:flex; align-items:center; gap:10px; font-size:20px; font-weight:700; margin-bottom:8px; }

  .security-badges{ display:flex; align-items:center; justify-content:center; gap:20px; margin-top:28px; padding-top:28px; border-top:2px solid #e5e7eb; flex-wrap:wrap; }
  .security-badge{ display:flex; align-items:center; gap:8px; font-size:13px; color:#6b7280; font-weight:600; }
  .security-badge i{ color:#0066ff; font-size:16px; }

  /* =========================
     LOADER / MODAL
     ========================= */
  .loader{ position:fixed; inset:0; background: rgba(255,255,255,.97); backdrop-filter: blur(12px); display:none; align-items:center; justify-content:center; z-index:1000; }
  .loader.active{ display:flex; }
  .spinner{ width:56px; height:56px; border:5px solid #f3f4f6; border-top:5px solid #0066ff; border-radius:50%; animation: spin .8s linear infinite; }
  @keyframes spin{ 0%{ transform: rotate(0deg); } 100%{ transform: rotate(360deg); } }

  .declined-animation{ animation: shake .5s ease; }
  @keyframes shake{ 0%,100%{ transform: translateX(0); } 25%{ transform: translateX(-10px); } 75%{ transform: translateX(10px); } }
  .unprocessed-animation{ animation: pulse .5s ease; }
  @keyframes pulse{ 0%,100%{ transform: scale(1); } 50%{ transform: scale(1.05); } }

  .modal{ display:none; position:fixed; inset:0; background: rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:2000; }
  .modal-content{ background:#fff; padding:32px; border-radius:16px; text-align:center; max-width:400px; box-shadow: var(--shadow-heavy); position:relative; }
  .modal-close{ position:absolute; top:16px; right:16px; background:none; border:none; font-size:24px; cursor:pointer; color:#666; }
  .modal-title{ font-size:24px; font-weight:700; color:#ef4444; margin-bottom:16px; display:flex; align-items:center; justify-content:center; gap:8px; }
  .modal-message{ font-size:16px; color:#333; margin-bottom:24px; }
  .modal-button{ padding:12px 24px; background: var(--primary-gradient); color:#fff; border:none; border-radius:12px; font-weight:600; cursor:pointer; transition: all .3s ease; }
  .modal-button:hover{ transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,102,255,.3); }

  /* =========================
     RESPONSIVE
     ========================= */
  @media (max-width: 968px){
    .main-container{ grid-template-columns: 1fr; gap:24px; }
    .left-section, .right-section{ padding:28px; position:static; height:auto; }
    .form-grid{ grid-template-columns: 1fr; gap:20px; }
    .progress-steps{ padding:0 10px; }
    .progress-line{ left:40px; right:40px; }
    .step{ width:38px; height:38px; font-size:14px; }
    .step-label{ font-size:11px; top:50px; }
  }
  @media (max-width: 480px){
    .container{ padding:0; }
    .left-section, .right-section{ padding:20px; border-radius:20px; }
    .logo-icon{ width:40px; height:40px; font-size:20px; }
    .logo-text{ font-size:22px; }
    .progress-bar{ padding:20px 16px; }
    .step{ width:32px; height:32px; font-size:12px; }
    .progress-line{ left:30px; right:30px; }
  }
</style>


</head>
<body>
  <?php include './userbar.php'; ?>
  <?php include './sidebar.php'; ?>  

  <!-- Contenido que respeta la sidebar -->
  <div class="main-with-sidebar">
    <div class="container">
      <div class="main-container">
        <!-- Sección izquierda - Formulario -->
        <div class="left-section">
          <div class="logo">
            <div class="logo-icon">Y</div>
            <div class="logo-text">YoPracticando</div>
          </div>

          <h1 class="main-title">Completa tu suscripción</h1>
          <p class="subtitle">Accede a los mejores estudiantes universitarios y construye tu programa de prácticas profesionales.</p>

          <!-- Progress Bar -->
          <div class="progress-bar">
            <h3 class="progress-title">Proceso de Suscripción</h3>
            <div class="progress-steps">
              <div class="progress-line">
                <div class="progress-line-fill" id="progress-fill"></div>
              </div>
              <div class="step completed" id="step-1">
                <i class="fas fa-crown"></i>
                <div class="step-label">Plan</div>
              </div>
              <div class="step active" id="step-2">
                <i class="fas fa-user"></i>
                <div class="step-label">Información</div>
              </div>
              <div class="step" id="step-3">
                <i class="fas fa-credit-card"></i>
                <div class="step-label">Pago</div>
              </div>
              <div class="step" id="step-4">
                <i class="fas fa-check"></i>
                <div class="step-label">Confirmación</div>
              </div>
            </div>
          </div>

          <div class="success-message" id="success-message">
            <div class="success-title">
              <i class="fas fa-check-circle"></i>
              ¡Pago procesado exitosamente!
            </div>
            <p style="font-size: 15px; margin-top: 8px; opacity: 0.95;">Tu suscripción ha sido activada. Redirigiendo...</p>
          </div>

          <!-- Información Personal -->
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-user"></i></div>
            <h2 class="section-title">Información Personal</h2>
          </div>

          <div class="form-grid">
            <div class="input-group">
              <label class="input-label">Nombre completo *</label>
              <input type="text" id="nombre" class="input-field" placeholder="Ej: Juan Pérez" value="<?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?>">
              <div id="nombre-error" class="error-message"></div>
            </div>
            <div class="input-group">
              <label class="input-label">Correo electrónico *</label>
              <input type="email" id="email" class="input-field" placeholder="tu@email.com" value="<?php echo htmlspecialchars($_SESSION['usuario_email'] ?? ''); ?>">
              <div id="email-error" class="error-message"></div>
            </div>
          </div>

          <div class="form-grid">
            <div class="input-group country-select">
              <label class="input-label">País *</label>
              <div class="custom-select">
                <div class="custom-select-trigger" id="country-trigger">
                  <div class="selected-country">
                    <span style="font-size: 20px;">🌎</span>
                    <span>Selecciona un país</span>
                  </div>
                  <i class="fas fa-chevron-down"></i>
                </div>
                <div class="custom-select-options" id="country-options">
                  <div class="custom-select-option" data-value="México" data-lada="+52"><span style="font-size: 20px;">🇲🇽</span><span>México</span></div>
                  <div class="custom-select-option" data-value="Estados Unidos" data-lada="+1"><span style="font-size: 20px;">🇺🇸</span><span>Estados Unidos</span></div>
                  <div class="custom-select-option" data-value="Canadá" data-lada="+1"><span style="font-size: 20px;">🇨🇦</span><span>Canadá</span></div>
                  <div class="custom-select-option" data-value="España" data-lada="+34"><span style="font-size: 20px;">🇪🇸</span><span>España</span></div>
                  <div class="custom-select-option" data-value="Argentina" data-lada="+54"><span style="font-size: 20px;">🇦🇷</span><span>Argentina</span></div>
                  <div class="custom-select-option" data-value="Colombia" data-lada="+57"><span style="font-size: 20px;">🇨🇴</span><span>Colombia</span></div>
                  <div class="custom-select-option" data-value="Chile" data-lada="+56"><span style="font-size: 20px;">🇨🇱</span><span>Chile</span></div>
                  <div class="custom-select-option" data-value="Perú" data-lada="+51"><span style="font-size: 20px;">🇵🇪</span><span>Perú</span></div>
                  <div class="custom-select-option" data-value="Brasil" data-lada="+55"><span style="font-size: 20px;">🇧🇷</span><span>Brasil</span></div>
                  <div class="custom-select-option" data-value="Venezuela" data-lada="+58"><span style="font-size: 20px;">🇻🇪</span><span>Venezuela</span></div>
                </div>
                <input type="hidden" id="pais" name="pais" value="<?php echo htmlspecialchars($_SESSION['usuario_pais'] ?? ''); ?>">
              </div>
              <div id="pais-error" class="error-message"></div>
            </div>
            <div class="input-group">
              <label class="input-label">Teléfono *</label>
              <input type="tel" id="telefono" class="input-field" placeholder="Ej: 5551234567" maxlength="15" value="<?php echo htmlspecialchars($_SESSION['usuario_telefono'] ?? ''); ?>">
              <div id="telefono-error" class="error-message"></div>
            </div>
          </div>

          <!-- Información Empresarial -->
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-building"></i></div>
            <h2 class="section-title">Información Empresarial</h2>
          </div>

          <div class="form-grid">
            <div class="input-group">
              <label class="input-label">Nombre de la empresa *</label>
              <input type="text" id="compania" class="input-field" placeholder="Nombre de tu empresa" value="<?php echo htmlspecialchars($_SESSION['usuario_compania'] ?? ''); ?>">
              <div id="compania-error" class="error-message"></div>
            </div>
          </div>

          <div class="form-grid full">
            <div class="input-group">
              <label class="input-label">Dirección completa *</label>
              <textarea id="direccion" class="input-field" placeholder="Calle, número, colonia, ciudad, estado, código postal" rows="3"><?php echo htmlspecialchars($_SESSION['usuario_direccion'] ?? ''); ?></textarea>
              <div id="direccion-error" class="error-message"></div>
            </div>
          </div>

          <!-- Información de Pago -->
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-credit-card"></i></div>
            <h2 class="section-title">Información de Pago</h2>
          </div>

          <div class="card-container" id="card-container">
            <div id="card-element"></div>
          </div>
          <div id="card-errors" class="error-message"></div>

          <div class="security-badges">
            <div class="security-badge"><i class="fas fa-shield-alt"></i><span>Pago 100% seguro</span></div>
            <div class="security-badge"><i class="fas fa-lock"></i><span>Encriptación SSL</span></div>
            <div class="security-badge"><i class="fab fa-stripe"></i><span>Powered by Stripe</span></div>
          </div>
        </div>

        <!-- Sección derecha - Resumen y pago -->
        <div class="right-section">
          <div class="payment-summary">
            <h3 class="summary-title">Resumen del pedido</h3>
            <div class="summary-item" id="subscription-item">
              <span id="subscription-text">Suscripción <?php echo $periodText; ?> (<?php echo $planName; ?>)</span>
              <span id="subscription-price"><?php echo $displayPrice; ?></span>
            </div>
            <div class="summary-item">
              <span>Impuestos</span>
              <span>$0.00 MXN</span>
            </div>
            <div class="summary-item total">
              <span>Total</span>
              <span id="total-price"><?php echo $displayPrice; ?></span>
            </div>
          </div>

          <button class="btn btn-primary" id="pay-button">
            <i class="fas fa-lock"></i>
            <span id="pay-button-text">Pagar <?php echo $displayPrice; ?></span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="loader" id="loader"><div class="spinner"></div></div>

  <!-- Modal para errores -->
  <div class="modal" id="error-modal">
    <div class="modal-content">
      <button class="modal-close" onclick="closeModal()">&times;</button>
      <div class="modal-title"><i class="fas fa-exclamation-triangle"></i> Error en el Pago</div>
      <p class="modal-message" id="modal-message"></p>
      <button class="modal-button" onclick="closeModal()">Entendido</button>
    </div>
  </div>

  <script>
    const stripe = Stripe('<?php echo $stripeLivePublishableKey; ?>');
    let cardElement = null;

    // Inicializar Stripe Elements
    function initializeStripeElements() {
      const elements = stripe.elements();
      const cardStyle = {
        base: { fontSize: '16px', color: '#1a1a1a', fontFamily: 'Inter, sans-serif', '::placeholder': { color: '#9ca3af' } },
        invalid: { color: '#ef4444' },
      };
      try {
        cardElement = elements.create('card', { style: cardStyle });
        cardElement.mount('#card-element');
        setupCardEvents();
      } catch (error) {
        console.error('Error initializing Stripe Elements:', error);
        document.getElementById('card-errors').innerHTML = `<i class="fas fa-exclamation-triangle"></i> Error al cargar el formulario de pago.`;
      }
    }
    initializeStripeElements();

    // Eventos del elemento de tarjeta
    function setupCardEvents() {
      const cardContainer = document.getElementById('card-container');
      cardElement.on('focus', () => cardContainer.classList.add('focused'));
      cardElement.on('blur',  () => cardContainer.classList.remove('focused'));
      cardElement.on('change', (event) => {
        const errorElement = document.getElementById('card-errors');
        if (event.error) errorElement.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${event.error.message}`;
        else errorElement.textContent = '';
      });
    }

    // Selector de país
    function setupCountrySelector() {
      const trigger = document.getElementById('country-trigger');
      const options = document.getElementById('country-options');
      const hiddenInput = document.getElementById('pais');
      const telefonoInput = document.getElementById('telefono');
      const initialCountry = '<?php echo htmlspecialchars($_SESSION['usuario_pais'] ?? ''); ?>';

      if (initialCountry) {
        const preselectedOption = options.querySelector(`[data-value="${initialCountry}"]`);
        if (preselectedOption) {
          const emoji = preselectedOption.querySelector('span:first-child').textContent;
          const value = preselectedOption.dataset.value;
          trigger.querySelector('.selected-country').innerHTML = `<span style="font-size: 20px;">${emoji}</span><span>${value}</span>`;
          hiddenInput.value = value;
          const currentPhone = telefonoInput.value.replace(/^\+\d+\s*/, '');
          if (currentPhone) telefonoInput.value = preselectedOption.dataset.lada + ' ' + currentPhone;
        }
      }

      trigger.addEventListener('click', () => {
        trigger.classList.toggle('open');
        options.classList.toggle('open');
      });
      document.addEventListener('click', (e) => {
        if (!trigger.contains(e.target) && !options.contains(e.target)) {
          trigger.classList.remove('open');
          options.classList.remove('open');
        }
      });
      options.querySelectorAll('.custom-select-option').forEach(option => {
        option.addEventListener('click', () => {
          const value = option.dataset.value;
          const lada = option.dataset.lada;
          const emoji = option.querySelector('span:first-child').textContent;
          trigger.querySelector('.selected-country').innerHTML = `<span style="font-size: 20px;">${emoji}</span><span>${value}</span>`;
          hiddenInput.value = value;
          const currentPhone = telefonoInput.value.replace(/^\+\d+\s*/, '');
          telefonoInput.value = lada + (currentPhone ? ' ' + currentPhone : '');
          trigger.classList.remove('open');
          options.classList.remove('open');
          checkFormCompletion();
        });
      });
    }

    // Validación del formulario
    function validateForm() {
      const errors = {};
      const fields = {
        nombre: document.getElementById('nombre').value.trim(),
        compania: document.getElementById('compania').value.trim(),
        pais: document.getElementById('pais').value.trim(),
        direccion: document.getElementById('direccion').value.trim(),
        telefono: document.getElementById('telefono').value.trim(),
        email: document.getElementById('email').value.trim(),
      };

      const nameRegex = /^(?:[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ'-]*\s+){1,}[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ'-]*$/;
      if (!fields.nombre) errors.nombre = 'El nombre es obligatorio';
      else if (!nameRegex.test(fields.nombre)) errors.nombre = 'Ingresa un nombre válido';

      if (!fields.compania) errors.compania = 'El nombre de la empresa es obligatorio';
      if (!fields.pais) errors.pais = 'El país es obligatorio';
      if (!fields.direccion) errors.direccion = 'La dirección es obligatoria';
      if (!fields.telefono) errors.telefono = 'El teléfono es obligatorio';
      else if (!/^\+\d{1,3}\s?\d{8,12}$/.test(fields.telefono)) errors.telefono = 'Ingresa un teléfono válido con lada';
      if (!fields.email) errors.email = 'El email es obligatorio';
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fields.email)) errors.email = 'Ingresa un email válido';

      Object.keys(fields).forEach(field => {
        const errorElement = document.getElementById(`${field}-error`);
        const inputElement = document.getElementById(field);
        if (errors[field]) {
          errorElement.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${errors[field]}`;
          inputElement.classList.add('error');
        } else {
          errorElement.textContent = '';
          inputElement.classList.remove('error');
        }
      });

      return { isValid: Object.keys(errors).length === 0, fields };
    }

    // Progreso
    function updateProgress(step, progressWidth) {
      const steps = ['step-1', 'step-2', 'step-3', 'step-4'];
      steps.forEach((id, index) => {
        const stepElement = document.getElementById(id);
        stepElement.classList.remove('active', 'completed');
        if (index + 1 < step) stepElement.classList.add('completed');
        else if (index + 1 === step) stepElement.classList.add('active');
      });
      document.getElementById('progress-fill').style.width = `${progressWidth}%`;
    }

    function animateProgressBar(){ setTimeout(()=> updateProgress(2, 33), 300); }
    window.addEventListener('load', () => { animateProgressBar(); setupCountrySelector(); });

    function checkFormCompletion(){
      const { isValid } = validateForm();
      updateProgress(isValid ? 3 : 2, isValid ? 66 : 33);
    }
    ['nombre','compania','direccion','telefono','email'].forEach(id=>{
      const el = document.getElementById(id);
      el.addEventListener('input', checkFormCompletion);
      el.addEventListener('blur',  checkFormCompletion);
    });

    // Modal
    function showModal(message){
      document.getElementById('modal-message').textContent = message;
      document.getElementById('error-modal').style.display = 'flex';
    }
    function closeModal(){ document.getElementById('error-modal').style.display = 'none'; }
    window.closeModal = closeModal; // para el botón X inline

    // Crear Payment Intent
    async function createPaymentIntent(fields) {
      if (<?php echo $price->unit_amount; ?> < 1000) {
        throw new Error('El pago mínimo es de $10.00 MXN');
      }
      const payload = {
        user_id: '<?php echo $_SESSION['usuario_id'] ?? 'user123'; ?>',
        amount: <?php echo $price->unit_amount; ?>,
        currency: '<?php echo $currency; ?>',
        plan: '<?php echo $plan; ?>',
        period: '<?php echo $period; ?>',
        nombre: fields.nombre,
        compania: fields.compania,
        pais: fields.pais,
        direccion: fields.direccion,
        telefono: fields.telefono,
        email: fields.email,
      };
      const response = await fetch('https://yopracticando.com/pago.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
      });
      const text = await response.text();
      let data;
      try { data = text ? JSON.parse(text) : null; }
      catch (e) { throw new Error(`Respuesta del servidor inválida: ${text}`); }
      if (!data || !data.success) throw new Error(data?.error || 'Error al crear el Payment Intent');
      return { clientSecret: data.clientSecret, status: data.status };
    }

    // Pagar
    document.getElementById('pay-button').addEventListener('click', async () => {
      const { isValid, fields } = validateForm();
      if (!isValid || !cardElement) return;

      const button = document.getElementById('pay-button');
      const originalContent = button.innerHTML;
      button.disabled = true;
      document.getElementById('loader').classList.add('active');

      try{
        updateProgress(3, 66);

        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparando pago...';
        const { clientSecret, status } = await createPaymentIntent(fields);

        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando pago...';
        const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
          payment_method: {
            card: cardElement,
            billing_details: {
              name: fields.nombre,
              email: fields.email,
              phone: fields.telefono,
              address: {
                country: fields.pais === 'México' ? 'MX' :
                        fields.pais === 'Estados Unidos' ? 'US' :
                        fields.pais === 'Canadá' ? 'CA' :
                        fields.pais === 'España' ? 'ES' :
                        fields.pais === 'Argentina' ? 'AR' :
                        fields.pais === 'Colombia' ? 'CO' :
                        fields.pais === 'Chile' ? 'CL' :
                        fields.pais === 'Perú' ? 'PE' :
                        fields.pais === 'Brasil' ? 'BR' :
                        fields.pais === 'Venezuela' ? 'VE' : 'US',
                line1: fields.direccion,
              },
            },
          },
        });

        if (error) {
          let message = 'Pago no procesado. Intenta de nuevo.';
          if (error.type === 'card_error' && error.code === 'card_declined') {
            message = 'Tu tarjeta fue declinada. Por favor, verifica los detalles de la tarjeta o usa otra.';
          } else if (error.type === 'validation_error' && status === 'requires_action') {
            message = 'El pago requiere autenticación adicional. Por favor, completa el proceso en la ventana emergente.';
          } else {
            message += ' Detalle: ' + error.message;
          }
          showModal(message);
          const errorElement = document.getElementById('card-errors');
          errorElement.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
          errorElement.classList.add(error.code === 'card_declined' ? 'declined-animation' : 'unprocessed-animation');
          return;
        }

        if (paymentIntent && paymentIntent.status === 'succeeded') {
          updateProgress(4, 100);
          document.getElementById('success-message').classList.add('active');
          button.innerHTML = '<i class="fas fa-check"></i> ¡Pago exitoso!';
          button.style.background = 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)';

          const successMessage = document.getElementById('success-message');
          successMessage.innerHTML = `
            <div class="success-title"><i class="fas fa-check-circle"></i> ¡Pago procesado exitosamente!</div>
            <p style="font-size: 15px; margin-top: 8px; opacity: 0.95;">Tu suscripción ha sido activada. ID: ${paymentIntent.id}</p>
            <p style="font-size: 15px; margin-top: 8px; opacity: 0.95;"><i class="fas fa-spinner fa-spin"></i> Redirigiendo a tu panel en <span id="countdown">3</span> segundos...</p>
          `;
          let countdown = 3;
          const countdownElement = document.getElementById('countdown');
          const countdownInterval = setInterval(() => {
            countdown--;
            if (countdownElement) countdownElement.textContent = countdown;
            if (countdown <= 0) {
              clearInterval(countdownInterval);
              window.location.href = 'mis-vacantes.php';
            }
          }, 1000);
        } else if (paymentIntent && paymentIntent.status === 'requires_action') {
          showModal('Se requiere autenticación adicional. Por favor, sigue las instrucciones en la ventana emergente.');
        }
      } catch (error){
        console.error('Error en el pago:', error);
        showModal('Error inesperado: ' + error.message + '. Por favor, contacta soporte si persiste.');
      } finally {
        button.disabled = false;
        button.innerHTML = originalContent;
        document.getElementById('loader').classList.remove('active');
        setTimeout(() => {
          document.getElementById('card-errors').classList.remove('declined-animation', 'unprocessed-animation');
        }, 2000);
      }
    });
  </script>
</body>
</html>
