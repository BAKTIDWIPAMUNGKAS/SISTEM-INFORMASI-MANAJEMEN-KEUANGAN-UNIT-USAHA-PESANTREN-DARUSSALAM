<?php
include 'koneksi.php';

echo "<h2>Proses Pembuatan Akun Unit Usaha</h2>";

// Daftar Data Master Unit Usaha
// Format: ['Nama Unit', 'Username', 'Password (Plain)', Saldo Awal]
$daftar_unit = [
    ['DN Bakery', 'dn_bakery', 'bakery123', 0],
    ['DN Transport', 'dn_transport', 'transport123', 0],
    ['DN Catering', 'dn_catering', 'catering123', 0],
    ['DN Cafe Santri', 'dn_cafesantri', 'cafesantri123', 0],
    // SILAKAN TAMBAHKAN ATAU UBAH DAFTAR INI HINGGA MENCAPAI 10 UNIT
];

$berhasil = 0;
$gagal = 0;

foreach ($daftar_unit as $unit) {
    $nama_unit = mysqli_real_escape_string($koneksi, $unit[0]);
    $username  = mysqli_real_escape_string($koneksi, $unit[1]);
    $password_plain = $unit[2];
    $saldo_awal = $unit[3];
    
    // Proses Enkripsi Kata Sandi
    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

    // Cek apakah username sudah ada agar tidak duplikat
    $cek = mysqli_query($koneksi, "SELECT id_unit FROM unit_usaha WHERE username = '$username'");
    
    if (mysqli_num_rows($cek) == 0) {
        $query = "INSERT INTO unit_usaha (nama_unit, username, password, saldo_awal, role) 
                  VALUES ('$nama_unit', '$username', '$password_hash', '$saldo_awal', 'unit')";
        
        if (mysqli_query($koneksi, $query)) {
            echo "<span style='color:green;'>[SUKSES]</span> Akun <b>$nama_unit</b> berhasil dibuat.<br>";
            $berhasil++;
        } else {
            echo "<span style='color:red;'>[GAGAL]</span> Terjadi kesalahan pada <b>$nama_unit</b>: " . mysqli_error($koneksi) . "<br>";
            $gagal++;
        }
    } else {
        echo "<span style='color:orange;'>[DILEWATI]</span> Username <b>$username</b> sudah terdaftar di database.<br>";
    }
}

echo "<h3>Ringkasan Eksekusi:</h3>";
echo "Berhasil dibuat: $berhasil akun<br>";
echo "Gagal/Dilewati: $gagal akun<br>";
echo "<br><strong style='color:red;'>PERINGATAN MANAJERIAL: Segera hapus file seeder_unit.php ini setelah selesai digunakan!</strong>";
?>