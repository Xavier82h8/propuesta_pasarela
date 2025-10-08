<?php
session_start();

require 'vendor/autoload.php';
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\Price;

// Configuración de claves
$stripeLiveSecretKey = getenv('STRIPE_LIVE_SECRET_KEY') ?: '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';
Stripe::setApiKey($stripeLiveSecretKey);

// CORS y headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://postulafacil.com'); // Ajusta a tu dominio
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Leer datos del body (información del usuario)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos JSON inválidos.']);
    exit;
}

// Validar que los datos del plan y precio existan en la sesión
if (!isset($_SESSION['amount'], $_SESSION['currency'], $_SESSION['plan'], $_SESSION['period'], $_SESSION['price_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'La información del plan no está en la sesión. Por favor, selecciona un plan de nuevo.']);
    exit;
}

// Validar que los datos del formulario del usuario lleguen
if (!$data || !isset($data['nombre'], $data['email'], $data['telefono'], $data['direccion'], $data['compania'], $data['pais'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan datos del formulario. Por favor, completa toda la información.']);
    exit;
}

// Asignar variables desde la SESIÓN (fuente de verdad para el pago)
$amount = $_SESSION['amount'];
$currency = $_SESSION['currency'];
$plan = $_SESSION['plan'];
$period = $_SESSION['period'];
$priceId = $_SESSION['price_id'];

// Asignar y sanitizar variables desde el POST (datos del usuario)
$nombre = trim($data['nombre']);
$email = trim($data['email']);
$telefono = trim($data['telefono']);
$direccion = trim($data['direccion']);
$compania = trim($data['compania']);
$pais_nombre_completo = trim($data['pais']);

// Mapeo simple de país a código
$countryCodes = ['México' => 'MX', 'Estados Unidos' => 'US', 'Canadá' => 'CA', 'España' => 'ES', 'Argentina' => 'AR', 'Colombia' => 'CO', 'Chile' => 'CL', 'Perú' => 'PE', 'Brasil' => 'BR', 'Venezuela' => 'VE'];
$country_code = 'MX'; // Default
foreach($countryCodes as $name => $code) {
    if (strpos($pais_nombre_completo, $name) !== false) {
        $country_code = $code;
        break;
    }
}

try {
    // Crear o buscar cliente en Stripe
    $customers = Customer::all(['email' => $email, 'limit' => 1]);
    if (count($customers->data) > 0) {
        $customer = $customers->data[0];
        // Opcional: Actualizar datos del cliente si ya existe
        Customer::update($customer->id, [
            'name' => $nombre,
            'phone' => $telefono,
            'address' => [
                'line1' => $direccion,
                'country' => $country_code,
            ],
        ]);
    } else {
        $customer = Customer::create([
            'name' => $nombre,
            'email' => $email,
            'phone' => $telefono,
            'address' => [
                'line1' => $direccion,
                'country' => $country_code,
            ],
            'metadata' => [
                'compania' => $compania,
            ],
        ]);
    }

    // Crear la suscripción
    $subscription = Subscription::create([
        'customer' => $customer->id,
        'items' => [['price' => $priceId]],
        'payment_behavior' => 'default_incomplete',
        'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
        'expand' => ['latest_invoice.payment_intent'],
        'description' => "Suscripción a YoPracticando - Plan " . ucfirst($plan) . " " . ucfirst($period),
    ]);

    // Retornar el client_secret para que el frontend confirme el pago
    echo json_encode([
        'success' => true,
        'clientSecret' => $subscription->latest_invoice->payment_intent->client_secret,
    ]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    error_log("Stripe API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Hubo un error con la pasarela de pago. Por favor, inténtalo de nuevo.']);
} catch (Exception $e) {
    http_response_code(500);
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ocurrió un error inesperado. Por favor, contacta a soporte.']);
}
?>