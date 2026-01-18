<?php
session_start();
include '../config/database.php';

/* =========================
   PROTEKSI ADMIN
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

/* =========================
   QUERY SUMMARY
========================= */

// total product
$q_product = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
$total_product = mysqli_fetch_assoc($q_product)['total'];

// total order (HANYA orderan)
$q_order = mysqli_query($conn, "
  SELECT COUNT(*) AS total 
  FROM orders
  WHERE status != 'cancel'
");
$total_order = (int)mysqli_fetch_assoc($q_order)['total'];


// total customer
$q_customer = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role='customer'"
);
$total_customer = mysqli_fetch_assoc($q_customer)['total'];

// produk terlaris
$bestQuery = mysqli_query($conn, "SELECT  p.name, SUM(od.quantity) AS total_qty
    FROM order_details od
    JOIN orders o ON o.id = od.order_id
    JOIN products p ON p.id = od.product_id
    WHERE o.status = 'paid'
    GROUP BY od.product_id
    ORDER BY total_qty DESC
    LIMIT 1
");

$best_product = mysqli_fetch_assoc($bestQuery);


// grafik penjualan produk
$bulan = date('m');
$tahun = date('Y');

$hari = [];
$total = [];

// bikin list tanggal 1..akhir bulan
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$bulan, (int)$tahun);
for ($d = 1; $d <= $daysInMonth; $d++) {
    $hari[] = str_pad($d, 2, '0', STR_PAD_LEFT);
    $total[] = 0;
}

// ambil data omzet per tanggal (completed)
$q = mysqli_query($conn, "
    SELECT DAY(created_at) as day, SUM(total_harga) as total
    FROM orders
    WHERE LOWER(status)='completed'
      AND MONTH(created_at)='$bulan'
      AND YEAR(created_at)='$tahun'
    GROUP BY DAY(created_at)
");

while($r = mysqli_fetch_assoc($q)){
    $idx = (int)$r['day'] - 1;
    $total[$idx] = (int)$r['total'];
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<style>
    .card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
        margin-top: 16px;
    }

    .card-title {
        font-size: 16px;
        font-weight: 800;
        margin: 0 0 10px 0;
        color: #374151;
    }

    .text-muted {
        color: #6b7280;
    }

    .text-pink {
        color: #ec4899;
        font-weight: 800;
    }

    .row-between {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .btn {
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        background: #fff;
        font-weight: 700;
        cursor: pointer;
    }

    .btn-primary {
        background: #ec4899;
        border-color: #ec4899;
        color: #fff;
    }

    .btn-primary:hover {
        filter: brightness(0.95);
    }
</style>

<body class="bg-gray-100 min-h-screen">

    <!-- ================= NAVBAR ================= -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-pink-600">
                Admin Dashboard
            </h1>

            <div class="flex space-x-6 text-gray-700 font-medium">
                <a href="dashboard.php" class="text-pink-600">Dashboard</a>
                <a href="product.php" class="hover:text-pink-600">Product</a>
                <a href="order.php" class="hover:text-pink-600">Order</a>
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

        <!-- SUMMARY CARDS -->
        <div class="flex flex-wrap justify-center gap-6 mb-8">

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 w-full sm:w-64 text-center transition-transform hover:scale-105">
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Total Product</p>
                <h2 class="text-4xl font-black text-pink-600 mt-2">
                    <?= number_format($total_product) ?>
                </h2>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 w-full sm:w-64 text-center transition-transform hover:scale-105">
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Total Order</p>
                <h2 class="text-4xl font-black text-blue-600 mt-2">
                    <?= number_format($total_order) ?>
                </h2>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 w-full sm:w-64 text-center transition-transform hover:scale-105">
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Customer</p>
                <h2 class="text-4xl font-black text-green-600 mt-2">
                    <?= number_format($total_customer) ?>
                </h2>
            </div>

        </div>
        <!-- PRODUK TERLARIS -->
        <div class="card">
            <div class="row-between">
                <h3 class="card-title">Produk Terlaris</h3>
                <a class="btn btn-primary" href="produk_terlaris.php">Lihat Detail</a>
            </div>

            <?php if (!empty($best_product)): ?>
                <p class="text-muted" style="margin:0;">
                    <b><?= htmlspecialchars($best_product['name']) ?></b>
                    — terjual
                    <span class="text-pink"><?= (int)$best_product['total_qty'] ?></span>
                    pcs
                </p>
            <?php else: ?>
                <p class="text-muted" style="margin:0;">Belum ada penjualan</p>
            <?php endif; ?>
        </div>

    </div>

    <div style=" text-align: center; margin-top:30px; justify-content: center; max-width:900px; height: 350px; margin: 0 auto;">
        <h3 style="font-weight: 750;">Grafik Penjualan Bulan Ini</h3>
        <canvas id="salesChart"></canvas>
    </div>

    <!-- script grafik penjualan -->
    <script>
        const labels = <?= json_encode($hari); ?>;
        const dataVals = <?= json_encode($total); ?>;

        const minVal = Math.min(...dataVals);
        const maxVal = Math.max(...dataVals);

        // kalau semua 0, kasih range default biar gak Rp 0 - Rp 1
        const padding = (maxVal === 0) ? 50000 : 5000;

        new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Total Penjualan (Rp)',
                    data: dataVals,
                    borderWidth: 4,
                    pointRadius: 6,
                    borderColor: '#ff4d88',
                    backgroundColor: 'rgba(255,77,136,0.2)',
                    pointBackgroundColor: '#ff4d88',
                    tension: 0.6,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        suggestedMin: Math.max(0, minVal - padding),
                        suggestedMax: maxVal + padding,
                        ticks: {
                            callback: (value) => 'Rp ' + Number(value).toLocaleString('id-ID')
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>