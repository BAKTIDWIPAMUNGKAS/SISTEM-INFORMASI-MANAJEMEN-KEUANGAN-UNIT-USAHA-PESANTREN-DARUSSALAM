<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_unit'])) { die("Akses ditolak"); }

$id_unit   = $_SESSION['id_unit'];
$nama_unit = $_SESSION['nama_unit'];
$is_admin  = ($_SESSION['role'] === 'admin');

// 1. Tangkap Parameter Filter dari URL (GET)
// Dashboard Unit mengirim tgl_mulai/tgl_akhir, Dashboard Admin mengirim mulai/akhir.
// Keduanya diterima supaya ekspor tetap mengikuti filter yang sedang ditampilkan.
$tgl_mulai = $_GET['tgl_mulai'] ?? ($_GET['mulai'] ?? '');
$tgl_akhir = $_GET['tgl_akhir'] ?? ($_GET['akhir'] ?? '');

$teks_filter = "Semua Waktu";

if ($is_admin) {
    // Admin: rekap SEMUA unit usaha (tanpa batasan id_unit)
    $kondisi_sql = "WHERE 1=1";
} else {
    // Unit: hanya transaksi miliknya sendiri
    $kondisi_sql = "WHERE t.id_unit = '" . (int)$id_unit . "'";
}

// Filter Tanggal
if (!empty($tgl_mulai) && !empty($tgl_akhir)) {
    $mulai = mysqli_real_escape_string($koneksi, $tgl_mulai);
    $akhir = mysqli_real_escape_string($koneksi, $tgl_akhir);
    $kondisi_sql .= " AND t.tanggal_transaksi BETWEEN '$mulai' AND '$akhir'";
    $teks_filter = "Periode: $mulai s/d $akhir";
}

// Filter Jenis Transaksi
if (!empty($_GET['jenis']) && $_GET['jenis'] != 'semua') {
    $jenis = mysqli_real_escape_string($koneksi, $_GET['jenis']);
    $kondisi_sql .= " AND t.jenis_transaksi = '$jenis'";
    $teks_filter .= " | Jenis: " . strtoupper($jenis);
}

$judul_laporan = $is_admin ? "SELURUH UNIT USAHA (KONSOLIDASI)" : strtoupper($nama_unit);
$nama_file     = $is_admin ? "Semua_Unit" : $nama_unit;

// Pengaturan Header untuk Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_{$nama_file}_" . date('Ymd') . ".xls");

// 2. Ambil Data Ringkasan
if ($is_admin) {
    $q_awal = mysqli_query($koneksi, "SELECT SUM(saldo_awal) AS total FROM unit_usaha WHERE role = 'unit'");
    $saldo_awal = mysqli_fetch_assoc($q_awal)['total'] ?? 0;
} else {
    $q_awal = mysqli_query($koneksi, "SELECT saldo_awal FROM unit_usaha WHERE id_unit = '" . (int)$id_unit . "'");
    $saldo_awal = mysqli_fetch_assoc($q_awal)['saldo_awal'] ?? 0;
}

$q_masuk = mysqli_query($koneksi, "SELECT SUM(t.nominal) AS total FROM transaksi t $kondisi_sql AND t.jenis_transaksi = 'pemasukan'");
$total_masuk = mysqli_fetch_assoc($q_masuk)['total'] ?? 0;

$q_keluar = mysqli_query($koneksi, "SELECT SUM(t.nominal) AS total FROM transaksi t $kondisi_sql AND t.jenis_transaksi = 'pengeluaran'");
$total_keluar = mysqli_fetch_assoc($q_keluar)['total'] ?? 0;

$saldo_akhir = $saldo_awal + $total_masuk - $total_keluar;
?>

<h3>LAPORAN KEUANGAN: <?php echo $judul_laporan; ?></h3>
<p>Tanggal Ekspor: <?php echo date('d/m/Y H:i'); ?></p>
<p><?php echo $teks_filter; ?></p>

<table border="1">
    <tr>
        <th colspan="2" bgcolor="#eeeeee">RINGKASAN KEUANGAN</th>
    </tr>
    <tr><td>Modal Awal<?php echo $is_admin ? ' (Total Semua Unit)' : ''; ?></td><td>Rp <?php echo number_format($saldo_awal, 0, '', ''); ?></td></tr>
    <tr><td>Total Pemasukan</td><td>Rp <?php echo number_format($total_masuk, 0, '', ''); ?></td></tr>
    <tr><td>Total Pengeluaran</td><td>Rp <?php echo number_format($total_keluar, 0, '', ''); ?></td></tr>
    <tr><td><b>SALDO AKHIR</b></td><td><b>Rp <?php echo number_format($saldo_akhir, 0, '', ''); ?></b></td></tr>
</table>

<br>
<table border="1">
    <thead>
        <tr bgcolor="#cccccc">
            <th>Tanggal</th>
            <?php if ($is_admin): ?><th>Unit Usaha</th><?php endif; ?>
            <th>Keterangan</th>
            <th>Jenis</th>
            <th>Nominal</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($is_admin) {
            $query = mysqli_query($koneksi, "
                SELECT t.*, u.nama_unit
                FROM transaksi t
                JOIN unit_usaha u ON t.id_unit = u.id_unit
                $kondisi_sql
                ORDER BY t.tanggal_transaksi ASC
            ");
        } else {
            $query = mysqli_query($koneksi, "
                SELECT t.*
                FROM transaksi t
                $kondisi_sql
                ORDER BY t.tanggal_transaksi ASC
            ");
        }
        while($row = mysqli_fetch_assoc($query)):
        ?>
        <tr>
            <td><?php echo $row['tanggal_transaksi']; ?></td>
            <?php if ($is_admin): ?><td><?php echo htmlspecialchars($row['nama_unit']); ?></td><?php endif; ?>
            <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
            <td><?php echo strtoupper($row['jenis_transaksi']); ?></td>
            <td><?php echo $row['nominal']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>