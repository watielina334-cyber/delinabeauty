<?php
include './config/database.php';

\Midtrans\Config::$serverKey = 'MIDTRANS_SERVER_KEY';
\Midtrans\Config::$isProduction = false;

$serverKey = $_ENV['MIDTRANS_SERVER_KEY'] ?? null;
if (!$serverKey) {
    http_response_code(500);
    exit('MIDTRANS_SERVER_KEY not set');
}

$payload = file_get_contents("php://input");
$data = json_decode($payload, true);

if (!$data || !isset($data['order_id'])) {
    http_response_code(400);
    exit('Invalid notification');
}

$order_id = $data['order_id'];
$status   = $data['transaction_status'] ?? 'pending';
$type     = $data['payment_type'] ?? 'midtrans';

// ambil bank + VA (kalau bank transfer)
$bank = null;
$va_number = null;

// VA biasa (BCA/BRI/BNI dll)
if (!empty($data['va_numbers'][0]['bank'])) {
    $bank = $data['va_numbers'][0]['bank'];
    $va_number = $data['va_numbers'][0]['va_number'];
}

// Permata VA beda field
if (!empty($data['permata_va_number'])) {
    $bank = 'permata';
    $va_number = $data['permata_va_number'];
}

// mapping status midtrans -> status internal
$dbStatus = match ($status) {
    'settlement', 'capture' => 'paid',
    'pending'              => 'pending',
    'expire', 'cancel', 'deny' => 'cancel',
    default                => 'pending'
};

// update DB (lebih aman)
$stmt = $conn->prepare("
    UPDATE orders SET
        status=?,
        metode_pembayaran=?,
        bank=?,
        va_number=?,
        midtrans_status=?
    WHERE order_code=?
");

$stmt->bind_param(
    "ssssss",
    $dbStatus,
    $type,
    $bank,
    $va_number,
    $status,
    $order_id
);

$stmt->execute();
$stmt->close();

http_response_code(200);
echo 'OK';

?>
