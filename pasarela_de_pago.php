<?php
session_start();

// Habilitar errores para depuración (eliminar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Requerir la librería de Stripe y funciones
// Asegúrate de que las rutas a estos archivos sean correctas
require 'vendor/autoload.php';
use Stripe\Stripe;

// Configuración de claves de Stripe
$stripeLiveSecretKey = getenv('STRIPE_LIVE_SECRET_KEY') ?: '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';
$stripeLivePublishableKey = getenv('STRIPE_LIVE_PUBLISHABLE_KEY') ?: 'pk_live_51R76cwH0kpgdEo6UfUYi7RSqtUEdXjmQENykmNIVb9M5wdDpNQQWYoOgnqZKSqb2YbSTLbqKZ0ooy6A2RxVrKMHP00aXXfV7EG';

Stripe::setApiKey($stripeLiveSecretKey);

// Definición de los IDs de precios de Stripe
$priceIds = [
    'basico' => ['mensual' => 'price_1S6GZtH0kpgdEo6U0tTbmK07', 'anual' => 'price_1RGekOH0kpgdEo6UTK8saFZ2'],
    'profesional' => ['mensual' => 'price_1S6GaKH0kpgdEo6UGdIKRfQc', 'anual' => 'price_1S6GfpH0kpgdEo6U3WQkT6mS'],
    'empresarial' => ['mensual' => 'price_1S6GalH0kpgdEo6UVlBbV3ka', 'anual' => 'price_1S6GgmH0kpgdEo6U0oz70klC']
];

// Obtener y normalizar los parámetros de la URL
$plan = strtolower($_GET['plan'] ?? 'empresarial'); // Default a empresarial si no se especifica
$period = strtolower($_GET['period'] ?? 'mensual');
$currency = strtolower($_GET['currency'] ?? 'mxn');

if ($period === 'monthly') $period = 'mensual';
if ($period === 'annual') $period = 'anual';

// Redirigir si el plan es básico (gratuito)
if ($plan === 'basico') {
    $userId = $_SESSION['usuario_id'] ?? 'user123';
    $userEmail = $_SESSION['usuario_email'] ?? '';
    header("Location: https://yopracticando.com/activate_free_plan.php?plan=$plan&period=$period&user_id=" . urlencode($userId) . "&email=" . urlencode($userEmail));
    exit();
}

// Validar que los parámetros sean correctos
if (!array_key_exists($plan, $priceIds) || !in_array($period, ['mensual', 'anual']) || $currency !== 'mxn') {
    header("Location: https://yopracticando.com/planes.php?error=invalid_plan");
    exit();
}

// Obtener el precio desde Stripe para mostrarlo
try {
    $priceId = $priceIds[$plan][$period];
    $price = \Stripe\Price::retrieve($priceId);
    $displayPrice = '$' . number_format($price->unit_amount / 100, 2);
    $priceAmount = $price->unit_amount;
    $displayCurrency = strtoupper($currency);
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Manejar error si el Price ID no existe en Stripe
    error_log("Stripe API error: " . $e->getMessage());
    header("Location: https://yopracticando.com/planes.php?error=price_not_found");
    exit();
}

$periodText = ($period === 'anual') ? 'Anual' : 'Mensual';
$planName = ucfirst($plan);

// Pre-llenar datos del usuario desde la sesión
$_SESSION['usuario_nombre'] = $_SESSION['usuario_nombre'] ?? '';
$_SESSION['usuario_email'] = $_SESSION['usuario_email'] ?? '';
$_SESSION['usuario_telefono'] = $_SESSION['usuario_telefono'] ?? '';
$_SESSION['usuario_compania'] = $_SESSION['usuario_compania'] ?? '';
$_SESSION['usuario_direccion'] = $_SESSION['usuario_direccion'] ?? '';
$_SESSION['usuario_pais'] = $_SESSION['usuario_pais'] ?? '🇲🇽 México'; // Default a México

