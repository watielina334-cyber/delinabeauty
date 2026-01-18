<?php
session_start();
include './config/database.php';

// buat best products otomatis
// helper cek prefix tanpa str_starts_with (biar aman untuk PHP lama)
function startsWith($str, $prefix) {
  return substr($str, 0, strlen($prefix)) === $prefix;
}

function productImageUrl($img) {
  if (empty($img)) return './assets/no-image.png';

  // kalau sudah URL
  if (preg_match('~^https?://~', $img)) return $img;

  // kalau sudah berupa path relatif
  if (startsWith($img, './') || startsWith($img, '../')) return $img;

  // kalau sudah mengandung folder (uploads/..., assets/..., dll)
  if (strpos($img, '/') !== false) return $img;

  // kalau cuma nama file, sesuaikan folder tempat gambar kamu
  return './assets/' . $img; // <-- ubah ke ./uploads/ kalau file ada di uploads
}


function getBestProducts($conn)
{
  // 1) coba dari order_items
  $q1 = mysqli_query($conn, "
    SELECT p.id, p.name, p.price, p.image, SUM(oi.quantity) AS sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE o.status != 'cancel'
    GROUP BY p.id
    ORDER BY sold DESC
    LIMIT 5
  ");
  if ($q1 && mysqli_num_rows($q1) > 0) return $q1;

  // 2) fallback: order_details
  $q2 = mysqli_query($conn, "
    SELECT p.id, p.name, p.price, p.image, SUM(od.quantity) AS sold
    FROM order_details od
    JOIN orders o ON od.order_id = o.id
    JOIN products p ON od.product_id = p.id
    WHERE o.status != 'cancel'
    GROUP BY p.id
    ORDER BY sold DESC
    LIMIT 5
  ");
  if ($q2 && mysqli_num_rows($q2) > 0) return $q2;

  // 3) fallback terakhir: produk terbaru (biar tetap tampil)
  return mysqli_query($conn, "
    SELECT id, name, price, image
    FROM products
    ORDER BY id DESC
    LIMIT 5
  ");
}

$bestProducts = getBestProducts($conn);
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Glad2Glow</title>
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
</head>

<body>

  <!-- ================= HEADER ================= -->
  <header class="w-full bg-[#f6dada] bg-transparent">
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

  <!-- ================= HERO ================= -->
  <div class="mt-16 h-[450px] bg-cover bg-center flex items-center justify-center"
    style="background-image:url('./assets/foto_header.jpg')">
    <div class="text-white text-center">
      <h1 class="text-4xl md:text-6xl font-bold">Glow Better with Glad2Glow </h1>
      <p class="mt-5">Wujudkan Kulit Cerah Impianmu</p>
      <div class="mt-8">
        <a href="product.php"
          class="inline-block px-8 py-3 bg-white text-gray-900 font-semibold rounded-full
        hover:bg-pink-500 hover:text-white transition">
          Temukan Sekarang →
        </a>
      </div>
    </div>
  </div>



  <!-- LIST BEST PRODUCT -->
  <div class="relative z-20 py-20 bg-white">
    <div class="text-center mb-10">
      <a href="product.php" class="inline-block">
        <h1 class="text-4xl md:text-6xl font-light text-pink-400 tracking-wide drop-shadow-sm 
                 inline-block border-b-2 border-pink-200 pb-2
                 hover:text-pink-600 hover:border-pink-400 transition cursor-pointer">
          🌸 Best Product 🌸
        </h1>
      </a>
    </div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">

        <?php if (!$bestProducts || mysqli_num_rows($bestProducts) == 0): ?>
          <p class="text-gray-500">Belum ada best product.</p>
        <?php else: ?>
          <?php while ($p = mysqli_fetch_assoc($bestProducts)) : ?>
            <?php
            $img = productImageUrl($p['image'] ?? '');
            
            // kalau image cuma nama file, biasanya:
            // $img = './assets/' . $p['image'];
            ?>
            <a href="product_detail.php?id=<?= (int)$p['id'] ?>" class="group relative z-30 block bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300">
              <div class="aspect-square w-full overflow-hidden bg-gray-200 relative">
                <img src="<?= htmlspecialchars($img) ?>" class="h-full w-full object-cover object-center group-hover:scale-110 transition duration-500" />
              </div>
              <div class="p-4 flex flex-col">
                <h3 class="text-sm text-gray-700 font-medium group-hover:text-pink-600 transition">
                  <?= htmlspecialchars($p['name']) ?>
                </h3>
                <p class="mt-2 text-lg font-bold text-gray-900">
                  Rp <?= number_format((float)$p['price'], 0, ',', '.') ?>
                </p>
                <span class="mt-4 text-pink-500 text-sm font-semibold flex items-center gap-1">
                  Lihat Detail <span>→</span>
                </span>
              </div>
            </a>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>


  <!-- komponen daily routine skincare -->
  <style>
    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
  <!-- komponen utamanya -->
  <section class="max-w-4xl mx-auto px-4 py-8">

    <h2 class="text-center text-3xl font-bold text-pink-600 mb-8">
      Glad2Glow Daily Routine
    </h2>

    <div class="grid md:grid-cols-2 gap-6">

      <!-- Morning Routine -->
      <div class="bg-pink-50 p-6 rounded-2xl shadow-md border border-pink-200 animate-fadeIn">
        <h3 class="text-xl font-semibold text-pink-700 mb-4 flex items-center gap-2">
          🌅 Morning Routine
        </h3>

        <ul class="space-y-3 text-gray-700 text-sm">
          <li>✨ <b>Gentle Cleanser</b> — membersihkan minyak setelah tidur</li>
          <li>🌸 <b>Hydrating Toner</b> — melembapkan kulit</li>
          <li>💗 <b>Glow Serum</b> — mencerahkan wajah</li>
          <li>🧴 <b>Moisturizer</b> — menjaga hidrasi</li>
          <li>☀️ <b>Sunscreen</b> — wajib untuk proteksi UV</li>
        </ul>
      </div>
      <!-- Night Routine -->
      <div class="bg-pink-50 p-6 rounded-2xl shadow-md border border-pink-200 animate-fadeIn animation-delay-200">
        <h3 class="text-xl font-semibold text-pink-700 mb-4 flex items-center gap-2">
          🌙 Night Routine
        </h3>
        <ul class="space-y-3 text-gray-700 text-sm">
          <li>🧼 <b>Cleansing Oil</b> — hapus makeup & sunscreen</li>
          <li>✨ <b>Facial Cleanser</b> — cuci muka sampai bersih</li>
          <li>🍃 <b>Exfoliating Toner</b> (2–3x/minggu)</li>
          <li>💗 <b>Repair Serum</b> — memperbaiki skin barrier</li>
          <li>🧴 <b>Night Moisturizer</b> — mengunci kelembapan</li>
        </ul>
      </div>
    </div>
  </section>

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
  <!-- ================= SCRIPT ================= -->
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const openBtn = document.getElementById("mobileMenuOpen");
      const closeBtn = document.getElementById("mobileMenuClose");
      const menuEl = document.getElementById("mobileMenu");

      openBtn.onclick = (e) => {
        e.preventDefault();
        menuEl.classList.remove("hidden", "pointer-events-none");
        setTimeout(() => {
          menuEl.classList.remove("opacity-0");
          menuEl.classList.add("opacity-100");
        }, 10);
        document.body.style.overflow = 'hidden';
      };

      closeBtn.onclick = (e) => {
        e.preventDefault();
        menuEl.classList.add("opacity-0");
        menuEl.classList.remove("opacity-100");
        setTimeout(() => {
          menuEl.classList.add("hidden", "pointer-events-none");
        }, 300);
        document.body.style.overflow = 'auto';
      };
    });
  </script>


</body>

</html>