<?php
session_start();

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'inventaris_db';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function getStok($id_barang) {
    global $conn;
    $q = mysqli_query($conn, "SELECT jumlah FROM barang WHERE id_barang='$id_barang'");
    $d = mysqli_fetch_assoc($q);
    return $d['jumlah'];
}
?>