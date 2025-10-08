<?php
require 'vendor/autoload.php';
require 'functions.php';
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\Price;
use Stripe\PaymentIntent;

// Configuración de claves (solo live)
$stripeLiveSecretKey = getenv('STRIPE_LIVE_SECRET_KEY') ?: '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';

// CORS y headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://postulafacil.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Leer datos del body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log de solicitud completa
error_log("Solicitud completa a las " . date('Y-m-d H:i:s') . ": " . json_encode($_SERVER) . " - Datos: " . print_r($data, true));

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $errorMsg = 'Los datos enviados no son válidos. Por favor, verifica el formulario.';
    error_log($errorMsg . " - Error JSON: " . json_last_error_msg());
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

// Sanitizar datos para evitar caracteres prohibidos
foreach ($data as $key => $value) {
    if (is_string($value)) {
        $value = str_replace('..', '', $value);
        if (preg_match('/[\x00-\x1F\x7F]/', $value)) {
            http_response_code(400);
            $errorMsg = "El campo $key contiene caracteres de control prohibidos. Valor: " . bin2hex($value);
            error_log($errorMsg);
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            exit;
        }
        $data[$key] = mb_convert_encoding(trim($value), 'UTF-8', 'auto');
    }
}

// Map de price IDs
$priceIdsMonthly = ['basico' => 'price_1S6GZtH0kpgdEo6U0tTbmK07', 'profesional' => 'price_1S6GaKH0kpgdEo6UGdIKRfQc', 'empresarial' => 'price_1S6GalH0kpgdEo6UVlBbV3ka'];
$priceIdsAnnual = ['basico' => 'price_1RGekOH0kpgdEo6UTK8saFZ2', 'profesional' => 'price_1S6GfpH0kpgdEo6U3WQkT6mS', 'empresarial' => 'price_1S6GgmH0kpgdEo6U0oz70klC'];
$countryCodes = ['México' => 'MX', 'Estados Unidos' => 'US', 'Canadá' => 'CA', 'España' => 'ES', 'Argentina' => 'AR', 'Colombia' => 'CO', 'Chile' => 'CL', 'Perú' => 'PE', 'Brasil' => 'BR', 'Venezuela' => 'VE'];

// Validación básica
if (!$data || !isset($data['amount'], $data['currency'], $data['plan'], $data['period'])) {
    http_response_code(400);
    $errorMsg = 'Faltan datos requeridos en la solicitud. Por favor, completa todos los campos.';
    error_log($errorMsg);
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

// Procesar valores
$amount = filter_var($data['amount'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000]]);
$currency = strtolower(trim($data['currency'] ?? ''));
$plan = strtolower(trim($data['plan'] ?? ''));
$input_period = strtolower(trim($data['period'] ?? ''));

// Mapeo robusto para período
if ($input_period === 'mensual' || $input_period === 'monthly') {
    $period = 'monthly';
    $period_for_db = 'mensual';
} elseif ($input_period === 'anual' || $input_period === 'annual') {
    $period = 'annual';
    $period_for_db = 'anual';
} else {
    error_log("Período inválido en " . date('Y-m-d H:i:s') . ": " . $input_period);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El período seleccionado no es válido. Elige mensual o anual.']);
    exit;
}

// Log de valores procesados
error_log("Valores procesados en " . date('Y-m-d H:i:s') . ": amount=$amount, currency=$currency, plan=$plan, period=$period");

