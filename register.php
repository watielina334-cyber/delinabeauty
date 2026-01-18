<?php
session_start();
include './config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['email'])) {

    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $role     = 'customer';

    // cek email
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

    if (mysqli_num_rows($cek) > 0) {
        $error = "Email sudah terdaftar!";
    } else {

        // hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // insert user
        mysqli_query($conn, "INSERT INTO users 
            (name, email, password, role)
            VALUES
            ('$name', '$email', '$password_hash', '$role')
        ");

        // set session
        $_SESSION['user_id'] = mysqli_insert_id($conn);
        $_SESSION['name']    = $name;
        $_SESSION['email']   = $email;
        $_SESSION['role']    = $role;

        echo "<script>
            alert('Register berhasil! Selamat datang.');
            window.location.href='index.php';
        </script>";
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Sign Up | Delina Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body class="bg-pink-50 min-h-screen flex items-center justify-center">

    <div class="bg-white w-full max-w-sm p-8 rounded-xl shadow-lg">

        <h2 class="text-2xl font-bold text-center text-pink-600 mb-6">
            Register
        </h2>

        <?php if (isset($error)) : ?>
            <div class="mb-4 p-3 text-sm text-red-600 bg-red-100 rounded-lg">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">

            <input
                type="email"
                name="email"
                placeholder="Email aktif"
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-400">

            <input
                type="text"
                name="name"
                placeholder="Nama lengkap"
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
                class="w-full bg-pink-500 hover:bg-pink-600 text-white py-3 rounded-lg font-semibold transition">
                Daftar Sekarang
            </button>

        </form>

        <div class="text-center mt-6 text-sm text-gray-600">
            Sudah punya akun?
            <a href="login.php" class="text-pink-500 font-semibold hover:underline">
                Login
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