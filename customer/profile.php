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

/* =========================
   AMBIL DATA USER
========================= */
$result = mysqli_query($conn, "SELECT name, email,no_hp, provinsi FROM users WHERE user_id='$user_id'");
$users = mysqli_fetch_assoc($result);

// default value (biar ga warning)
$name   = $users['name'] ?? '';
$email  = $users['email'] ?? '';
$no_hp = $users['no_hp'] ?? '';
$provinsi = $users['provinsi'] ?? '';

/* =========================
   JIKA FORM DISUBMIT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $email  = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $no_hp  = mysqli_real_escape_string($conn, $_POST['no_hp'] ?? '');
    $provinsi = mysqli_real_escape_string($conn, $_POST['provinsi'] ?? '');

    $user_id_int = (int)$user_id;

    $sql = "UPDATE users 
            SET name='$name', email='$email', provinsi='$provinsi', no_hp='$no_hp' 
            WHERE user_id=$user_id_int";

    $ok = mysqli_query($conn, $sql);
    if (!$ok) {
        die("Gagal update: " . mysqli_error($conn));
    }

    header("Location: profile.php?success=1");
    exit;
}

$provinsiList = [
  "Aceh","Sumatera Utara","Sumatera Barat","Riau","Kepulauan Riau","Jambi","Sumatera Selatan","Bangka Belitung","Bengkulu","Lampung",
  "DKI Jakarta","Jawa Barat","Banten","Jawa Tengah","DI Yogyakarta","Jawa Timur",
  "Bali","Nusa Tenggara Barat","Nusa Tenggara Timur",
  "Kalimantan Barat","Kalimantan Tengah","Kalimantan Selatan","Kalimantan Timur","Kalimantan Utara",
  "Sulawesi Utara","Gorontalo","Sulawesi Tengah","Sulawesi Barat","Sulawesi Selatan","Sulawesi Tenggara",
  "Maluku","Maluku Utara",
  "Papua","Papua Barat","Papua Selatan","Papua Tengah","Papua Pegunungan","Papua Barat Daya"
];

$currentProvinsi = $users['provinsi'] ?? '';

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Cusomer Dashboard</title>
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
                <a href="profile.php" class="text-pink-600">Profile</a>
                <a href="order.php" class="hover:text-pink-600">Orders</a>
                <a href="../index.php" class="hover:text-pink-600">Home</a>

            </div>
        </div>
    </nav>



    <!-- ================= CONTENT ================= -->
    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- HEADER -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">👤 Update Profile</h2>
            <p class="text-sm text-gray-500">Update your personal information</p>
        </div>

        <div class="bg-white rounded-2xl shadow p-8 max-w-3xl">

            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-5 bg-green-100 text-green-700 px-4 py-3 rounded-lg">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-5 bg-red-100 text-red-700 px-4 py-3 rounded-lg">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="profile_update.php" method="POST" class="space-y-6">

                <!-- NAME -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Full Name
                    </label>
                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars($users['name']) ?>"
                        required
                        style="border: 2px solid gray;"
                        class="w-full rounded-lg border-gray-300 focus:ring-pink-500 focus:border-pink-500 px-4 py-2">
                </div>

                <!-- EMAIL -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email Address
                    </label>
                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($users['email']) ?>"
                        required
                        style="border: 2px solid gray;"

                        class="w-full rounded-lg border-gray-300 focus:ring-pink-500 focus:border-pink-500 px-4 py-2">
                </div>

                <!-- PASSWORD -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        New Password
                    </label>
                    <input
                        type="password"
                        name="password"
                        style="border: 2px solid gray;"

                        placeholder="Leave blank if not changing"
                        class="w-full rounded-lg border-gray-300 focus:ring-pink-500 focus:border-pink-500 px-4 py-2">
                </div>

                <!-- NO HP -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"> No. HP / WhatsApp </label>
                    <input
                        type="text"
                        name="no_hp"
                        value="<?= htmlspecialchars($no_hp) ?>"
                        placeholder="08xxxxxxxxxx"
                        required
                        style="border: 2px solid gray;"
                        class="w-full rounded-lg px-4 py-2">
                </div>

                <!-- alamat customer -->
                <div>
                    <label class="block font-medium mb-1">Provinsi</label>
                    <select name="provinsi" required class="w-full border rounded p-2">
                        <option value="">-- Pilih Provinsi --</option>
                        <?php foreach ($provinsiList as $p): ?>
                            <option value="<?= htmlspecialchars($p) ?>" <?= ($currentProvinsi === $p) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                </div>

                <!-- ACTIONS -->
                <div class="flex gap-4 pt-4">
                    <button type="submit" name="save" value="1"
                        class="bg-pink-500 hover:bg-pink-600 text-white px-6 py-2 rounded-lg font-semibold">
                        💾 Save Changes
                    </button>

                    <a href="dashboard.php"
                        class="px-6 py-2 rounded-lg border text-gray-600 hover:bg-gray-100">
                        Cancel
                    </a>
                </div>

            </form>

        </div>
    </div>

</body>

</html>