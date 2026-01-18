<?php
session_start();
include '../config/database.php';

// ================= PROTEKSI ADMIN =================
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$no = 1;

// ambil input filter
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';
$qRaw = $_GET['q'] ?? '';
$q    = mysqli_real_escape_string($conn, $qRaw);

// base where
$where = "WHERE role='customer'";

// filter search
if ($qRaw !== '') {
    $where .= " AND (name LIKE '%$q%' OR email LIKE '%$q%')";
}

// filter tanggal
$isFilter = (!empty($from) && !empty($to));
if ($isFilter) {
    $from_dt = mysqli_real_escape_string($conn, $from . " 00:00:00");
    $to_dt   = mysqli_real_escape_string($conn, $to . " 23:59:59");
    $where  .= " AND created_at BETWEEN '$from_dt' AND '$to_dt'";
}

// ================= DELETE CUSTOMER =================
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE user_id=$user_id AND role='customer'");
    header("Location: customer.php");
    exit;
}

// ================= DATA CUSTOMER (1 QUERY FINAL) =================
$customers = mysqli_query($conn, "
    SELECT user_id, name, email, created_at
    FROM users
    $where
    ORDER BY user_id DESC
");

// ================= KARTU SUMMARY (tidak terfilter) =================
$totalCustomer = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='customer'"))['total'] ?? 0);

$todayCustomer = (int)(mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM users 
    WHERE role='customer' AND DATE(created_at)=CURDATE()
"))['total'] ?? 0);

$monthCustomer = (int)(mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM users 
    WHERE role='customer' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())
"))['total'] ?? 0);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customer Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <!-- navbar content -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-pink-600">
                Admin Dashboard
            </h1>

            <div class="flex space-x-6 text-gray-700 font-medium">
                <a href="dashboard.php" class="hover:text-pink-600">Dashboard</a>
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
    <!-- komponen kolom search dari tanggal ke tanggal -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 class="text-xl font-bold">Customer Management</h2>

            <form method="GET" class="flex gap-2 items-end">
                <div>
                    <label class="block text-xs font-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="from"
                        value="<?= htmlspecialchars($_GET['from'] ?? '') ?>"
                        class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="to"
                        value="<?= htmlspecialchars($_GET['to'] ?? '') ?>"
                        class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-pink-400">
                </div>

                <button class="bg-pink-500 hover:bg-pink-600 text-white px-4 py-2 rounded-lg">
                    🔍 Filter
                </button>
                <a href="customer.php"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
                    Reset
                </a>
            </form>
        </div>

        <!-- komponen daftar customer berdasarkan kategori -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow p-5">
                <p class="text-sm text-gray-500">Total Customer</p>
                <p class="text-2xl font-bold text-pink-600"><?= $totalCustomer ?></p>
            </div>

            <div class="bg-white rounded-xl shadow p-5">
                <p class="text-sm text-gray-500">Daftar Hari Ini</p>
                <p class="text-2xl font-bold text-blue-600"><?= $todayCustomer ?></p>
            </div>

            <div class="bg-white rounded-xl shadow p-5">
                <p class="text-sm text-gray-500">Daftar Bulan Ini</p>
                <p class="text-2xl font-bold text-green-600"><?= $monthCustomer ?></p>
            </div>
        </div>

        <!-- komponen filter kolom search customer -->
        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table class="w-full border-collapse">
                <thead class="bg-pink-500 text-white">
                    <tr>
                        <th class="p-2">No</th>
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Nama</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-left">Tanggal Daftar</th>
                        <th class="p-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($customers) == 0): ?>
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-500">
                                Belum ada customer
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php while ($row = mysqli_fetch_assoc($customers)) : ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2 text-center"><?= $no++ ?></td>
                            <td class="p-3"><?= $row['user_id'] ?></td>
                            <td class="p-3"><?= $row['name'] ?></td>
                            <td class="p-3"><?= $row['email'] ?></td>
                            <td class="p-3">
                                <?= date('d M Y', strtotime($row['created_at'])) ?>
                            </td>
                            <td class="p-3 text-center">
                                <a href="?delete=<?= $row['user_id'] ?>"
                                    onclick="return confirm('Yakin hapus customer ini?')"
                                    class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 font-semibold">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>

</html>