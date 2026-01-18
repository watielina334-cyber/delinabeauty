<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About Glad2Glow</title>
    <script src="https://cdn.tailwindcss.com"></script>

</head>
<style>
    :root {
        --pink: #FFC8DD;
        --mint: #CDECE5;
        --beige: #FF6EE;
        --gray: #f6f7fb;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: system-ui, sans-serif;
        background: var(--gray);
        color: #333;
    }

    .page {
        width: 100%;
    }

    .hero {
        background: white;
        padding: 80px 20px;
    }

    .hero-container {
        max-width: 1200px;
        margin: auto;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        align-items: center;
    }

    .hero-text h1 {
        font-size: 40px;
        margin: 20px 0;
    }

    .hero-text p {
        color: #666;
    }

    .badge {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 20px;
        background: var(--mint);
        font-size: 15px;
    }

    .hero-actions {
        margin-top: 20px;
        display: flex;
        gap: 20px;
    }

    .btn-primary {
        background: var (--pink);
        padding: 12px 25px;
        border-radius: 15px;
        text-decoration: none;
        color: #000;
        font-weight: 600;
    }

    .link-muted {
        color: #8606;
        text-decoration: none;
    }

    .link tags {
        margin-top: 20px;
        display: flex;
        gap: 10px;
    }

    .hero-tags span {
        background: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .05);
    }

    .hero img {
        text-align: center;
    }

    .hero-image img {
        width: 250px;
    }

    .section {
        padding: 80px 20px;
    }

    .section-grid {
        max-width: 1200px;
        margin: auto;
        display: grid;
        grid-column: 2fr 1fr-columns 2fr 1fr;
    }

    .card-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-top: 20px;
    }

    .card-grid.three {
        grid-template-columns: repeat(3, 1fr);
    }

    .card {
        background: white;
        padding: 20px;
        border-radius: 15px;
    }

    .card.highlight {
        box-shadow: 0 6px 20px rgba(0, 0, 0, .08)
    }

    .card.highlight {
        box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
    }

    .card-mint {
        background: var(--version);
    }

    .card.pink {
        background: var(--pink);
    }

    .card.black {
        background: var(--coding);
    }

    @media (max-width: 900px) {

        .hero-container,
        section-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<body>

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


    <main class="page">

        <!-- HERO -->
        <section class="hero">
            <div class="hero-container">

                <div class="hero-text">
                    <span class="badge">New • Clean & Fun</span>
                    <h1>Glad2Glow — Simple skincare, joyful results</h1>
                    <p>
                        Perawatan kulit yang minimalis namun playful. Formulasi lembut,
                        efektif, dan mudah dimasukkan ke rutinitas harian.
                    </p>

                    <div class="hero-actions">
                        <a href="product.php" class="btn-primary">Shop Now</a>
                    </div>

                    <div class="hero-tags">
                        <span>Dermatology-friendly</span>
                        <span>Non-comedogenic</span>
                        <span>No Parabens</span>
                    </div>
                </div>

                <div class="hero-image">
                    <img src="./assets/serum_best_seller.webp" alt="Glad2Glow">
                    <p>Glow Serum — Bestseller</p>
                </div>

            </div>
        </section>

        <!-- WHO WE ARE -->
        <section class="section">
            <div class="section-grid">
                <div>
                    <h2>Who We Are</h2>
                    <p>
                        Glad2Glow hadir dari ide sederhana: merawat kulit tidak harus kompleks.
                    </p>

                    <div class="card-grid">
                        <div class="card">
                            <h3>Mudah & Efektif</h3>
                            <p>Produk kami cocok untuk semua rutinitas.</p>
                        </div>
                        <div class="card">
                            <h3>Aesthetic & Playful</h3>
                            <p>Desain pastel yang clean dan fun.</p>
                        </div>
                    </div>
                </div>

                <aside class="card highlight">
                    <h3>Quick Facts</h3>
                    <ul>
                        <li>✨ Berdiri: 2022</li>
                        <li>🌿 Clean formulas</li>
                        <li>💬 #Glad2GlowFam</li>
                        <li>♻️ Eco packaging</li>
                    </ul>
                </aside>
            </div>
        </section>

        <!-- MISSION -->
        <section class="section alt">
            <div class="section-grid">
                <div>
                    <h2>Our Mission</h2>
                    <p>
                        Membuat skincare yang mudah dipakai, lembut, dan efektif.
                    </p>

                    <div class="card-grid three">
                        <div class="card mint">
                            <h3>Easy</h3>
                            <p>Tanpa langkah ribet.</p>
                        </div>
                        <div class="card pink">
                            <h3>Gentle</h3>
                            <p>Aman untuk kulit sensitif.</p>
                        </div>
                        <div class="card beige">
                            <h3>Affordable</h3>
                            <p>Harga bersahabat.</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3>Glow Philosophy</h3>
                    <p>
                        Kebiasaan kecil menghasilkan perubahan besar.
                    </p>
                </div>
            </div>
        </section>

    </main>

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