<?php
session_start();
require './config/database.php';

// buat mencari produk per kategori
$kategori_id = $_GET['kategori_id'] ?? null;
$keyword     = $_GET['q'] ?? null;

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$types  = "";

// filter kategori
if ($kategori_id) {
    $sql .= " AND kategori_id = ?";
    $params[] = $kategori_id;
    $types .= "i";
}

// filter search
if ($keyword) {
    $sql .= " AND name LIKE ?";
    $params[] = "%$keyword%";
    $types .= "s";
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Product</title>
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            feather.replace();

            const btn = document.getElementById("searchBtn");
            const dropdown = document.getElementById("searchDropdown");

            btn.addEventListener("click", (e) => {
                e.stopPropagation();

                dropdown.classList.toggle("hidden");
                setTimeout(() => {
                    dropdown.classList.toggle("opacity-0");
                    dropdown.classList.toggle("scale-95");
                }, 10);

                dropdown.querySelector("input").focus();
            });

            // klik di luar → nutup
            document.addEventListener("click", () => {
                if (!dropdown.classList.contains("hidden")) {
                    dropdown.classList.add("opacity-0", "scale-95");
                    setTimeout(() => dropdown.classList.add("hidden"), 200);
                }
            });
        });
    </script>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>

        /* RESET */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f3f4f6;
            padding-top: 80px;
        }

        /* ================= HEADER ================= */
        header {
            position: fixed;
            top: 0;
            width: 100%;
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .1);
            z-index: 100;
        }

        nav {
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }

        nav img {
            height: 50px;
        }

        .menu {
            display: flex;
            gap: 30px;
        }

        .menu a {
            text-decoration: none;
            color: #111;
            padding: 8px 16px;
            border-radius: 20px;
        }

        .menu a:hover {
            background: #ec4899;
            color: white;
        }

        /* ================= CATEGORY ================= */
        .category-bar {
            position: relative;
            z-index: 9999;
            margin-top: 70px;
            background: #fce7f3;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }

        .category-bar ul {
            display: flex;
            justify-content: center;
            gap: 40px;
            padding: 15px;
            list-style: none;
        }

        .category-bar a {
            text-decoration: none;
            color: #374151;
            font-weight: bold;
        }

        .category-bar a:hover {
            color: #ec4899;
        }

        #searchDropdown.hidden {
            display: none;
            pointer-events: none;
        }

        /* ================= PRODUCT GRID ================= */
        .product-grid {
            max-width: 1200px;
            margin: 40px auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            padding: 0 20px;
        }

        .product-card {
            background: white;
            padding: 15px;
            border-radius: 14px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .1);
            transition: .2s;
        }

        .product-card:hover {
            transform: translateY(-4px);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-card h3 {
            margin-top: 10px;
            font-size: 14px;
            color: #111;
        }

        .product-card p {
            margin-top: 6px;
            color: #ec4899;
            font-weight: bold;
        }

        .product-card a {
            display: block;
            margin-top: 12px;
            text-align: center;
            background: #ec4899;
            color: white;
            padding: 10px;
            border-radius: 10px;
            text-decoration: none;
        }

        .product-card a:hover {
            background: #db2777;
        }

        /* ================= FOOTER ================= */
        footer {
            background: #fce7f3;
            margin-top: 60px;
            padding: 40px 20px;
            font-size: 14px;
        }

        footer h3 {
            margin-bottom: 10px;
        }

        footer a {
            color: #111;
            text-decoration: none;
        }

        footer a:hover {
            color: #ec4899;
        }
    </style>
</head>

<body class="bg-gray-100">

    <!-- ================= HEADER ================= -->
    <header class="w-full bg-[#f6dada] bg-white">
        <nav class="relative z-50 flex items-center justify-between px-6 py-3">
            <!-- LOGO -->
            <a href="index.php" class="flex items-center">
                <img src="./assets/logo_delina.png" class="h-12 w-auto">
            </a>

            <!-- MENU DESKTOP -->
            <div class="hidden lg:flex gap-6 mx-auto text-pink-700 font-medium">
                <a href="index.php" class="px-4 py-2 rounded-lg hover:bg-pink-500 hover:text-white transition">Home</a>
                <a href="about.php" class="px-4 py-2 rounded-lg hover:bg-pink-500 hover:text-white transition">About</a>
                <a href="product.php" class="px-4 py-2 rounded-lg hover:bg-pink-500 hover:text-white transition">Product</a>
                <a href="contact.php" class="px-4 py-2 rounded-lg hover:bg-pink-500 hover:text-white transition">Contact</a>
            </div>

            <!-- SEARCH -->
            <div class="flex items-center gap-4">
                <div class="relative">

                    <!-- BUTTON ICON -->
                    <button id="searchBtn"
                        class="text-gray-500 hover:text-pink-500">
                        <i data-feather="search"></i>
                    </button>

                    <!-- DROPDOWN SEARCH -->
                    <div id="searchDropdown"
                        class="absolute right-0 mt-3 w-72
                hidden opacity-0 scale-95
                transition-all duration-200
                bg-white border border-gray-200
                rounded-lg shadow-lg z-50">

                        <form action="product.php" method="GET">
                            <input
                                type="text"
                                name="q"
                                placeholder="Search product..."
                                class="w-full px-4 py-2.5 rounded-lg
                    focus:outline-none focus:ring-2
                    focus:ring-pink-300">
                        </form>
                    </div>
                </div>

                <!-- RIGHT DESKTOP -->
                <div class="flex items-center gap-4">


                </div>


                <!-- menu login -->
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="hidden lg:flex gap-4 text-[#6b4b4b">
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
            </div>
        </nav><br>
        <!-- CATEGORY BAR (sticky + active) -->
        <div class="sticky top-0 z-40 bg-white/80 backdrop-blur border-b border-pink-100 mb-8">
            <div class="max-w-7xl mx-auto px-4">
                <ul class="flex gap-2 py-3 overflow-x-auto no-scrollbar justify-start sm:justify-center">
                    <?php
                    $active = $_GET['kategori_id'] ?? '';
                    $cats = [
                        4 => 'Cleanser',
                        2 => 'Toner',
                        1 => 'Serum',
                        3 => 'Moisturizer',
                        5 => 'Sunscreen',
                        6 => 'Mask',
                    ];
                    foreach ($cats as $id => $label):
                        $isActive = ((string)$active === (string)$id);
                    ?>
                        <li>
                            <a href="/delinabeauty/product.php?kategori_id=<?= $id ?>"
                                class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-semibold transition
                    <?= $isActive ? 'bg-pink-500 text-white shadow' : 'bg-pink-50 text-pink-700 hover:bg-pink-100' ?>">
                                <?= $label ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <style>
            .no-scrollbar::-webkit-scrollbar {
                display: none;
            }

            .no-scrollbar {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
        </style>

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

    <!-- grid product skincare -->

    <div class="pt-10">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 mt-10">
            <?php while ($p = $result->fetch_assoc()): ?>
                <a href="product_detail.php?id=<?= (int)$p['id'] ?>"
                    class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition overflow-hidden flex flex-col">

                    <!-- Gambar Produk (lebih rapi, full, konsisten) -->
                    <div class="relative aspect-[4/3] bg-gray-100 overflow-hidden">
                        <img
                            src="./assets/<?= htmlspecialchars($p['image']) ?>"
                            onerror="this.onerror=null;this.src='./assets/no-image.png';"
                            class="h-full w-full object-contain bg-white group-hover:scale-[1.02] transition duration-300"
                            alt="<?= htmlspecialchars($p['name']) ?>" />
                    </div>

                    <!-- Konten -->
                    <div class="p-5 flex flex-col flex-1">
                        <h3 class="text-sm font-semibold text-gray-900 leading-snug line-clamp-2 group-hover:text-pink-600 transition">
                            <?= htmlspecialchars($p['name']) ?>
                        </h3>

                        <p class="mt-2 text-lg font-bold text-gray-900">
                            Rp <?= number_format((float)$p['price'], 0, ',', '.') ?>
                        </p>

                        <div class="flex-1"></div>

                        <div class="mt-4">
                            <span class="inline-flex w-full items-center justify-center rounded-xl bg-pink-500 px-4 py-2 text-sm font-semibold text-white
                       group-hover:bg-pink-600 transition">
                                Lihat Detail →
                            </span>
                        </div>
                    </div>

                </a>
            <?php endwhile; ?>
        </div>
    </div>

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
</body>

</html>