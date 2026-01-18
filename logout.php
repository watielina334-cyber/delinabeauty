<?php
session_start();

// hapus semua session
session_unset();
session_destroy();

// redirect ke halaman utama
echo "<script>
    alert('Logout berhasil!');
    window.location.href='index.php';
</script>";
exit;
