<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? 0);

/* ================= ORDER ================= */
$orderQ = mysqli_query($conn, "SELECT o.id, o.created_at, o.metode_pembayaran, o.status, o.shipping_cost, o.total_harga, u.no_hp, u.provinsi
  FROM orders o
  JOIN users u ON o.user_id = u.user_id
  WHERE o.id = $order_id AND o.user_id = $user_id
");

$order = mysqli_fetch_assoc($orderQ);
if (!$order) die("Order tidak ditemukan");

$status = strtolower(trim($order['status'] ?? 'pending'));
$shipping_cost = (int)($order['shipping_cost'] ?? 0);

/* ================= ITEMS ================= */
$itemsQuery = mysqli_query($conn, "
    SELECT od.quantity, od.price, od.subtotal, p.name, p.image
    FROM order_details od
    JOIN products p ON p.id = od.product_id
    WHERE od.order_id = $order_id
");

/* ================= HITUNG SUBTOTAL PRODUK ================= */
$subtotal_produk = 0;
$items = [];

while ($row = mysqli_fetch_assoc($itemsQuery)) {
    $items[] = $row;
    $subtotal_produk += (int)$row['subtotal'];
}

// Total final (pilih salah satu):
// 1) kalau orders.total_harga SUDAH termasuk ongkir -> pakai ini (lebih aman)
$total_bayar = isset($order['total_harga']) ? (int)$order['total_harga'] : ($subtotal_produk + $shipping_cost);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-pink-600">Customer Dashboard</h1>
            <div class="flex space-x-6 text-gray-700 font-medium">
                <a href="profile.php" class="hover:text-pink-600">Profile</a>
                <a href="order.php" class="text-pink-600">Orders</a>
                <a href="../index.php" class="hover:text-pink-600">Home</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">📦 Order Detail</h2>
            <a href="order.php" class="text-sm text-gray-500 hover:underline">← Back to Orders</a>
        </div>

        <div class="bg-white rounded-2xl shadow p-6 mb-6 grid md:grid-cols-2 gap-6">

            <div>
                <p class="text-sm text-gray-500">Order ID</p>
                <p class="font-semibold"><?= (int)$order['id'] ?></p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Order Date</p>
                <p class="font-semibold"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Payment Method</p>
                <p class="font-semibold"><?= strtoupper($order['metode_pembayaran']) ?></p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Status</p>
                <?php if ($status === 'pending'): ?>
                    <span class="inline-block px-3 py-1 rounded-full text-sm bg-yellow-100 text-yellow-700">Pending</span>
                <?php elseif ($status === 'processing'): ?>
                    <span class="inline-block px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-700">Processing</span>
                <?php elseif ($status === 'shipped'): ?>
                    <span class="inline-block px-3 py-1 rounded-full text-sm bg-indigo-100 text-indigo-700">Shipped</span>
                <?php elseif ($status === 'completed'): ?>
                    <span class="inline-block px-3 py-1 rounded-full text-sm bg-green-100 text-green-700">Completed</span>
                <?php elseif ($status === 'cancel'): ?>
                    <span class="inline-block px-3 py-1 rounded-full text-sm bg-red-100 text-red-700">Cancel</span>
                <?php else: ?>
                    <span class="inline-block px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700"><?= htmlspecialchars($status) ?></span>
                <?php endif; ?>
            </div>

            <div class="md:col-span-2">
                <p class="text-sm text-gray-500">Shipping Address</p>
                <p class="font-semibold"><?= nl2br(htmlspecialchars($order['provinsi'])) ?></p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left">Product</th>
                        <th class="px-6 py-4 text-center">Price</th>
                        <th class="px-6 py-4 text-center">Qty</th>
                        <th class="px-6 py-4 text-right">Subtotal</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td class="px-6 py-4 flex items-center gap-4">
                                <img src="../assets/<?= htmlspecialchars($it['image']) ?>" class="w-14 h-14 object-cover rounded" alt="">
                                <span class="font-semibold"><?= htmlspecialchars($it['name']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-center">Rp <?= number_format((int)$it['price']) ?></td>
                            <td class="px-6 py-4 text-center"><?= (int)$it['quantity'] ?></td>
                            <td class="px-6 py-4 text-right font-semibold">Rp <?= number_format((int)$it['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- RINGKASAN (SUBTOTAL + ONGKIR + TOTAL) -->
            <div class="px-6 py-5 border-t bg-gray-50 text-right space-y-1">
                <div class="text-sm text-gray-600">
                    Subtotal Produk: <span class="font-semibold text-gray-800">Rp <?= number_format($subtotal_produk) ?></span>
                </div>
                <div class="text-sm text-gray-600">
                    Ongkir: <span class="font-semibold text-gray-800">Rp <?= number_format($shipping_cost) ?></span>
                </div>
                <div class="text-lg font-bold text-pink-600 pt-2">
                    Total Payment: Rp <?= number_format($total_bayar) ?>
                </div>
            </div>
        </div>

    </div>
</body>

</html>