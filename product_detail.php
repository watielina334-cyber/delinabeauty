<?php
session_start();
require './config/database.php';

// ambil id produk dari URL
if (!isset($_GET['id'])) {
    die("Produk tidak ditemukan");
}

$id = $_GET['id'];

// ambil data produk
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$products = $stmt->get_result()->fetch_assoc();

?>

<!DOCTYPE html>
<html>

<head>
    <title><?= $products['name'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            min-height: 100vh;
            display:flex;
            flex-direction:column;

        }

        .detail-container {
            max-width: 1500px;
            margin: 40px auto;
            background: #fff;
            padding: 20px;
            display: flex;
            gap: 40px;
            font-family: Arial, sans-serif;
        }

        .product-image img {
            width: 350px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgb(0, 0, 0, .1);
        }

        .btn-group button {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
        }

        .qty {
            margin-bottom: 10px;
        }

        .cart {
            background: #ff9800;
        }

        .buy {
            background: #e53935;
        }
    </style>

</head>

<body>

    <!-- ================= HEADER ================= -->
    <header class="fixed top-0 inset-x-0 z-50 bg-white shadow">
        <nav class="flex items-center justify-between h-16 px-4 lg:px-10 ">

            <!-- LOGO -->
            <a href="index.php">
                <img src="./assets/logo_delina.png" class="h-16">
            </a>

            <!-- MENU DESKTOP -->
            <div class="hidden lg:flex gap-16">
                <a href="index.php" class="hover:bg-pink-500 px-4 py-2 rounded-full hover:text-white transition">Home</a>
                <a href="about.php" class="hover:bg-pink-500 px-4 py-2 rounded-full hover:text-white transition">About</a>
                <a href="product.php" class="hover:bg-pink-500 px-4 py-2 rounded-full hover:text-white transition">Product</a>
                <a href="contact.php" class="hover:bg-pink-500 px-4 py-2 rounded-full hover:text-white transition">Contact</a>
            </div>

            <!-- RIGHT DESKTOP -->
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="hidden lg:flex gap-4">
                    <a href="login.php">Login</a>
                </div>

            <?php else: ?>
                <?php
                // Tentukan dashboard berdasarkan role
                $dashboardLink = 'customer/profile.php';

                if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                    $dashboardLink = 'admin/dashboard.php';
                }
                ?>

                <div class="hidden lg:flex gap-5 items-center">
                    <a href="<?= $dashboardLink ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" fill="currentColor" class="bi bi-grid" viewBox="0 0 16 16">
                            <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z" />
                        </svg>
                    </a>

                    <a href="cart.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" fill="currentColor" class="bi bi-cart3" viewBox="0 0 16 16">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l.84 4.479 9.144-.459L13.89 4zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2" />
                        </svg>
                    </a>

                    <a href="logout.php" class="text-red-500">Logout</a>
                </div>
            <?php endif; ?>

            <!-- HAMBURGER (MOBILE) -->
            <button id="mobileMenuOpen" class="lg:hidden">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M3 6h18M3 12h18M3 18h18" stroke-linecap="round" />
                </svg>
            </button>

        </nav>
    </header>

    <!-- ================= MOBILE MENU ================= -->
    <div id="mobileMenu" class="fixed inset-0 bg-white z-50 hidden p-6">

        <button id="mobileMenuClose" class="mb-6">
            ✕
        </button>

        <div class="space-y-4 text-lg">
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="product.php">Product</a>
            <a href="contact.php">Contact</a>
        </div>

        <div class="mt-6 border-t pt-4">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="block">Login</a>
            <?php else: ?>
                <a href="logout.php" class="block text-red-500">Logout</a>
            <?php endif; ?>
        </div>

    </div>


    <div class="detail-container " style="margin-top: 100px; justify-content:center;">

        <!-- GAMBAR -->
        <div class="product-image">
            <img src="./assets/<?= $products['image'] ?>" alt="">
        </div>

        <!-- INFO produk -->
        <div class="product-info">
            <h2><?= $products['name'] ?></h2>
            <div class="desc"><br>
                <?= nl2br($products['description']) ?>
            </div><br>
            <div class="price">
                Rp <?= number_format($products['price']) ?>
            </div>
            <div>
                Stok Tersedia: <?= $products['stock'] ?>
            </div>

            <form action="cart_add.php" method="POST">
                <input type="hidden" name="product_id" value="<?= $products['id'] ?>">

                <!-- QTY -->
                <div class="qty mb-4">
                    <label>Jumlah :</label>
                    <input type="number"
                        id="qty_input"
                        name="quantity"
                        value="1"
                        min="1"
                        max="<?= $products['stock'] ?>"
                        class="border border-black rounded text-center w-16">
                </div>

                <div class="flex gap-3">
                    <!-- ADD TO CART -->
                    <form action="cart_add.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $products['id'] ?>">
                        <input type="hidden" name="quantity" id="qty_cart">
                        <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-black px-4 py-2 rounded">
                            🛒 Tambah ke Keranjang
                        </button>
                    </form>

                    <!-- BUY NOW -->
                    <form action="buy_now.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $products['id'] ?>">
                        <input type="hidden" name="quantity" id="qty_buy">
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                            ⚡ Beli Sekarang
                        </button>
                    </form>
                </div>
            </form>
                <script>
                    const qtyInput = document.getElementById('qty_input');
                    const qtyCart = document.getElementById('qty_cart');
                    const qtyBuy = document.getElementById('qty_buy');

                    function syncQty() {
                        qtyCart.value = qtyInput.value;
                        qtyBuy.value = qtyInput.value;
                    }

                    qtyInput.addEventListener('input', syncQty);
                    syncQty();
                </script>


                <!-- FOOTER DELINA BEAUTY -->
                <footer class="bg-pink-100 text-gray-700 mt-12 py-10 px-6 border-t border-pink-200">
                    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">

                        <!-- Brand -->
                        <div>
                            <h2 class="text-2xl font-bold text-pink-600">Delina Beauty</h2>
                            <p class="mt-3 text-sm text-gray-600 leading-relaxed">
                                Temukan skincare terbaik untuk kulit sehat, glowing, dan percaya diri ✨
                            </p>
                        </div>

                        <!-- Quick Links -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3 text-gray-800">Menu</h3>
                            <ul class="space-y-2 text-sm">
                                <li><a href="index.php" class="hover:text-pink-500">Home</a></li>
                                <li><a href="product.php" class="hover:text-pink-500">Produk</a></li>
                            </ul>
                        </div>

                        <!-- Contact -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3 text-gray-800">Kontak</h3>
                            <ul class="text-sm space-y-2">
                                <li>Email: <a href="mailto:delinabeauty@gmail.com" class="hover:text-pink-500">delinabeauty@gmail.com</a></li>
                                <li>WhatsApp: <a href="#" class="hover:text-pink-500">0823-7855-9918</a></li>
                                <li>Instagram: <a href="#" class="hover:text-pink-500">@delinabeauty.id</a></li>
                            </ul>
                        </div>

                        <!-- Support -->
                        <div>
                            <h3 class="text-black font-semibold mb-4">Support</h3>
                            <ul class="space-y-2 text-black">
                                <li><a href="faq.php" class="hover:text-indigo-600">FAQ</a></li>
                                <li><a href="#" class="hover:text-indigo-600">Check Order</a></li>
                            </ul>
                        </div>

                        <!-- Company -->
                        <div>
                            <h3 class="text-black font-semibold mb-4">Company</h3>
                            <ul class="space-y-2 text-black">
                                <li><a href="about.php" class="hover:text-indigo-600">About</a></li>
                                <li><a href="career.php" class="hover:text-indigo-600">Carrier</a></li>
                            </ul>
                        </div>

                        <!-- Legal -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3 text-gray-800">Legal</h3>
                            <ul class="text-sm space-y-2">
                                <li><a href="terms.php" class="hover:text-indigo-600">Terms of service</a></li>
                                <li><a href="privacy.php" class="hover:text-indigo-600">Privacy policy</a></li>
                                <li><a href="license.php" class="hover:text-indigo-600">License</a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Bottom -->
                    <div class="border-t border-pink-200 mt-10 pt-5 text-center text-sm text-gray-600">
                        © 2025 Delina Beauty — Glow with Confidence ✨
                    </div>
                </footer>
    </div>
</body>

</html>