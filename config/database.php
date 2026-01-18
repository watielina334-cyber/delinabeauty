<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ecommerce_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    exit;
}
?>