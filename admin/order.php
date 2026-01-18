<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$no = 1;

/* =========================
   DELETE (sebenarnya cancel)
========================= */
if (isset($_GET['delete'])) {
    $order_id = (int)$_GET['delete'];

    // Ambil status order
    $cek = mysqli_query($conn, "SELECT status FROM orders WHERE id = $order_id");
    $order = mysqli_fetch_assoc($cek);

    // Proteksi: kalau sudah completed, jangan boleh "delete/cancel"
    // (kamu bisa ubah aturan ini sesuai kebutuhan)
    if ($order && strtolower($order['status']) === 'completed') {
        header("Location: order.php?error=completed_order");
        exit;
    }

    mysqli_query($conn, "UPDATE orders SET status = 'cancel' WHERE id = $order_id");
    header("Location: order.php?success=cancel");
    exit;
}

/* =========================
   FILTER TANGGAL (1 sistem)
========================= */
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

$whereTanggal = "";
$labelPeriode = "";

if (!empty($from) && !empty($to)) {
    // amanin input date (YYYY-MM-DD) -> tambah jam
    $from_dt = mysqli_real_escape_string($conn, $from . " 00:00:00");
    $to_dt   = mysqli_real_escape_string($conn, $to . " 23:59:59");

    $whereTanggal = " AND o.created_at BETWEEN '$from_dt' AND '$to_dt' ";
    $labelPeriode = date('d-m-Y', strtotime($from)) . " s/d " . date('d-m-Y', strtotime($to));
}

// =========================
// FILTER STATUS (dropdown)
// =========================
$statusFilter = $_GET['status'] ?? '';  // contoh: completed / shipped / pending / etc
$statusFilter = strtolower(trim($statusFilter));

$whereStatus = "";
if ($statusFilter !== '' && in_array($statusFilter, ['pending','processing','shipped','completed','cancel'])) {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $whereStatus = " AND LOWER(o.status) = '$safeStatus' ";
}

