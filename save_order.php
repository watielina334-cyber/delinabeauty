<?php
session_start();
require_once './config/database.php'; // pastikan $conn terisi

header('Content-Type: application/json');

function out($arr, $code=200){
  http_response_code($code);
  echo json_encode($arr);
  exit;
}

// 1) cek koneksi
if (!$conn) {
  out(['status'=>'error','message'=>'Koneksi DB null'], 500);
}
if (mysqli_connect_errno()) {
  out(['status'=>'error','message'=>'DB connect error: '.mysqli_connect_error()], 500);
}

// 2) cek login & items
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) out(['status'=>'error','message'=>'User belum login / user_id kosong'], 401);

$items = $_SESSION['checkout_items'] ?? [];
if (empty($items)) out(['status'=>'error','message'=>'checkout_items kosong di session'], 400);

// 3) ambil input
$raw = file_get_contents("php://input");
$data = json_decode($raw, true) ?: [];

$alamat = trim($data['alamat'] ?? ($_SESSION['alamat'] ?? '-'));
$metode = trim($data['metode'] ?? 'midtrans');

$shipping = (int)($_SESSION['checkout_ongkir'] ?? 0);
$total_items = (int)($_SESSION['checkout_total'] ?? 0);

// fallback hitung total dari items
if ($total_items <= 0) {
  $sum = 0;
  foreach ($items as $it) {
    $price = (int)($it['price'] ?? 0);
    $qty   = (int)($it['quantity'] ?? 0);
    $sub   = (int)($it['subtotal'] ?? ($price * $qty));
    $sum  += $sub;
  }
  $total_items = $sum;
}

$gross = $total_items + $shipping;
if ($gross <= 0) out(['status'=>'error','message'=>'Total invalid: '.$gross], 400);

// 4) generate order_code
$order_code = 'ORD-' . date('YmdHis') . '-' . random_int(100,999);

mysqli_begin_transaction($conn);

try {
  // 5) insert orders (pakai prepared statement)
  $status = 'pending';
  $stmt = mysqli_prepare($conn,
    "INSERT INTO orders (order_code, user_id, total_harga, metode_pembayaran, alamat, status, shipping_cost)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
  );
  if (!$stmt) throw new Exception("Prepare orders gagal: " . mysqli_error($conn));

  mysqli_stmt_bind_param($stmt, "siisssi",
    $order_code, $user_id, $gross, $metode, $alamat, $status, $shipping
  );

  if (!mysqli_stmt_execute($stmt)) {
    throw new Exception("Execute orders gagal: " . mysqli_stmt_error($stmt));
  }

  $order_id = mysqli_insert_id($conn);
  mysqli_stmt_close($stmt);

  // 6) insert detail
  $stmt2 = mysqli_prepare($conn,
    "INSERT INTO order_details (order_id, product_id, quantity, price, subtotal)
     VALUES (?, ?, ?, ?, ?)"
  );
  if (!$stmt2) throw new Exception("Prepare details gagal: " . mysqli_error($conn));

  foreach ($items as $it) {
    $pid = (int)($it['product_id'] ?? 0);
    $qty = (int)($it['quantity'] ?? 0);
    $pr  = (int)($it['price'] ?? 0);
    $sub = (int)($it['subtotal'] ?? ($pr * $qty));

    if ($pid<=0 || $qty<=0) throw new Exception("Item invalid: product_id/qty kosong");

    mysqli_stmt_bind_param($stmt2, "iiiii", $order_id, $pid, $qty, $pr, $sub);

    if (!mysqli_stmt_execute($stmt2)) {
      throw new Exception("Execute details gagal: " . mysqli_stmt_error($stmt2));
    }
  }
  mysqli_stmt_close($stmt2);

  mysqli_commit($conn);

  // simpan di session biar bisa dipakai next step
  $_SESSION['midtrans_order_code'] = $order_code;

  out([
    'status' => 'success',
    'message' => 'Order berhasil disimpan',
    'order_code' => $order_code,
    'order_id_db' => $order_id,
    'gross_amount' => $gross
  ]);

} catch(Exception $e) {
  mysqli_rollback($conn);
  out([
    'status'=>'error',
    'message'=>$e->getMessage(),
    'debug'=>[
      'mysqli_error'=>mysqli_error($conn),
      'session_user_id'=>$user_id,
      'items_count'=>count($items),
      'gross'=>$gross
    ]
  ], 500);
}
?>