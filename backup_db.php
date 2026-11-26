<?php
session_start();
include 'koneksi.php';

// Proteksi Keamanan Tingkat Tinggi: Hanya Admin Pusat yang boleh melakukan backup
if (!isset($_SESSION['id_unit']) || $_SESSION['role'] !== 'admin') {
    die("Pelanggaran Keamanan: Anda tidak memiliki otoritas untuk mengakses fitur ini.");
}

// Konfigurasi Nama File Backup
$nama_database = "dn_unit"; 
$tanggal = date("Y-m-d_H-i-s");
$nama_file = "Backup_" . $nama_database . "_" . $tanggal . ".sql";

// Header untuk memaksa browser mengunduh file
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename=' . $nama_file);

$output = "-- Sistem Keuangan Pesantren Darussalam\n";
$output .= "-- Waktu Backup: " . date("d-m-Y H:i:s") . "\n\n";

// Dapatkan daftar semua tabel
$tabel_query = mysqli_query($koneksi, "SHOW TABLES");
while ($row = mysqli_fetch_row($tabel_query)) {
    $tabel = $row[0];
    
    // Dapatkan struktur tabel (CREATE TABLE)
    $create_query = mysqli_query($koneksi, "SHOW CREATE TABLE `$tabel`");
    $create_row = mysqli_fetch_row($create_query);
    $output .= "\n\n" . $create_row[1] . ";\n\n";
    
    // Dapatkan isi data dari tabel (INSERT INTO)
    $data_query = mysqli_query($koneksi, "SELECT * FROM `$tabel`");
    $jumlah_kolom = mysqli_num_fields($data_query);
    
    while ($data_row = mysqli_fetch_row($data_query)) {
        $output .= "INSERT INTO `$tabel` VALUES(";
        for ($j = 0; $j < $jumlah_kolom; $j++) {
            $data_row[$j] = addslashes($data_row[$j]);
            // Format data null atau teks
            if (isset($data_row[$j])) {
                $output .= '"' . $data_row[$j] . '"';
            } else {
                $output .= '""';
            }
            if ($j < ($jumlah_kolom - 1)) {
                $output .= ',';
            }
        }
        $output .= ");\n";
    }
}

// Cetak seluruh isi string untuk diunduh
echo $output;
exit();
?>