/* =========================
   COUNT ORDER PER STATUS
========================= */
$q_status = mysqli_query($conn, "SELECT LOWER(o.status) AS status, COUNT(*) AS total FROM orders o WHERE 1=1
    $whereTanggal
    GROUP BY LOWER(o.status)
");

$statusCount = [];
$totalAll = 0;

while ($row = mysqli_fetch_assoc($q_status)) {
    $statusCount[$row['status']] = (int)$row['total'];
    $totalAll += (int)$row['total'];
}

$allStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancel'];
foreach ($allStatuses as $st) {
    if (!isset($statusCount[$st])) $statusCount[$st] = 0;
}

/* =========================
   SUMMARY PERIODE (COMPLETED)
   - omzet periode (completed)
   - jumlah order periode (completed)
========================= */
$periode_order_all = 0;       // SEMUA order (kecuali cancel) dalam periode
$periode_omzet_completed = 0; // OMZET hanya completed dalam periode

if ($from && $to) {

    // 1) Jumlah order periode: semua status kecuali cancel
    $qOrderAll = mysqli_query($conn, "SELECT COUNT(*) AS total_order FROM orders o WHERE LOWER(o.status) != 'cancel'
        $whereTanggal
    ");
    $rAll = mysqli_fetch_assoc($qOrderAll);
    $periode_order_all = (int)($rAll['total_order'] ?? 0);

    // 2) Omzet periode: hanya completed
    $qOmzet = mysqli_query($conn, "SELECT COALESCE(SUM(o.total_harga),0) AS omzet FROM orders o WHERE LOWER(o.status) = 'completed'
        $whereTanggal
    ");
    $rOmzet = mysqli_fetch_assoc($qOmzet);
    $periode_omzet_completed = (int)($rOmzet['omzet'] ?? 0);
}

/* =========================
   TOTAL TOKO (SEMUA COMPLETED)
========================= */
$qTotalToko = mysqli_query($conn, "SELECT COALESCE(SUM(total_harga),0) AS omzet FROM orders WHERE LOWER(status) = 'completed'
");
$omzet_toko_completed = (int)(mysqli_fetch_assoc($qTotalToko)['omzet'] ?? 0);

/* =========================
   PRODUK TERJUAL (PERIODE, COMPLETED)
========================= */
$produkTerjual = null;
$total_qty = 0;

if ($from && $to) {
    $produkTerjual = mysqli_query($conn, "SELECT p.id AS product_id, p.name AS nama_produk,MAX(od.price) AS harga_satuan, SUM(od.quantity) AS total_qty, SUM(od.quantity * od.price) AS total_penjualan
        FROM order_details od
        JOIN orders o ON od.order_id = o.id
        JOIN products p ON od.product_id = p.id
        WHERE LOWER(o.status) = 'completed'
        $whereTanggal
        GROUP BY p.id, p.name
        ORDER BY total_qty DESC
    ");

    // total qty terjual (completed)
    $qQty = mysqli_query($conn, "
        SELECT COALESCE(SUM(od.quantity),0) AS total_qty
        FROM orders o
        JOIN order_details od ON od.order_id = o.id
        WHERE LOWER(o.status) = 'completed'
        $whereTanggal
    ");
    $qtyRow = mysqli_fetch_assoc($qQty);
    $total_qty = (int)($qtyRow['total_qty'] ?? 0);
}

/* =========================
   DATA ORDER (TABLE)
   Note: exclude cancel dari listing
========================= */
$orders = mysqli_query($conn, "
    SELECT o.*, u.name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE 1=1
    $whereTanggal
    $whereStatus
    ORDER BY o.id DESC
");


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin - Order</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

    <!-- ================= NAVBAR ================= -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-pink-600">Admin Dashboard</h1>

            <div class="flex space-x-6 text-gray-700 font-medium">
                <a href="dashboard.php" class="hover:text-pink-600">Dashboard</a>
                <a href="product.php" class="hover:text-pink-600">Product</a>
                <a href="order.php" class="text-pink-600">Order</a>
                <a href="customer.php" class="hover:text-pink-600">Customer</a>
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

        <!-- Header + Filter (rapi, kanan) -->
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <h1 class="text-2xl font-bold">Order Management</h1>

            <form method="GET" class="flex flex-col md:flex-row gap-3 items-end bg-white p-4 rounded-xl shadow">
                <div>
                    <label class="text-sm text-gray-600 block mb-1">Dari Tanggal</label>
                    <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"
                        class="border rounded-lg px-3 py-2">
                </div>

                <div>
                    <label class="text-sm text-gray-600 block mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"
                        class="border rounded-lg px-3 py-2">
                </div>

                <button type="submit"
                    class="h-[42px] px-6 bg-pink-600 text-white rounded-lg hover:bg-pink-700">
                    🔍 Cari
                </button>

                <?php if ($from && $to): ?>
                    <a href="order.php"
                        class="h-[42px] px-6 bg-gray-200 rounded-lg flex items-center hover:bg-gray-300">
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Summary Periode (muncul kalau filter aktif) -->
        <?php if ($from && $to): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-white p-5 rounded-xl shadow">
                    <p class="text-gray-500 text-sm">Pendapatan Periode (Completed)</p>
                    <p class="text-2xl font-bold text-green-600">
                        Rp <?= number_format($periode_omzet_completed, 0, ',', '.') ?>
                    </p>
                    <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($labelPeriode) ?></p>
                </div>

                <div class="bg-white p-5 rounded-xl shadow">
                    <p class="text-gray-500 text-sm">Jumlah Order Periode (Completed)</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <?= number_format($periode_order_all) ?> order
                    </p>
                    <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($labelPeriode) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Status cards -->
        <div class="bg-white p-6 rounded-xl shadow mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">

            <!-- FILTER STATUS -->
            <form method="GET" class="flex flex-wrap gap-4 items-end">

                <!-- keep from/to kalau ada -->
                <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
                <input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">

                <div>
                    <label class="text-sm text-gray-600 block mb-1">Filter Status</label>
                    <select name="status" class="border rounded-lg px-3 py-2 min-w-[200px]">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $statusFilter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $statusFilter == 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="shipped" <?= $statusFilter == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="completed" <?= $statusFilter == 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancel" <?= $statusFilter == 'cancel' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <button type="submit"
                    class="h-[42px] px-6 bg-pink-600 text-white rounded-lg hover:bg-pink-700">
                    🔍 Tampilkan
                </button>

                <a href="order.php"
                    class="h-[42px] px-6 inline-flex items-center justify-center bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Reset
                </a>
            </form>

            <!-- INFO FILTER AKTIF -->
            <div class="text-sm text-gray-500">
                <?php if ($statusFilter): ?>
                    Menampilkan status: <span class="font-semibold"><?= strtoupper($statusFilter) ?></span>
                <?php else: ?>
                    Menampilkan: <span class="font-semibold">SEMUA STATUS</span>
                <?php endif; ?>
            </div>
        </div>


        <!-- TABLE -->
        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-pink-500 text-white">
                    <tr>
                        <th class="p-2">No</th>
                        <th class="p-3 text-left">Order ID</th>
                        <th class="p-3 text-left">Customer</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-left">Total</th>
                        <th class="p-3 text-left">Pembayaran</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Tanggal</th>
                        <th class="p-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($orders) == 0): ?>
                        <tr>
                            <td colspan="9" class="p-6 text-center text-gray-500">
                                Belum ada order
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php while ($row = mysqli_fetch_assoc($orders)) : ?>
                        <?php $statusRow = strtolower($row['status'] ?? 'pending'); ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2 text-center"><?= $no++ ?></td>
                            <td class="p-3">#<?= (int)$row['id'] ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['email']) ?></td>
                            <td class="p-3 font-semibold text-pink-600">
                                Rp <?= number_format((int)$row['total_harga'], 0, ',', '.') ?>
                            </td>
                            <td class="p-3"><?= htmlspecialchars(strtoupper($row['metode_pembayaran'] ?? '-')) ?></td>

                            <td class="p-3">
                                <form action="update_status.php" method="POST">
                                    <input type="hidden" name="order_id" value="<?= (int)$row['id'] ?>">
                                    <select name="status" onchange="this.form.submit()"
                                        class="px-3 py-1 rounded-full text-xs border bg-white">
                                        <option value="pending" <?= $statusRow === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $statusRow === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped" <?= $statusRow === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="completed" <?= $statusRow === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancel" <?= $statusRow === 'cancel' ? 'selected' : '' ?>>Cancel</option>
                                    </select>
                                </form>
                            </td>

                            <td class="p-3"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td class="p-3 text-center">
                                <div class="flex gap-4 justify-center items-center">
                                    <a href="?delete=<?= (int)$row['id'] ?>"
                                        onclick="return confirm('Yakin ingin menghapus (cancel) order ini?')"
                                        class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded">
                                        Delete
                                    </a>
                                    <a href="order_detail.php?id=<?= (int)$row['id'] ?>"
                                        class="text-pink-500 hover:underline font-semibold">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>

        <!-- Produk terjual + Rekap (muncul kalau filter aktif) -->
        <?php if ($from && $to): ?>
            <div class="mt-8 bg-white rounded-xl shadow p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                    <h3 class="font-bold text-lg">📊 Produk Terjual (Completed)</h3>
                    <div class="text-sm text-gray-500"><?= htmlspecialchars($labelPeriode) ?></div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Produk</th>
                                <th class="p-2 text-center">Harga Satuan</th>
                                <th class="p-2 text-center">Qty Terjual</th>
                                <th class="p-2 text-right">Total Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$produkTerjual || mysqli_num_rows($produkTerjual) == 0): ?>
                                <tr>
                                    <td colspan="4" class="p-4 text-center text-gray-500">
                                        Tidak ada produk terjual di periode ini
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($p = mysqli_fetch_assoc($produkTerjual)): ?>
                                    <tr class="border-t">
                                        <td class="p-2"><?= htmlspecialchars($p['nama_produk']) ?></td>
                                        <td class="p-2 text-center">Rp <?= number_format((int)$p['harga_satuan']) ?></td>
                                        <td class="p-2 text-center"><?= (int)$p['total_qty'] ?></td>
                                        <td class="p-2 text-right">Rp <?= number_format((int)$p['total_penjualan']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Rekap bawah -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 border rounded-lg p-4">
                        <div class="text-sm text-gray-500">Total Qty Terjual (Completed)</div>
                        <div class="text-2xl font-bold"><?= number_format($total_qty, 0, ',', '.') ?></div>
                    </div>

                    <div class="bg-gray-50 border rounded-lg p-4">
                        <div class="text-sm text-gray-500">Pendapatan Periode (Completed)</div>
                        <div class="text-2xl font-bold text-green-600">
                            Rp <?= number_format($periode_omzet_completed, 0, ',', '.') ?>
                        </div>
                    </div>

                    <div class="bg-gray-50 border rounded-lg p-4">
                        <div class="text-sm text-gray-500">Jumlah Order Periode (Completed)</div>
                        <div class="text-2xl font-bold">
                            <?= number_format($periode_order_all) ?> order
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TOTAL TOKO (selalu tampil, paling bawah) -->
            <div class="mt-10 bg-white p-6 rounded-xl shadow flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <p class="text-gray-500 text-sm">Total Pendapatan Toko (Semua Order Completed)</p>
                    <p class="text-3xl font-bold text-green-700">
                        Rp <?= number_format($omzet_toko_completed, 0, ',', '.') ?>
                    </p>
                </div>
                <div class="text-sm text-gray-400">
                    *Tidak termasuk Pending / Processing / Shipped / Cancelled
                </div>
            </div>

            </div>
</body>

</html>