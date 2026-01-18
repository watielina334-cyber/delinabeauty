<?php
session_start();
include '../config/database.php';

/* =========================
   PROTEKSI CUSTOMER
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ambil order milik customer ini
$result = mysqli_query($conn, "
    SELECT *
    FROM orders
    WHERE user_id = '$user_id'
    ORDER BY created_at DESC
");

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

    <!-- ================= NAVBAR ================= -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-pink-600">
                Customer Dashboard
            </h1>

            <div class="flex space-x-6 text-gray-700 font-medium">
                <a href="profile.php" class="hover:text-pink-600">Profile</a>
                <a href="order.php" class="text-pink-600">Orders</a>
                <a href="../index.php" class="hover:text-pink-600">Home</a>

            </div>
        </div>
    </nav>



    <!-- ================= CONTENT ================= -->
    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- HEADER -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">🧾 Order History</h2>
            <span class="text-sm text-gray-500">
                Welcome back, <?= $_SESSION['name'] ?? 'Customer' ?>
            </span>
        </div>

        <?php
        function statusBadge($status)
        {
            $map = [
                'pending'    => ['Pending',    'bg-yellow-100 text-yellow-700 border-yellow-200'],
                'processing' => ['Processing', 'bg-blue-100 text-blue-700 border-blue-200'],
                'shipped'    => ['Shipped',    'bg-purple-100 text-purple-700 border-purple-200'],
                'completed'  => ['Completed',  'bg-emerald-100 text-emerald-700 border-emerald-200'],
                'cancel'     => ['Canceled',   'bg-red-100 text-red-700 border-red-200'],
            ];

            $s = $map[$status] ?? [ucfirst($status), 'bg-gray-100 text-gray-700 border-gray-200'];
            return '<span class="px-3 py-1 rounded-full text-xs border ' . $s[1] . '">' . $s[0] . '</span>';
        }
        ?>


        <!-- TABLE -->
        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left">Order Code</th>
                        <th class="px-6 py-4 text-left">Date</th>
                        <th class="px-6 py-4 text-left">Total</th>
                        <th class="px-6 py-4 text-left">Payment</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-semibold text-gray-800">
                                    <?= htmlspecialchars($order['order_code']) ?>
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    <?= date('d M Y', strtotime($order['created_at'])) ?>
                                </td>

                                <td class="px-6 py-4 font-semibold text-gray-800">
                                    Rp <?= number_format($order['total_harga']) ?>
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    <?= strtoupper($order['metode_pembayaran']) ?>
                                </td>

                                <td class="px-6 py-4">
                                    <?= statusBadge($order['status']) ?>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <a href="order_detail.php?id=<?= $order['id'] ?>"
                                        class="text-pink-500 hover:underline font-semibold">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                You have no orders yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>

</html>