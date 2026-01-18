<?php
require './config/database.php'; // sesuaikan

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status   = $_POST['status'] ?? '';

    $allowed = ['pending','processing','shipped','completed','cancel'];
    if (!$order_id || !in_array($status, $allowed)) {
        header("Location: order.php?err=invalid");
        exit;
    }

    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();

    header("Location: order.php?ok=1");
    exit;
}
?>