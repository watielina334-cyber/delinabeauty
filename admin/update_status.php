<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  die("Unauthorized");
}

$order_id = (int)($_POST['order_id'] ?? 0);
$status   = strtolower(trim($_POST['status'] ?? ''));

$allowed = ['pending','processing','shipped','completed','cancel'];

if ($order_id <= 0 || !in_array($status, $allowed, true)) {
  header("Location: order.php?error=invalid");
  exit;
}

// Ambil status lama
$qOld = mysqli_query($conn, "SELECT status FROM orders WHERE id=$order_id LIMIT 1");
if (!$qOld || mysqli_num_rows($qOld) === 0) {
  header("Location: order.php?error=order_not_found");
  exit;
}
$old = mysqli_fetch_assoc($qOld);
$old_status = strtolower($old['status'] ?? '');

// Mulai transaksi biar aman
mysqli_begin_transaction($conn);

try {
  // Update status order
  $upd = mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id=$order_id");
  if (!$upd) {
    throw new Exception("Gagal update status: " . mysqli_error($conn));
  }

  // Potong stok HANYA saat transisi: (bukan completed) -> completed
  if ($old_status !== 'completed' && $status === 'completed') {

    // Ambil item order
    $items = mysqli_query($conn, "
      SELECT product_id, quantity
      FROM order_details
      WHERE order_id = $order_id
    ");
    if (!$items) {
      throw new Exception("Gagal ambil order_details: " . mysqli_error($conn));
    }

    while ($it = mysqli_fetch_assoc($items)) {
      $pid = (int)$it['product_id'];
      $qty = (int)$it['quantity'];

      // Potong stok dengan proteksi: stok harus cukup (stock >= qty)
      $cut = mysqli_query($conn, "
        UPDATE products
        SET stock = stock - $qty
        WHERE id = $pid AND stock >= $qty
      ");

      if (!$cut || mysqli_affected_rows($conn) === 0) {
        // Kalau stok tidak cukup / update gagal
        throw new Exception("Stok tidak cukup untuk produk ID $pid");
      }
    }
  }

  mysqli_commit($conn);
  header("Location: order.php?success=updated");
  exit;

} catch (Exception $e) {
  mysqli_rollback($conn);
  header("Location: order.php?error=" . urlencode($e->getMessage()));
  exit;
}
?>