// Validar contenido
if ($amount === false) {
    http_response_code(400);
    $errorMsg = 'El monto es inválido o inferior al mínimo ($10.00 MXN). Verifica el plan seleccionado.';
    error_log($errorMsg);
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}
if (!array_key_exists($plan, $priceIdsMonthly)) {
    error_log("Plan inválido en " . date('Y-m-d H:i:s') . ": " . $plan);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El plan seleccionado no es válido. Elige básico, profesional o empresarial.']);
    exit;
}
if ($currency !== 'mxn') {
    http_response_code(400);
    $errorMsg = 'La moneda seleccionada no es compatible. Usa MXN.';
    error_log($errorMsg);
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

// Asignar variables para DB
$user_id = intval($data['user_id'] ?? 0);
$compania = trim($data['compania'] ?? '');
$direccion = trim($data['direccion'] ?? '');
$email = trim($data['email'] ?? '');
$nombre = trim($data['nombre'] ?? '');
$pais = trim($data['pais'] ?? '');
$country_code = $countryCodes[$pais] ?? 'MX';
$telefono = trim($data['telefono'] ?? '');

// Stripe API Key
Stripe::setApiKey($stripeLiveSecretKey);

try {
    // Seleccionar el mapa de precios según el período
    $priceIds = ($period === 'annual') ? $priceIdsAnnual : $priceIdsMonthly;
    $priceId = $priceIds[$plan];
    $price = Price::retrieve($priceId);

    if ($amount != $price->unit_amount) {
        throw new Exception("El monto no coincide con el plan en Stripe. Esperado: " . $price->unit_amount . ", recibido: " . $amount);
    }

    $expectedInterval = ($period === 'annual') ? 'year' : 'month';
    if ($price->type !== 'recurring' || $price->recurring->interval !== $expectedInterval) {
        throw new Exception("El plan no es una suscripción " . $expectedInterval . " válida.");
    }

    // Crear Customer
    $customer = Customer::create([
        'name' => $nombre,
        'email' => $email,
        'phone' => $telefono,
        'address' => [
            'line1' => $direccion,
            'country' => $country_code,
        ],
        'metadata' => [
            'user_id' => $user_id,
            'compania' => $compania,
        ],
    ]);

    // Crear Subscription
    $subscription = Subscription::create([
        'customer' => $customer->id,
        'items' => [['price' => $priceId]],
        'payment_behavior' => 'default_incomplete',
        'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
        'expand' => ['latest_invoice.payment_intent'],
        'description' => "Membresía " . ucfirst($plan) . " - " . ucfirst($period_for_db) . " en YoPracticando",
    ]);

    // Obtener el PaymentIntent
    $paymentIntent = $subscription->latest_invoice->payment_intent;

    // Guardar registro inicial en la base de datos
    $conn = conectarDB();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos. Por favor, inténtalo más tarde.");
    }
    $conn->set_charset("utf8mb4");
    $conn->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

    $stmt = $conn->prepare("INSERT INTO membresias (
        user_id, amount, currency, is_test, plan, compania, direccion,
        email, nombre, pais, period, telefono, transaction_id,
        customer_id, sub_id, status, current_period_start, current_period_end
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception("Error al preparar la consulta en la base de datos: " . $conn->error);
    }

    $customer_id = $customer->id;
    $sub_id = $subscription->id;
    $transaction_id = $paymentIntent->id;
    $status = $subscription->status;
    $current_period_start = date('Y-m-d H:i:s', $subscription->current_period_start);
    $current_period_end = date('Y-m-d H:i:s', $subscription->current_period_end);
    $is_test = 0;

    $stmt->bind_param(
        "idsissssssssssssss",
        $user_id,
        $amount,
        $currency,
        $is_test,
        $plan,
        $compania,
        $direccion,
        $email,
        $nombre,
        $pais,
        $period_for_db,
        $telefono,
        $transaction_id,
        $customer_id,
        $sub_id,
        $status,
        $current_period_start,
        $current_period_end
    );

    $stmt->execute();
    $stmt->close();

    // Verificar y actualizar pagos exitosos
    verifyAndUpdateSuccessfulPayments($conn, $user_id);

    // Limpieza de registros incompletos
    $cleanupTime = 300; // 5 minutos
    $queryCleanup = "DELETE FROM membresias WHERE user_id = ? AND status NOT IN ('active', 'trialing') AND UNIX_TIMESTAMP(created_at) < UNIX_TIMESTAMP() - ?";
    $stmtCleanup = $conn->prepare($queryCleanup);
    if ($stmtCleanup) {
        $stmtCleanup->bind_param("ii", $user_id, $cleanupTime);
        $stmtCleanup->execute();
        $stmtCleanup->close();
    }

    $conn->close();

    // Retornar client_secret
    echo json_encode([
        'success' => true,
        'clientSecret' => $paymentIntent->client_secret,
    ]);

} catch (\Stripe\Exception\CardException $e) {
    http_response_code(400);
    $errorMessage = 'Tu tarjeta fue declinada. Por favor, verifica los detalles o usa otra tarjeta. Código: ' . $e->getError()->code . ' - Detalle: ' . $e->getError()->message;
    error_log("Error Stripe (Tarjeta) en " . date('Y-m-d H:i:s') . ": " . $errorMessage);
    echo json_encode(['success' => false, 'error' => $errorMessage]);
} catch (\Stripe\Exception\RateLimitException $e) {
    http_response_code(429);
    $errorMessage = 'Demasiadas solicitudes. Por favor, inténtalo de nuevo en unos minutos.';
    error_log("Error Stripe (Rate Limit) en " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $errorMessage]);
} catch (\Stripe\Exception\InvalidRequestException $e) {
    http_response_code(400);
    $errorMessage = 'Solicitud inválida a Stripe. Detalle: ' . $e->getMessage() . '. Por favor, contacta soporte.';
    error_log("Error Stripe (Invalid Request) en " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $errorMessage]);
} catch (\Stripe\Exception\AuthenticationException $e) {
    http_response_code(401);
    $errorMessage = 'Error de autenticación con Stripe. Por favor, contacta al administrador.';
    error_log("Error Stripe (Authentication) en " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $errorMessage]);
} catch (\Stripe\Exception\ApiConnectionException $e) {
    http_response_code(503);
    $errorMessage = 'Error de conexión con Stripe. Por favor, inténtalo más tarde.';
    error_log("Error Stripe (API Connection) en " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $errorMessage]);
} catch (Exception $e) {
    http_response_code(500);
    $errorMessage = $e->getMessage() ?: 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo o contacta soporte.';
    error_log("Error general en " . date('Y-m-d H:i:s') . ": " . $errorMessage);
    echo json_encode(['success' => false, 'error' => $errorMessage]);
}

