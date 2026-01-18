<?php
session_start();
include './config/database.php';

// =====================
// CEK LOGIN
// =====================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$items = [];
$total = 0;
$cart_ids = [];

// =====================
// MODE BUY NOW
// =====================
if (isset($_GET['buy_now']) && isset($_SESSION['buy_now'])) {

    $product_id = (int)$_SESSION['buy_now']['product_id'];
    $qty = (int)($_SESSION['buy_now']['quantity'] ?? 1);
    if ($qty < 1) $qty = 1;

    $q = mysqli_query($conn, "
        SELECT id, name, price, image, stock
        FROM products 
        WHERE id = $product_id
    ");

    if (!$row = mysqli_fetch_assoc($q)) die("Produk tidak ditemukan");

    // (opsional) clamp qty biar ga melebihi stok
    if ($qty > (int)$row['stock']) $qty = (int)$row['stock'];

    $subtotal = $qty * (int)$row['price'];

    $items[] = [
        'product_id' => $row['id'],
        'name'       => $row['name'],
        'price'      => $row['price'],
        'quantity'   => $qty,
        'image'      => $row['image'],
        'subtotal'   => $subtotal
    ];

    $total = $subtotal;
}

// =====================
// MODE CART
// =====================
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cart_ids'])) {

    $selected_cart_ids = array_map('intval', $_POST['cart_ids']);
    $ids = implode(',', $selected_cart_ids);

    $cartQuery = mysqli_query($conn, "
        SELECT carts.cart_id,
               carts.quantity,
               products.id AS product_id,
               products.name,
               products.price,
               products.image,
               (carts.quantity * products.price) AS subtotal
        FROM carts
        JOIN products ON carts.product_id = products.id
        WHERE carts.cart_id IN ($ids)
          AND carts.user_id = $user_id
    ");

    if (mysqli_num_rows($cartQuery) == 0) {
        die("Cart kosong");
    }

    while ($row = mysqli_fetch_assoc($cartQuery)) {
        $items[] = $row;
        $cart_ids[] = $row['cart_id'];
        $total += $row['subtotal'];
    }
}
// =====================
// JIKA TIDAK ADA DATA
// =====================
else {
    header("Location: cart.php");
    exit;
}

// =====================
// VALIDASI TOTAL
// =====================
if ($total <= 0) {
    die("Total tidak valid");
}

// =====================
// AMBIL DATA USER
// =====================
$userQ = mysqli_query($conn, "
    SELECT provinsi, no_hp 
    FROM users 
    WHERE user_id = $user_id
");

$users = mysqli_fetch_assoc($userQ);

if (!$users || empty($users['provinsi'])) {
    die("Alamat belum diisi");
}

$provinsi = $users['provinsi'];
$no_hp  = $users['no_hp'];

// SIMPAN KE SESSION (INI KUNCI)
$_SESSION['checkout_items'] = $items;
$_SESSION['checkout_total'] = (int)$total;

$_SESSION['checkout_ongkir'] = 0;      // default, nanti diupdate saat pilih metode
$_SESSION['provinsi'] = $provinsi;         // save_order.php butuh ini
$_SESSION['phone']  = $no_hp;          // ambil dari DB biar konsisten

?>

<!DOCTYPE html>
<html>

<head>
    <title>Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="text/javascript"
        src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="Mid-client-1poGZ7NR2rocXHSW">
        const cartIds = <?= json_encode($cart_ids ?? []) ?>;
    </script>

</head>
<style>
    .popup-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .4);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .popup-card {
        background: #fff;
        padding: 30px 40px;
        border-radius: 14px;
        width: 360px;
        text-align: center;
        box-shadow: 0 20px 50px rgba(0, 0, 0, .2);
        animation: scaleIn .3s ease;
    }

    @keyframes scaleIn {
        from {
            transform: scale(.8);
            opacity: 0
        }

        to {
            transform: scale(1);
            opacity: 1
        }
    }

    .popup-card .icon {
        width: 70px;
        height: 70px;
        background: #2ecc71;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin: 0 auto 15px;
    }

    .popup-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .popup-actions .btn {
        flex: 1;
        padding: 10px;
        border-radius: 8px;
        text-decoration: none;
        background: #eee;
        color: #333;
        font-weight: 500;
    }

    .popup-actions .btn.primary {
        background: #ec4899;
        color: #fff;
    }
</style>

<body class="bg-gray-100 ">

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


    <div class="min-h-screen py-20 px-4">

        <div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow mt-16">

            <h2 class="text-2xl font-bold mb-6">📋 Checkout</h2>

            <table class="w-full mb-6">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 text-left">Produk</th>
                        <th class="p-2">Harga</th>
                        <th class="p-2">Qty</th>
                        <th class="p-2">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= $item['name'] ?></td>
                                <td>Rp <?= number_format($item['price']) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td>Rp <?= number_format($item['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">Keranjang kosong</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>


            <!-- komponen provinsi -->
            <h3 class="font-bold mb-2">Alamat Pengiriman</h3>

            <div class="border rounded-lg p-3 bg-gray-50 mb-4">
                <?= nl2br(htmlspecialchars($provinsi)) ?>
            </div>

            <!-- komponen no_hp -->
            <h3 class="font-bold mb-2">No.HP</h3>

            <div class="border rounded-lg p-3 bg-gray-50 mb-4">
                <?= nl2br(htmlspecialchars($no_hp)) ?>
            </div>

            <!-- METODE PEMBAYARAN -->
            <form id="checkout-form" method="POST" action="process_order.php">
                <input type="hidden" name="shipping_cost" id="shipping_cost" value="0">
                <?php foreach ($items as $item): ?>
                    <input type="hidden" name="items[<?= $item['product_id'] ?>][id]" value="<?= $item['product_id'] ?>">
                    <input type="hidden" name="items[<?= $item['product_id'] ?>][name]" value="<?= $item['name'] ?>">
                    <input type="hidden" name="items[<?= $item['product_id'] ?>][price]" value="<?= $item['price'] ?>">
                    <input type="hidden" name="items[<?= $item['product_id'] ?>][quantity]" value="<?= $item['quantity'] ?>">
                <?php endforeach; ?>


                <h3 class="font-bold mb-2">Metode Pembayaran</h3>

                <label class="block mb-2">
                    <input type="radio" name="metode_pembayaran" value="transfer" required>
                    Transfer Bank / E-Wallet
                </label>

                <label class="block mb-4">
                    <input type="radio" name="metode_pembayaran" value="cod">
                    COD (Bayar di Tempat)
                </label>

                <!-- ONGKIR -->
                <p id="ongkir-wrapper" style="display:none;" class="text-sm mt-2 text-gray-600">
                    Ongkir: Rp <span id="ongkir-text">0</span>
                </p>

                <!-- TOTAL (ANGKA MURNI!) -->

                <p class="text-right text-xl font-semibold">
                    Total: Rp <span id="total-text"><?= number_format($total) ?></span>
                </p>

                <!-- INPUT ALAMAT (WAJIB ADA) -->
                <input type="hidden" id="provinsi" value="<?= htmlspecialchars($provinsi ?? '') ?>">
                <input type="text" name="no_hp" id="no_hp">

                <?php foreach ($cart_ids as $cid): ?>
                    <input type="hidden" name="cart_ids[]" value="<?= $cid ?>">
                <?php endforeach; ?>

                <button type="button" id="pay-button"
                    class="w-full bg-pink-500 hover:bg-pink-600 text-white py-3 rounded-lg font-semibold">
                    💳 Pay / Lanjut Pembayaran
                </button>


            </form>
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

    <script>
        const payButton = document.getElementById('pay-button');
        const metodeRadios = document.querySelectorAll('input[name="metode_pembayaran"]');
        const ongkirWrapper = document.getElementById('ongkir-wrapper');
        const ongkirText = document.getElementById('ongkir-text');
        const shippingCostInput = document.getElementById('shipping_cost');
        const totalText = document.getElementById('total-text');

        const baseTotal = <?= (int)$total ?>;

        // Ambil provinsi dari hidden input
        const provinsiEl = document.getElementById('provinsi');

        function formatRupiah(n) {
            return (n || 0).toLocaleString('id-ID');
        }

        function setOngkir(shipping_cost, extraText = '') {
            ongkirWrapper.style.display = 'block';
            ongkirText.innerText = extraText ?
                `${formatRupiah(shipping_cost)} ${extraText}` :
                formatRupiah(shipping_cost);

            shippingCostInput.value = shipping_cost;
            totalText.innerText = formatRupiah(baseTotal + shipping_cost);
        }

        // Mapping provinsi -> pulau (ringkas, tapi lengkap)
        const ISLAND_BY_PROVINCE = {
            // SUMATERA
            "Aceh": "sumatera",
            "Sumatera Utara": "sumatera",
            "Sumatera Barat": "sumatera",
            "Riau": "sumatera",
            "Kepulauan Riau": "sumatera",
            "Jambi": "sumatera",
            "Sumatera Selatan": "sumatera",
            "Bangka Belitung": "sumatera",
            "Bengkulu": "sumatera",
            "Lampung": "sumatera",

            // JAWA
            "DKI Jakarta": "jawa",
            "Jawa Barat": "jawa",
            "Banten": "jawa",
            "Jawa Tengah": "jawa",
            "DI Yogyakarta": "jawa",
            "Jawa Timur": "jawa",

            // KALIMANTAN
            "Kalimantan Barat": "kalimantan",
            "Kalimantan Tengah": "kalimantan",
            "Kalimantan Selatan": "kalimantan",
            "Kalimantan Timur": "kalimantan",
            "Kalimantan Utara": "kalimantan",

            // SULAWESI
            "Sulawesi Utara": "sulawesi",
            "Gorontalo": "sulawesi",
            "Sulawesi Tengah": "sulawesi",
            "Sulawesi Barat": "sulawesi",
            "Sulawesi Selatan": "sulawesi",
            "Sulawesi Tenggara": "sulawesi",

            // BALI & NUSA TENGGARA
            "Bali": "bali_nt",
            "Nusa Tenggara Barat": "bali_nt",
            "Nusa Tenggara Timur": "bali_nt",

            // MALUKU
            "Maluku": "maluku",
            "Maluku Utara": "maluku",

            // PAPUA
            "Papua": "papua",
            "Papua Barat": "papua",
            "Papua Selatan": "papua",
            "Papua Tengah": "papua",
            "Papua Pegunungan": "papua",
            "Papua Barat Daya": "papua"
        };

        // Tarif per pulau (konsep kamu: tiap pindah pulau +5000)
        // Base: Sumatera 10k, Jawa 15k, Kalimantan 20k, Sulawesi 25k, Bali/NT 30k, Maluku 35k, Papua 40k
        const COD_SHIPPING_BY_ISLAND = {
            sumatera: 15000,
            jawa: 30000,
            kalimantan: 40000,
            sulawesi: 50000,
            bali_nt: 60000,
            maluku: 70000,
            papua: 80000
        };

        function getIslandFromProvinsi(provinsi) {
            return ISLAND_BY_PROVINCE[provinsi] || null;
        }

        function hitungOngkirCODPerPulau() {
            const provinsi = (provinsiEl?.value || '').trim();
            const island = getIslandFromProvinsi(provinsi);

            // fallback kalau provinsi belum dipilih / kosong
            if (!provinsi || !island) return {
                cost: 0,
                label: '(COD - provinsi belum valid)'
            };

            const cost = COD_SHIPPING_BY_ISLAND[island] ?? 0;
            const label = `(COD - ${provinsi} / ${island.toUpperCase()})`;

            return {
                cost,
                label
            };
        }

        function hitungOngkirTransfer() {
            // sesuai request kamu: transfer ongkir 0
            return {
                cost: 0,
                label: '(Transfer - Gratis Ongkir)'
            };
        }

        // update ongkir saat metode dipilih
        metodeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'cod') {
                    const {
                        cost,
                        label
                    } = hitungOngkirCODPerPulau();
                    setOngkir(cost, label);
                } else {
                    const {
                        cost,
                        label
                    } = hitungOngkirTransfer();
                    setOngkir(cost, label);
                }
            });
        });

        payButton.addEventListener('click', function() {
            const metode = document.querySelector('input[name="metode_pembayaran"]:checked');
            if (!metode) {
                alert('Pilih metode pembayaran terlebih dahulu');
                return;
            }

            // COD submit langsung
            if (metode.value === 'cod') {
                const {
                    cost
                } = hitungOngkirCODPerPulau();
                shippingCostInput.value = cost;
                document.getElementById('checkout-form').submit();
                return;
            }

            // TRANSFER via midtrans
            const {
                cost
            } = hitungOngkirTransfer();
            shippingCostInput.value = cost;
            setOngkir(cost, '(Transfer - Gratis Ongkir)');

            const grandTotal = baseTotal + cost;

            fetch('payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        shipping_cost: cost,
                        total: grandTotal
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.token) {
                        alert('Snap token tidak ditemukan');
                        return;
                    }

                    window.snap.pay(data.token, {
                        onSuccess: function(result) {
                            fetch('save_order.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        midtrans: result,
                                        shipping_cost: cost,
                                        total: grandTotal
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.status === 'success') window.location.href = 'payment_success.php';
                                    else alert(data.message || 'Gagal menyimpan order');
                                });
                        },
                        onPending: function() {
                            alert('Menunggu pembayaran');
                        },
                        onError: function() {
                            alert('Pembayaran gagal');
                        }
                    });
                });
        });
    </script>

</body>

</html>