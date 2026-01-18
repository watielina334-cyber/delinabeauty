<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$order_id = (int) $_GET['id'];

/* ================= ORDER ================= */
$orderQuery = mysqli_query($conn, "
    SELECT *
    FROM orders
    WHERE id = $order_id
");

$order = mysqli_fetch_assoc($orderQuery);

if (!$order) {
    echo "<h2 class='text-center mt-20'>Order not found</h2>";
    exit;
}

/* ================= ITEMS ================= */
$itemsQuery = mysqli_query($conn, "
    SELECT 
        od.quantity,
        od.price,
        od.subtotal,
        p.name,
        p.image
    FROM order_details od
    JOIN products p ON p.id = od.product_id
    WHERE od.order_id = $order_id
");

// menghitung shipping_cost
$q_total = mysqli_query($conn, "
    SELECT COALESCE(SUM(quantity * price),0) AS total_barang
    FROM order_details
    WHERE order_id = $order_id
");
$total_barang = (int)(mysqli_fetch_assoc($q_total)['total_barang'] ?? 0);

// ambil shipping_cost dari tabel orders (pastikan kolomnya ada)
$shipping_cost = (int)($order['shipping_cost'] ?? 0);

// grand total
$grand_total = $total_barang + $shipping_cost;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Cusomer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-pink-600">
                Admin Dashboard
            </h1>

            <div class="flex space-x-6 text-gray-700 font-medium">
                <a href="dashboard.php" class="hover:text-pink-600">Dashboard</a>
                <a href="product.php" class="hover:text-pink-600">Product</a>
                <a href="order.php" class="text-pink-600">Order</a>
                <a href="customer.php" class="hover:text-pink-600">Customer</a>
                <!-- 🔥 LOGOUT -->
                <a href="../logout.php"
                    onclick="return confirm('Yakin ingin logout?')"
                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                    Logout
                </a>
            </div>
        </div>
    </nav>



    <!-- ================= CONTENT ================= -->
    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- HEADER -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold">📦 Order Detail</h2>
            <a href="order.php"
                class="text-sm text-gray-500 hover:underline">
                ← Back to Orders
            </a>
        </div>

        <!-- ORDER INFO -->
        <div class="bg-white rounded-2xl shadow p-6 mb-6 grid md:grid-cols-2 gap-6">

            <div>
                <p class="text-sm text-gray-500">Order ID</p>
                <p class="font-semibold"><?= $order['id'] ?></p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Order Date</p>
                <p class="font-semibold">
                    <?= date('d M Y H:i', strtotime($order['created_at'])) ?>
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Payment Method</p>
                <p class="font-semibold"><?= $order['metode_pembayaran'] ?></p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Status</p>

                <?php
                $status = $order['status'];

                $map = [
                    'pending'    => ['Pending',    'bg-yellow-100 text-yellow-700 border-yellow-200'],
                    'paid'       => ['Paid',       'bg-green-100 text-green-700 border-green-200'],
                    'processing' => ['Processing', 'bg-blue-100 text-blue-700 border-blue-200'],
                    'shipped'    => ['Shipped',    'bg-purple-100 text-purple-700 border-purple-200'],
                    'completed'  => ['Completed',  'bg-emerald-100 text-emerald-700 border-emerald-200'],
                    'cancel'     => ['Canceled',   'bg-red-100 text-red-700 border-red-200'],
                ];

                $class = $map[$status] ?? 'bg-gray-100 text-gray-700';
                ?>

                <span class="inline-block px-3 py-1 rounded-full text-sm <?= $class ?>">
                    <?= ucfirst($status) ?>
                </span>
            </div>


            <div class="md:col-span-2">
                <p class="text-sm text-gray-500">Shipping Address</p>
                <p class="font-semibold"><?= nl2br(htmlspecialchars($order['alamat'])) ?></p>
            </div>

        </div>

        <!-- ITEMS -->
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
                    <?php while ($item = mysqli_fetch_assoc($itemsQuery)): ?>
                        <tr>
                            <td class="px-6 py-4 flex items-center gap-4">
                                <img src="../assets/<?= $item['image'] ?>"
                                    class="w-14 h-14 object-cover rounded">
                                <span class="font-semibold"><?= $item['name'] ?></span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                Rp <?= number_format($item['price']) ?>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <?= $item['quantity'] ?>
                            </td>

                            <td class="px-6 py-4 text-right font-semibold">
                                Rp <?= number_format($item['price'] * $item['quantity']) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>

        <!-- TOTAL -->
        <div class="text-sm text-gray-600 space-y-1">
            <div class="flex justify-between gap-8">
                <span>Total Barang</span>
                <span class="font-semibold">Rp <?= number_format($total_barang) ?></span>
            </div>
            <div class="flex justify-between gap-8">
                <span>Ongkir</span>
                <span class="font-semibold">Rp <?= number_format($shipping_cost) ?></span>
            </div>
            <hr class="my-2">
        </div>

        <p class="text-2xl font-bold text-pink-500 text-right">
            Rp <?= number_format($grand_total) ?>
        </p>

    </div>

</body>

</html>