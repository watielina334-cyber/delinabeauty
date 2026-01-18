<?php
session_start();
include './config/database.php';

/* ===============================
   JIKA SUDAH LOGIN -> REDIRECT SESUAI ROLE
================================ */
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'kurir') {
        header("Location: kurir/index.php");
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
}

/* ===============================
   PROSES LOGIN
================================ */
if (isset($_POST['login'])) {

    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // ambil user berdasarkan email
    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

    if (mysqli_num_rows($query) == 1) {

        $user = mysqli_fetch_assoc($query);

        // cek password hash
        if (password_verify($password, $user['password'])) {

            // set session
            $_SESSION['user_id'] = $user['user_id']; // pastikan field ini bener
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            // redirect sesuai role (INI YANG KAMU BUTUH)
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
                exit;
            } elseif ($user['role'] === 'kurir') {
                header("Location: kurir/index.php");
                exit;
            } else {
                header("Location: index.php");
                exit;
            }

        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Email tidak ditemukan!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-pink-50 min-h-screen flex items-center justify-center">

    <div class="bg-white w-full max-w-sm p-8 rounded-xl shadow-lg">

        <h3 class="text-2xl font-bold text-center text-pink-600 mb-6">
            Login
        </h3>

        <!-- <?php if (isset($error)) : ?>
            <div class="mb-4 p-3 text-sm text-red-600 bg-red-100 rounded-lg">
                <?= $error ?>
            </div>
        <?php endif; ?> -->

        <form method="POST" class="space-y-4">

            <input
                type="email"
                name="email"
                placeholder="Email aktif anda"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-400">

            <div class="relative">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Password"
                    required
                    class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-400">

                <button
                    type="button"
                    onclick="togglePassword()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-pink-500">
                    <i id="toggleIcon" class="fa-solid fa-eye"></i>
                </button>
            </div>
            
            <button
                type="submit"
                name="login"
                class="w-full bg-pink-500 hover:bg-pink-600 text-white py-3 rounded-lg font-semibold transition">
                Login
            </button>
        </form>

        <div class="text-center mt-6 text-sm text-gray-600">
            Belum Punya Akun?
            <a href="register.php" class="text-pink-500 font-semibold hover:underline">
                Register
            </a>
        </div>

    </div>


    <!-- JAVASCRIPT TOGGLE PASSWORD -->
    <script>
        function togglePassword() {
            const password = document.getElementById("password");
            const icon = document.getElementById("toggleIcon");

            if (password.type === "password") {
                password.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                password.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>

</body>

</html>