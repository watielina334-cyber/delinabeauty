<?php
session_start();
require_once './config/database.php';

// =====================
// CEK REQUEST
// =====================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

// =====================
// CEK LOGIN
// =====================
if (!isset($_SESSION['user_id'])) {
    die('Belum login');
}

$user_id = (int)$_SESSION['user_id'];
$metode  = strtolower($_POST['metode_pembayaran'] ?? '');

// ambil shipping_cost dari form checkout (name="shipping_cost")
$shipping_cost = isset($_POST['shipping_cost']) ? (int)$_POST['shipping_cost'] : 0;

$no_hp  = $_POST['no_hp'] ?? '';
$alamat = $_POST['alamat'] ?? '';

$_SESSION['checkout_ongkir'] = $shipping_cost;

// =====================
// VALIDASI METODE
// =====================
if ($metode !== 'cod') {
    die('Metode pembayaran tidak valid');
}

$items = $_POST['items'] ?? [];
if (empty($items)) {
    die('Item kosong');
}

// =====================
// HITUNG TOTAL PRODUK
// =====================
$total_produk = 0;
foreach ($items as $it) {
    $price = (int)($it['price'] ?? 0);
    $qty   = (int)($it['quantity'] ?? 0);
    $total_produk += ($price * $qty);
}

// grand total
$total = $total_produk + $shipping_cost;

// =====================
// SIMPAN ORDER
// =====================
$order_code = 'COD-' . time();
$alamat_safe = mysqli_real_escape_string($conn, $alamat);

$insert = mysqli_query($conn, "
    INSERT INTO orders (user_id, order_code, total_harga, metode_pembayaran, status, alamat, shipping_cost)
    VALUES ($user_id, '$order_code', $total, 'cod', 'pending', '$alamat_safe', $shipping_cost)
");

if (!$insert) {
    die('Gagal insert orders: ' . mysqli_error($conn));
}

$order_id = mysqli_insert_id($conn);
if (!$order_id) {
    die('Gagal menyimpan order');
}

// =====================
// SIMPAN DETAIL ORDER
// =====================
foreach ($items as $it) {
    $product_id = (int)($it['id'] ?? 0);
    $price      = (int)($it['price'] ?? 0);
    $qty        = (int)($it['quantity'] ?? 0);
    $subtotal   = $price * $qty;

    if ($product_id <= 0 || $qty <= 0) continue;

    $ok = mysqli_query($conn, "
        INSERT INTO order_details (order_id, product_id, price, quantity, subtotal)
        VALUES ($order_id, $product_id, $price, $qty, $subtotal)
    ");

    if (!$ok) {
        die('Gagal insert order_details: ' . mysqli_error($conn));
    }
}

// =====================
// HAPUS CART
// =====================
if (!empty($_POST['cart_ids'])) {
    $ids = implode(',', array_map('intval', $_POST['cart_ids']));
    mysqli_query($conn, "DELETE FROM carts WHERE cart_id IN ($ids)");
}

// =====================
// REDIRECT SUKSES
// =====================
header("Location: order_success.php?order=$order_code");
exit;
