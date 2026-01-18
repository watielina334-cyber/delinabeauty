<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Pembayaran Berhasil</title>

</head>
<style>
    /* overlay */
    .popup-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .popup-success {
        background: #fff;
        width: 90%;
        max-width: 420px;
        border-radius: 15px;
        padding: 32px 28px;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        animation: popupScale 0.35s ease;
    }

    .icon-check {
        width: 65px;
        height: 65px;
        background: #22c55e;
        color: #fff;
        font-size: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
    }

    .popup-success h2 {
        margin: 0 0 8px;
        font-size: 22px;
        color: #111;
    }

    .popup-success p {
        font-size: 15px;
        color: #555;
        margin-bottom: 25px;
    }

    .popup-actions {
        display: flex;
        gap: 13px;
    }

    .btn {
        flex: 1;
        padding: 10px 0;
        border-radius: 10px;
        font-size: 15px;
        text-decoration: none;
        text-align: center;
        background: #e5e7eb;
        color: #111;
        transition: 0.2s;
    }

    .btn.primary {
        background: pink;
        color: #fff;
    }

    .btn:hover {
        opacity: 0.9;
    }

    /* animation */
    @keyframes popupScale {
        from {
            transform: scale(0.9);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }
</style>

<body>
    <div class="popup-overlay">
        <div class="popup-success">
            <div class="icon-check">✔️</div>
            <h2>Pembayaran Berhasil</h2>
            <p>Terimakasih, pesanan kamu sedang di proses.</p>

            <div class="popup-actions">
                <a href="customer/order.php" class="btn primary">Lihat Pesanan</a>
                <a href="index.php" class="btn">Kembali ke Home</a>
            </div>
        </div>
    </div>
</body>
<script>
    setTimeout(() => {
        document.querySelector('.popup-overlay').style.display = 'none';
    }, 3000);
</script>

</html>