<?php
session_start();
require_once './config/database.php';

header('Content-Type: application/json');

// ambil RAW JSON
$data = json_decode(file_get_contents("php://input"), true);

$buyNow = $data['buy_now'] ?? false;

if ($buyNow) {
    $product_id = $_SESSION['buy_now_product_id'] ?? null;
    if (!$product_id) {
        echo json_encode(['error' => 'Produk tidak ditemukan']);
        exit;
    }

    $q = mysqli_query($conn, "
        SELECT price FROM products WHERE id='$product_id'
    ");
    $row = mysqli_fetch_assoc($q);
    $total = $row['price'];

} else {

    if (!isset($data['cart_ids']) || empty($data['cart_ids'])) {
        echo json_encode(['error' => 'Data cart tidak ditemukan']);
        exit;
    }

    // proses cart seperti biasa
}

// ==================
// HITUNG TOTAL
// ==================
$total = 0;
$ids = implode(',', array_map('intval', $cart_ids));

$q = mysqli_query($conn, "
    SELECT c.qty, p.price 
    FROM cart c
    JOIN products p ON p.id = c.product_id
    WHERE c.id IN ($ids) AND c.user_id = '$user_id'
");

while ($row = mysqli_fetch_assoc($q)) {
    $total += $row['qty'] * $row['price'];
}

// ==================
// ONGKIR (METRO / NON METRO)
// ==================
$alamat = $_SESSION['alamat'] ?? '';
$ongkir = (stripos($alamat, 'metro') !== false) ? 0 : 7000;

$total_bayar = $total + $ongkir;

// ==================
// SIMPAN ORDER COD
// ==================
$insert = mysqli_query($conn, "
    INSERT INTO orders (user_id, metode_pembayaran, ongkir, total, status)
    VALUES ('$user_id', 'COD', '$ongkir', '$total_bayar', 'menunggu')
");

if (!$insert) {
    echo json_encode(['error' => 'Gagal simpan order']);
    exit;
}

// ==================
// HAPUS CART
// ==================
mysqli_query($conn, "
    DELETE FROM cart WHERE id IN ($ids) AND user_id='$user_id'
");

echo json_encode(['success' => true]);
?>