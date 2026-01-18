<?php
session_start();
include '../config/database.php';

// proteksi admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$no = 1;
// ================= DELETE =================
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // hapus image
    $img = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM products WHERE id='$id'"));
    if ($img && file_exists("../assets/" . $img['image'])) {
        unlink("../assets/" . $img['image']);
    }

    mysqli_query($conn, "DELETE FROM products WHERE id='$id'");
    header("Location: product.php");
    exit;
}

// ================= EDIT =================
$edit = null;
if (isset($_GET['edit'])) {
    $id   = $_GET['edit'];
    $edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id='$id'"));
}

// ================= CREATE / UPDATE =================
if (isset($_POST['save'])) {

    $kategori_id = $_POST['kategori_id'];
    $name        = $_POST['name'];
    $price       = $_POST['price'];
    $stock       = $_POST['stock'];
    $description = $_POST['description'];

    // upload image
    if (!empty($_FILES['image']['name'])) {
        $ext        = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], "../assets/" . $image_name);
    }

    // UPDATE
    if (!empty($_POST['product_id'])) {
        $product_id = $_POST['product_id'];

        if (!empty($_FILES['image']['name'])) {
            mysqli_query($conn, "UPDATE products SET
                kategori_id='$kategori_id',
                name='$name',
                price='$price',
                stock='$stock',
                description='$description',
                image='$image_name'
                WHERE id='$product_id'
            ");
        } else {
            mysqli_query($conn, "UPDATE products SET
                kategori_id='$kategori_id',
                name='$name',
                price='$price',
                stock='$stock',
                description='$description'
                WHERE id='$product_id'
            ");
        }
    }
    // CREATE
    else {
        mysqli_query($conn, "INSERT INTO products
            (kategori_id, name, price, stock, description, image)
            VALUES
            ('$kategori_id','$name','$price','$stock','$description','$image_name')
        ");
    }

    header("Location: product.php");
    exit;
}

// ================= DATA =================
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name_kategori ASC");
$products   = mysqli_query($conn, "SELECT products.*, categories.name_kategori FROM products JOIN categories ON products.kategori_id = categories.kategori_id ORDER BY products.id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Product Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <!-- ================= NAVBAR ================= -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-pink-600">
                Admin Dashboard
            </h1>

            <div class="flex space-x-6 text-gray-700 font-medium">
                <a href="dashboard.php" class="hover:text-pink-600">Dashboard</a>
                <a href="product.php" class="text-pink-600">Product</a>
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


    <div class="max-w-7xl mx-auto px-6 py-8">

        <h1 class="text-2xl font-bold mb-6">Product Management</h1>

        <!-- ================= FORM ================= -->
        <div class="bg-white p-6 rounded-xl shadow mb-10">
            <h2 class="font-semibold mb-4">
                <?= $edit ? 'Edit Product' : 'Tambah Product' ?>
            </h2>

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-2 gap-4">
                <input type="hidden" name="product_id" value="<?= $edit['id'] ?? '' ?>">

                <select name="kategori_id" required class="border p-3 rounded-lg col-span-2">
                    <option value="">-- Pilih Kategori --</option>
                    <?php while ($cat = mysqli_fetch_assoc($categories)) : ?>
                        <option value="<?= $cat['kategori_id'] ?>"
                            <?= isset($edit['kategori_id']) && $edit['kategori_id'] == $cat['kategori_id'] ? 'selected' : '' ?>>
                            <?= $cat['name_kategori'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <input type="text" name="name" placeholder="Nama Product"
                    value="<?= $edit['name'] ?? '' ?>" required class="border p-3 rounded-lg">

                <input type="number" name="price" placeholder="Harga"
                    value="<?= $edit['price'] ?? '' ?>" required class="border p-3 rounded-lg">

                <input type="number" name="stock" placeholder="Stock"
                    value="<?= $edit['stock'] ?? '' ?>" required class="border p-3 rounded-lg">

                <input type="file" name="image" class="border p-3 rounded-lg">

                <textarea name="description" placeholder="Deskripsi"
                    class="border p-3 rounded-lg col-span-2"><?= $edit['description'] ?? '' ?></textarea>

                <button name="save"
                    class="col-span-2 bg-pink-500 hover:bg-pink-600 text-white py-3 rounded-lg font-semibold">
                    <?= $edit ? 'Update Product' : 'Simpan Product' ?>
                </button>
            </form>
        </div>

        <!-- ================= TABLE ================= -->
        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table class="w-full border-collapse">
                <thead class="bg-pink-500 text-white">
                    <tr>
                        <th class="p-2">No</th>
                        <th class="p-3">Image</th>
                        <th class="p-3">Nama</th>
                        <th class="p-3">Kategori</th>
                        <th class="p-3">Harga</th>
                        <th class="p-3">Stock</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($products)) : ?>
                        <tr class="border-b">
                            <td class="p-2 text-center"><?= $no++ ?></td>
                            <td class="p-3">
                                <img src="../assets/<?= $row['image'] ?>" class="w-16 h-16 object-cover rounded">
                            </td>
                            <td class="p-3"><?= $row['name'] ?></td>
                            <td class="p-3"><?= $row['name_kategori'] ?></td>
                            <td class="p-3">Rp <?= number_format($row['price'], 0, ',', '.') ?></td>
                            <td class="p-3"><?= $row['stock'] ?></td>
                            <td class="p-3 flex gap-2">
                                <a href="?edit=<?= $row['id'] ?>"
                                    class="px-3 py-1 bg-blue-500 text-white rounded">Edit</a>
                                <a href="?delete=<?= $row['id'] ?>"
                                    onclick="return confirm('Hapus product?')"
                                    class="px-3 py-1 bg-red-500 text-white rounded">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>