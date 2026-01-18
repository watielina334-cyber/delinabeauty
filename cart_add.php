<?php
session_start();
include './config/database.php';

// ================= CEK LOGIN =================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id    = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $qty        = $_POST['quantity'];
    $action     = $_POST['action'];

    // cek produk sudah ada di cart atau belum
    $cek = mysqli_query($conn, "
        SELECT * FROM carts 
        WHERE user_id='$user_id' AND product_id='$product_id'
    ");

    if (mysqli_num_rows($cek) > 0) {
        // update qty
        mysqli_query($conn, "
            UPDATE carts 
            SET quantity = quantity + $qty
            WHERE user_id='$user_id' AND product_id='$product_id'
        ");
    } else {
        // insert cart
        mysqli_query($conn, "
            INSERT INTO carts (user_id, product_id, quantity)
            VALUES ('$user_id', '$product_id', '$qty')
        ");
    }

    // ================= REDIRECT =================
    if ($action == 'buy') {
        header("Location: cart.php");
    } else {
        echo "<script>
            alert('Produk berhasil ditambahkan ke keranjang');
            window.location.href='cart.php';
        </script>";
    }
    exit;
}
?>