// Guardar detalles del plan en sesión para el backend
$_SESSION['plan'] = $plan;
$_SESSION['period'] = $period;
$_SESSION['price_id'] = $priceId;
$_SESSION['amount'] = $priceAmount;
$_SESSION['currency'] = $currency;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completa tu suscripción - YoPracticando</title>
    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-blue: #4285f4;
            --primary-green: #34a853;
            --dark-text: #202124;
            --light-gray: #e0e0e0;
            --error-red: #ea4335;
            --warning-yellow: #fbbc05;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            position: relative;
            overflow-x: hidden;
        }
        .container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 420px; gap: 32px; position: relative; z-index: 1; }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(40px) saturate(180%);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 8px 16px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 1);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(60px); } to { opacity: 1; transform: translateY(0); } }
        .left-section { padding: 56px; }
        .main-title { font-size: 36px; font-weight: 800; margin-bottom: 16px; color: var(--dark-text); letter-spacing: -1px; }
        .subtitle { font-size: 17px; color: #5f6368; margin-bottom: 48px; line-height: 1.6; }
        .progress-container { margin-bottom: 48px; padding: 32px; background: linear-gradient(135deg, rgba(66, 133, 244, 0.05) 0%, rgba(52, 168, 83, 0.05) 100%); border-radius: 20px; border: 1px solid rgba(66, 133, 244, 0.1); }
        .steps { display: flex; justify-content: space-between; }
        .step { display: flex; flex-direction: column; align-items: center; gap: 12px; flex: 1; }
        .step-icon { width: 56px; height: 56px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; color: #9aa0a6; font-size: 22px; border: 3px solid var(--light-gray); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .step.completed .step-icon { background: var(--primary-green); border-color: var(--primary-green); color: white; }
        .step.active .step-icon { background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-green) 100%); border-color: var(--primary-blue); color: white; transform: scale(1.1); }
        .step-label { font-size: 14px; font-weight: 600; color: #5f6368; }
        .step.active .step-label { color: var(--dark-text); font-weight: 700; }
        .step-connector { height: 6px; background: rgba(224, 224, 224, 0.5); border-radius: 3px; position: relative; margin: -42px auto 24px; max-width: calc(100% - 180px); }
        .step-connector-fill { height: 100%; background: linear-gradient(90deg, var(--primary-blue) 0%, var(--primary-green) 100%); border-radius: 3px; width: 0%; transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
        .section { background: white; border-radius: 24px; padding: 40px; margin-bottom: 28px; }
        .section-header { display: flex; align-items: center; gap: 16px; margin-bottom: 32px; }
        .section-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #f1f3f4 0%, #e8eaed 100%); display: flex; align-items: center; justify-content: center; color: #5f6368; font-size: 20px; }
        .section-header.completed .section-icon { background: linear-gradient(135deg, #e6f4ea 0%, #d4ede0 100%); color: var(--primary-green); }
        .section-title { font-size: 22px; font-weight: 700; color: var(--dark-text); flex-grow: 1; }
        .section-complete-badge { background: linear-gradient(135deg, #e6f4ea 0%, #d4ede0 100%); color: var(--primary-green); padding: 8px 16px; border-radius: 24px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; opacity: 0; transform: scale(0.8); transition: all 0.3s ease; }
        .section-header.completed .section-complete-badge { opacity: 1; transform: scale(1); }
        .form-row { display: flex; gap: 24px; margin-bottom: 24px; }
        .input-group { flex: 1; position: relative; }
        .input-label { font-size: 14px; font-weight: 600; color: #5f6368; margin-bottom: 10px; display: block; }
        .input-field, .card-input-field { width: 100%; height: 56px; border: 2px solid #dadce0; border-radius: 14px; padding: 0 18px; font-size: 16px; color: var(--dark-text); transition: all 0.3s ease; background: white; font-weight: 500; }
        .card-input-field { display: flex; align-items: center; padding-right: 48px; }
        .input-field:focus, .card-input-field:focus-within { outline: none; border-color: var(--primary-blue); box-shadow: 0 0 0 4px rgba(66, 133, 244, 0.15); transform: translateY(-2px); }
        .input-field.invalid, .card-input-field.invalid { border-color: var(--error-red) !important; }
        .card-container { margin-bottom: 40px; perspective: 1500px; }
        .card-flip-container { position: relative; width: 100%; max-width: 460px; height: 290px; margin: 0 auto 40px; transition: transform 0.7s cubic-bezier(0.4, 0, 0.2, 1); transform-style: preserve-3d; }
        .card-flip-container.flipped { transform: rotateY(180deg); }
        .card-face { position: absolute; width: 100%; height: 100%; backface-visibility: hidden; border-radius: 24px; padding: 32px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); overflow: hidden; }
        .card-front { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-front.visa { background: linear-gradient(135deg, #1A1F71 0%, #0D47A1 100%); }
        .card-front.mastercard { background: linear-gradient(135deg, #EB001B 0%, #F79E1B 50%, #FF5F00 100%); }
        .card-front.amex { background: linear-gradient(135deg, #006FCF 0%, #0077CC 50%, #00A4E0 100%); }
        .card-back { background: linear-gradient(135deg, #434343 0%, #000000 100%); transform: rotateY(180deg); }
        .card-visual { height: 100%; display: flex; flex-direction: column; justify-content: space-between; position: relative; z-index: 1; }
        .card-logo-container { display: flex; justify-content: flex-end; font-size: 36px; color: white; }
        .card-chip { width: 54px; height: 44px; background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #B8860B 100%); border-radius: 10px; }
        .card-number-display { font-size: 26px; letter-spacing: 4px; font-weight: 600; color: white; margin: 24px 0; font-family: 'Courier New', monospace; }
        .card-details-display { display: flex; justify-content: space-between; }
        .card-detail-label { font-size: 10px; text-transform: uppercase; color: white; }
        .card-detail-value { font-size: 17px; font-weight: 700; color: white; margin-top: 6px; text-transform: uppercase; }
        .card-magnetic-strip { width: 100%; height: 56px; background: #000; margin: 24px 0; }
        .card-cvc-display { display: flex; justify-content: flex-end; align-items: center; gap: 14px; background: white; padding: 12px 24px; border-radius: 10px; margin-top: 36px; }
        .card-input-grid { display: grid; gap: 24px; }
        .card-input-group { position: relative; }
        .card-input-label { font-size: 14px; font-weight: 600; color: #5f6368; margin-bottom: 10px; display: block; }
        .card-input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .card-brand-icon { position: absolute; right: 18px; top: 12px; font-size: 28px; opacity: 0; transition: all 0.3s ease; }
        .card-brand-icon.active { opacity: 1; }
        .card-error-message { color: var(--error-red); font-size: 13px; font-weight: 600; margin-top: 8px; display: none; min-height: 1em; }
        .card-error-message.show { display: block; }
        .right-section { position: sticky; top: 40px; height: fit-content; }
        .summary-card { padding: 40px; }
        .summary-title { font-size: 22px; font-weight: 700; color: var(--dark-text); margin-bottom: 28px; }
        .plan-badge { background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-green) 100%); border-radius: 20px; padding: 28px; color: white; margin-bottom: 28px; }
        .plan-name { font-size: 16px; margin-bottom: 12px; font-weight: 600; }
        .plan-price { font-size: 42px; font-weight: 800; margin-bottom: 6px; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 18px; font-size: 15px; }
        .summary-item.total { font-size: 22px; font-weight: 700; color: var(--dark-text); padding-top: 20px; border-top: 2px solid rgba(0, 0, 0, 0.1); margin-top: 12px; }
        .pay-button { width: 100%; height: 60px; background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-green) 100%); border: none; border-radius: 14px; color: white; font-size: 17px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 14px; transition: all 0.3s ease; }
        .pay-button:disabled { opacity: 0.7; cursor: not-allowed; }
        .spinner { width: 22px; height: 22px; border: 3px solid rgba(255, 255, 255, 0.3); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .success-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px); display: none; align-items: center; justify-content: center; z-index: 2000; }
        .success-overlay.active { display: flex; }
        .success-message { text-align: center; }
        .success-icon { width: 100px; height: 100px; background: linear-gradient(135deg, var(--primary-green) 0%, #2d8e44 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 50px; margin: 0 auto 28px; }
        .success-title { font-size: 32px; font-weight: 800; color: var(--dark-text); margin-bottom: 16px; }
        .error-modal { background: white; border-radius: 24px; padding: 48px; max-width: 440px; text-align: center; box-shadow: 0 24px 72px rgba(0, 0, 0, 0.3); opacity: 0; transform: scale(0.85); transition: all 0.3s ease; }
        #error-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 2000; }
        #error-overlay.active { display: flex; }
        #error-modal.active { opacity: 1; transform: scale(1); }
        .error-icon { width: 100px; height: 100px; background: linear-gradient(135deg, #fef7f6 0%, #fce8e6 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--error-red); font-size: 50px; margin: 0 auto 24px; }
        .error-title { font-size: 26px; font-weight: 700; color: var(--dark-text); margin-bottom: 16px; }
        .error-text { font-size: 16px; color: #5f6368; margin-bottom: 28px; line-height: 1.6; }
        .error-button { background: linear-gradient(135deg, var(--primary-blue) 0%, #3367d6 100%); border: none; border-radius: 12px; padding: 16px 40px; font-size: 16px; font-weight: 700; color: white; cursor: pointer; }
        .validation-alert { position: fixed; bottom: 40px; left: 50%; transform: translateX(-50%) translateY(120px); background: var(--dark-text); color: white; padding: 18px 28px; border-radius: 14px; display: flex; align-items: center; gap: 14px; box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4); z-index: 1500; opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .validation-alert.show { transform: translateX(-50%) translateY(0); opacity: 1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section glass-card">
            <!-- Contenido del formulario... -->
            <div class="section">
                <div class="section-header" id="payment-header">
                    <div class="section-icon"><i class="fas fa-credit-card"></i></div>
                    <h2 class="section-title">Información de Pago</h2>
                    <div class="section-complete-badge"><i class="fas fa-check"></i><span>Completo</span></div>
                </div>
                <div class="card-container">
                    <!-- ... tarjeta 3D ... -->
                </div>
                <div class="card-input-grid">
                    <div class="card-input-group">
                        <label class="card-input-label">Número de tarjeta *</label>
                        <div class="card-input-field" id="card-number-element"><div class="card-brand-icon" id="card-brand-icon"><i class="fas fa-credit-card"></i></div></div>
                        <div class="card-error-message" id="cardNumber-error"></div>
                    </div>
                    <div class="card-input-group">
                        <label class="card-input-label">Nombre en la tarjeta *</label>
                        <input type="text" class="input-field" id="card-name">
                        <div class="card-error-message" id="cardName-error"></div>
                    </div>
                    <div class="card-input-row">
                        <div class="card-input-group">
                            <label class="card-input-label">Fecha de expiración *</label>
                            <div class="card-input-field" id="card-expiry-element"></div>
                            <div class="card-error-message" id="cardExpiry-error"></div>
                        </div>
                        <div class="card-input-group">
                            <label class="card-input-label">CVC *</label>
                            <div class="card-input-field" id="card-cvc-element"></div>
                            <div class="card-error-message" id="cardCvc-error"></div>
                        </div>
                    </div>
                    <div class="card-input-group">
                        <label class="card-input-label">Código postal *</label>
                        <input type="text" class="input-field" id="card-zip">
                        <div class="card-error-message" id="cardZip-error"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="right-section">
            <!-- ... resumen del pedido ... -->
            <button class="pay-button" id="pay-button">
                <span id="button-text">Pagar <?php echo htmlspecialchars($displayPrice); ?> <?php echo htmlspecialchars($displayCurrency); ?></span>
            </button>
        </div>
    </div>
    <!-- ... modales de éxito y error ... -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const stripe = Stripe('<?php echo $stripeLivePublishableKey; ?>');
    const elements = stripe.elements({ locale: 'es' });
    const elementStyles = {
        base: { color: '#202124', fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif', fontSize: '16px', '::placeholder': { color: '#aab7c4' } },
        invalid: { color: '#ea4335', iconColor: '#ea4335' }
    };
    const cardNumber = elements.create('cardNumber', { style: elementStyles, showIcon: true, iconStyle: 'solid' });
    cardNumber.mount('#card-number-element');
    const cardExpiry = elements.create('cardExpiry', { style: elementStyles });
    cardExpiry.mount('#card-expiry-element');
    const cardCvc = elements.create('cardCvc', { style: elementStyles });
    cardCvc.mount('#card-cvc-element');

    const formElements = {
        nombre: document.getElementById('nombre'), email: document.getElementById('email'), pais: document.getElementById('selected-country'),
        telefono: document.getElementById('telefono'), compania: document.getElementById('compania'), direccion: document.getElementById('direccion'),
        cardName: document.getElementById('card-name'), cardZip: document.getElementById('card-zip'), payButton: document.getElementById('pay-button')
    };

    const formState = {};
    const fieldsToValidate = ['nombre', 'email', 'telefono', 'compania', 'direccion', 'card-name', 'card-zip'];
    fieldsToValidate.forEach(id => formState[id] = false);
    ['cardNumber', 'cardExpiry', 'cardCvc'].forEach(name => formState[name] = false);

    function setupStripeElementValidation(element, name) {
        element.on('change', function(event) {
            formState[name] = event.complete;
            const errorElement = document.getElementById(name + '-error');
            const fieldContainer = element._component.parentElement;
            fieldContainer.classList.toggle('invalid', !!event.error);
            if (errorElement) {
                errorElement.textContent = event.error ? event.error.message : '';
                errorElement.classList.toggle('show', !!event.error);
            }
        });
    }
    setupStripeElementValidation(cardNumber, 'cardNumber');
    setupStripeElementValidation(cardExpiry, 'cardExpiry');
    setupStripeElementValidation(cardCvc, 'cardCvc');

    formElements.payButton.addEventListener('click', async () => {
        setLoading(true);
        try {
            const response = await fetch('pago.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nombre: formElements.nombre.value, email: formElements.email.value, telefono: formElements.telefono.value,
                    direccion: formElements.direccion.value, compania: formElements.compania.value, pais: formElements.pais.textContent.trim()
                })
            });
            const data = await response.json();
            if (!data.success) throw new Error(data.error);

            const { paymentIntent, error } = await stripe.confirmCardPayment(data.clientSecret, {
                payment_method: {
                    card: cardNumber,
                    billing_details: { name: formElements.cardName.value, email: formElements.email.value, phone: formElements.telefono.value, address: { line1: formElements.direccion.value, postal_code: formElements.cardZip.value } }
                }
            });

            if (error) throw error;
            if (paymentIntent && paymentIntent.status === 'succeeded') showSuccess();
            else throw new Error('El pago no fue exitoso.');

        } catch (error) {
            showError('Error en el Pago', error.message || 'Ocurrió un error inesperado.');
        } finally {
            setLoading(false);
        }
    });

    function setLoading(isLoading) {
        formElements.payButton.disabled = isLoading;
        formElements.payButton.querySelector('#button-text').textContent = isLoading ? 'Procesando...' : 'Pagar';
    }
    function showSuccess() { document.getElementById('success-overlay').classList.add('active'); }
    function showError(title, message) {
        document.getElementById('error-title').textContent = title;
        document.getElementById('error-text').textContent = message;
        document.getElementById('error-overlay').classList.add('active');
        document.getElementById('error-modal').classList.add('active');
    }
});
</script>
</body>
</html>