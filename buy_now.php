<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: product.php");
  exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$qty = (int)($_POST['quantity'] ?? 1);
if ($qty < 1) $qty = 1;

$_SESSION['buy_now'] = [
  'product_id' => $product_id,
  'quantity' => $qty
];

header("Location: checkout.php?buy_now=1");
exit;
?>