<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
$input = json_decode(file_get_contents("php://input"), true);
$_SESSION['checkout_ongkir'] = (int)($input['shipping_cost'] ?? 0);


\Midtrans\Config::$serverKey = '';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

header('Content-Type: application/json');

// ambil items dari session
$items = $_SESSION['checkout_items'] ?? [];
if (empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'Cart kosong']);
    exit;
}

// ambil ongkir dari body json
$payload = json_decode(file_get_contents("php://input"), true) ?? [];
$shipping_cost = (int)($payload['shipping_cost'] ?? 0);

// simpan shipping_cost ke session biar save_order.php bisa pakai
$_SESSION['checkout_ongkir'] = $shipping_cost;

// hitung total barang dari items (lebih aman)
$total_barang = 0;
$item_details = [];

foreach ($items as $item) {
    $price = (int)$item['price'];
    $qty   = (int)$item['quantity'];
    $total_barang += ($price * $qty);

    $item_details[] = [
        'id' => (string)$item['product_id'],
        'price' => $price,
        'quantity' => $qty,
        'name' => substr($item['name'], 0, 50),
    ];
}

// tambahkan ongkir sebagai item
if ($shipping_cost > 0) {
    $item_details[] = [
        'id' => 'ONGKIR',
        'price' => $shipping_cost,
        'quantity' => 1,
        'name' => 'Ongkos Kirim',
    ];
}

$gross_amount = $total_barang + $shipping_cost;

$order_id = 'ORDER-' . time();

$params = [
    'transaction_details' => [
        'order_id' => $order_id,
        'gross_amount' => $gross_amount,
    ],
    'item_details' => $item_details,
    'customer_details' => [
        'first_name' => $_SESSION['name'] ?? 'Customer',
        'phone' => $_SESSION['phone'] ?? '',
    ],
];

try {
    $snapToken = \Midtrans\Snap::getSnapToken($params);
    echo json_encode([
        'status' => 'ok',
        'token' => $snapToken,
        'order_id' => $order_id,
        'gross_amount' => $gross_amount,
        'shipping_cost' => $shipping_cost
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>