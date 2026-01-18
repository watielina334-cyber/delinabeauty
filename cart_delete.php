<?php
session_start();
include 'config/database.php';

// cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// cek parameter
if (isset($_GET['id'])) {

    $cart_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // hapus cart milik user ini saja
    mysqli_query($conn, "
        DELETE FROM carts 
        WHERE cart_id='$cart_id' AND user_id='$user_id'
    ");

    echo "<script>
        alert('Produk berhasil dihapus dari keranjang');
        window.location.href='cart.php';
    </script>";
    exit;
}

// fallback
header("Location: cart.php");
exit;