// Función para verificar y actualizar pagos exitosos
function verifyAndUpdateSuccessfulPayments($conn, $user_id) {
    global $stripeLiveSecretKey;

    Stripe::setApiKey($stripeLiveSecretKey);

    try {
        // Consultar todas las suscripciones del user_id (asumiendo que tienes un metadata con user_id)
        $subscriptions = Subscription::all([
            'metadata' => ['user_id' => $user_id],
            'expand' => ['data.latest_invoice.payment_intent'],
            'limit' => 100,
        ]);

        foreach ($subscriptions->data as $sub) {
            $paymentIntent = $sub->latest_invoice->payment_intent;
            if ($paymentIntent && $paymentIntent->status === 'succeeded' && $sub->status === 'active') {
                // Verificar si existe en la base de datos
                $checkStmt = $conn->prepare("SELECT id, status FROM membresias WHERE sub_id = ? AND user_id = ?");
                $checkStmt->bind_param("si", $sub->id, $user_id);
                $checkStmt->execute();
                $result = $checkStmt->get_result();

                if ($result->num_rows === 0) {
                    // Insertar nuevo registro
                    $insertStmt = $conn->prepare("INSERT INTO membresias (
                        user_id, amount, currency, is_test, plan, compania, direccion,
                        email, nombre, pais, period, telefono, transaction_id,
                        customer_id, sub_id, status, current_period_start, current_period_end
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $amount = $sub->plan->amount;
                    $currency = $sub->plan->currency;
                    $plan = strtolower($sub->plan->nickname);
                    $period = ($sub->plan->interval === 'year') ? 'anual' : 'mensual';
                    $compania = $sub->customer->metadata->compania ?? '';
                    $direccion = $sub->customer->address->line1 ?? '';
                    $email = $sub->customer->email ?? '';
                    $nombre = $sub->customer->name ?? '';
                    $pais = array_search($sub->customer->address->country, $countryCodes) ?? '';
                    $telefono = $sub->customer->phone ?? '';
                    $transaction_id = $paymentIntent->id;
                    $customer_id = $sub->customer;
                    $sub_id = $sub->id;
                    $status = $sub->status;
                    $current_period_start = date('Y-m-d H:i:s', $sub->current_period_start);
                    $current_period_end = date('Y-m-d H:i:s', $sub->current_period_end);
                    $is_test = 0;

                    $insertStmt->bind_param(
                        "idsissssssssssssss",
                        $user_id,
                        $amount,
                        $currency,
                        $is_test,
                        $plan,
                        $compania,
                        $direccion,
                        $email,
                        $nombre,
                        $pais,
                        $period,
                        $telefono,
                        $transaction_id,
                        $customer_id,
                        $sub_id,
                        $status,
                        $current_period_start,
                        $current_period_end
                    );

                    if ($insertStmt->execute()) {
                        error_log("Nuevo registro insertado para sub_id: $sub_id para user_id: $user_id");
                    } else {
                        error_log("Error al insertar nuevo registro para sub_id: $sub_id - " . $conn->error);
                    }
                    $insertStmt->close();
                } else {
                    // Actualizar registro si el estado no coincide
                    $row = $result->fetch_assoc();
                    if ($row['status'] !== $sub->status) {
                        $updateStmt = $conn->prepare("UPDATE membresias SET status = ?, current_period_start = ?, current_period_end = ?, transaction_id = ? WHERE id = ?");
                        $current_period_start = date('Y-m-d H:i:s', $sub->current_period_start);
                        $current_period_end = date('Y-m-d H:i:s', $sub->current_period_end);
                        $membership_id = $row['id'];
                        $updateStmt->bind_param("ssssi", $sub->status, $current_period_start, $current_period_end, $paymentIntent->id, $membership_id);

                        if ($updateStmt->execute()) {
                            error_log("Registro actualizado para sub_id: $sub_id a status: " . $sub->status . " y transaction_id: " . $paymentIntent->id);
                        } else {
                            error_log("Error al actualizar registro para sub_id: $sub_id - " . $conn->error);
                        }
                        $updateStmt->close();
                    }
                }
                $checkStmt->close();
            }
        }
    } catch (Exception $e) {
        error_log("Error al verificar pagos exitosos en " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
    }
}
?>