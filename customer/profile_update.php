<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
  die("Session user_id tidak ada, silakan login ulang");
}
$user_id = (int)$_SESSION['user_id'];

$name     = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
$email    = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
$no_hp    = mysqli_real_escape_string($conn, $_POST['no_hp'] ?? '');
$provinsi = mysqli_real_escape_string($conn, $_POST['provinsi'] ?? '');
$password = $_POST['password'] ?? '';

if ($provinsi === '') {
  $_SESSION['error'] = 'Provinsi wajib dipilih';
  header("Location: profile.php");
  exit;
}

if (!empty($password)) {
  $hashed = password_hash($password, PASSWORD_DEFAULT);
  $sql = "UPDATE users SET
            name='$name',
            email='$email',
            no_hp='$no_hp',
            provinsi='$provinsi',
            password='$hashed'
          WHERE user_id=$user_id";
} else {
  $sql = "UPDATE users SET
            name='$name',
            email='$email',
            no_hp='$no_hp',
            provinsi='$provinsi'
          WHERE user_id=$user_id";
}

if (mysqli_query($conn, $sql)) {
  $_SESSION['success'] = 'Profile updated successfully';
} else {
  $_SESSION['error'] = 'Failed to update profile: ' . mysqli_error($conn);
}

header("Location: profile.php");
exit;
?>