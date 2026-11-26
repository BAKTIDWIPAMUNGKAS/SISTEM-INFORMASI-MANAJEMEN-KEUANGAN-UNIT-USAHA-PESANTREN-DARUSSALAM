<?php
$host = "localhost";
$user = "root";
$pass = ""; // Default Laragon biasanya kosong
$db   = "dn_unit";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
// Jika berhasil, variabel $koneksi ini yang akan kita gunakan di file lain
?>