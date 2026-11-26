<?php
session_start();
include 'koneksi.php';

// Tangkap parameter tanggal dari URL (jika ada)
$tanggal_mulai = isset($_GET['mulai']) ? $_GET['mulai'] : date('Y-m-01'); // Default: awal bulan ini
$tanggal_akhir = isset($_GET['akhir']) ? $_GET['akhir'] : date('Y-m-t');  // Default: akhir bulan ini

// Tambahan filter (WHERE) untuk query
$filter_tanggal = "WHERE tanggal_transaksi BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'";
$filter_tanggal_where_and = "AND tanggal_transaksi BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'";

// 1. Kalkulasi Metrik Lintas Unit (Berdasarkan Filter Waktu)
$q_global = mysqli_query($koneksi, "
    SELECT 
        (SELECT SUM(nominal) FROM transaksi WHERE jenis_transaksi = 'pemasukan' $filter_tanggal_where_and) as total_pemasukan,
        (SELECT SUM(nominal) FROM transaksi WHERE jenis_transaksi = 'pengeluaran' $filter_tanggal_where_and) as total_pengeluaran
");
$global = mysqli_fetch_assoc($q_global);
$total_masuk_periode = $global['total_pemasukan'] ?? 0;
$total_keluar_periode = $global['total_pengeluaran'] ?? 0;

// ... (Biarkan query Kesehatan Kas Unit tetap seperti sebelumnya untuk melihat saldo absolut saat ini) ...

// 3. Tarik Riwayat Transaksi (Berdasarkan Filter Waktu)
$query_riwayat = "
    SELECT t.tanggal_transaksi, u.nama_unit, t.keterangan, t.jenis_transaksi, t.nominal
    FROM transaksi t
    JOIN unit_usaha u ON t.id_unit = u.id_unit
    $filter_tanggal
    ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC
";
$hasil_riwayat = mysqli_query($koneksi, $query_riwayat);

// Validasi Ganda: Harus sudah login DAN harus memiliki role 'admin'
if (!isset($_SESSION['id_unit']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- LOGIKA PENGHAPUSAN TRANSAKSI (KHUSUS ADMIN) ---
if (isset($_GET['hapus_id'])) {
    $id_hapus = mysqli_real_escape_string($koneksi, $_GET['hapus_id']);
    
    $query_hapus = "DELETE FROM transaksi WHERE id_transaksi = '$id_hapus'";
    if (mysqli_query($koneksi, $query_hapus)) {
        // Gunakan script JS untuk memberikan alert dan membersihkan URL dari parameter ?hapus_id
        echo "<script>
                alert('Satu riwayat transaksi berhasil dihapus permanen. Saldo telah dikalkulasi ulang.');
                window.location.href = 'admin_dashboard.php';
              </script>";
        exit();
    } else {
        echo "<script>alert('Gagal menghapus data: " . mysqli_error($koneksi) . "');</script>";
    }
}
// ---------------------------------------------------

// 1. Kalkulasi Metrik Global Pesantren
$q_global = mysqli_query($koneksi, "
    SELECT 
        (SELECT SUM(saldo_awal) FROM unit_usaha) as total_modal,
        (SELECT SUM(nominal) FROM transaksi WHERE jenis_transaksi = 'pemasukan') as total_pemasukan,
        (SELECT SUM(nominal) FROM transaksi WHERE jenis_transaksi = 'pengeluaran') as total_pengeluaran
");
$global = mysqli_fetch_assoc($q_global);

$total_modal = $global['total_modal'] ?? 0;
$total_masuk = $global['total_pemasukan'] ?? 0;
$total_keluar = $global['total_pengeluaran'] ?? 0;
$aset_bersih = $total_modal + $total_masuk - $total_keluar;

// 2. Kalkulasi Performa per Unit (Menggunakan LEFT JOIN & GROUP BY)
$query_unit = "
    SELECT 
        u.nama_unit, 
        u.saldo_awal,
        COALESCE(SUM(CASE WHEN t.jenis_transaksi = 'pemasukan' THEN t.nominal ELSE 0 END), 0) as masuk,
        COALESCE(SUM(CASE WHEN t.jenis_transaksi = 'pengeluaran' THEN t.nominal ELSE 0 END), 0) as keluar
    FROM unit_usaha u
    LEFT JOIN transaksi t ON u.id_unit = t.id_unit
    WHERE u.role = 'unit' 
    GROUP BY u.id_unit
";
$hasil_unit = mysqli_query($koneksi, $query_unit);

// 3. Tarik Riwayat Transaksi (Berdasarkan Filter Waktu)
$query_riwayat = "
    SELECT t.id_transaksi, t.tanggal_transaksi, u.nama_unit, t.keterangan, t.jenis_transaksi, t.nominal
    FROM transaksi t
    JOIN unit_usaha u ON t.id_unit = u.id_unit
    $filter_tanggal
    ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC
";
$hasil_riwayat = mysqli_query($koneksi, $query_riwayat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Pusat Komando Keuangan Pesantren</title>
    <style>
        body { font-family: sans-serif; background: #eef2f5; margin: 0; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 1.5em; }
        
        .summary-cards { display: flex; gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; flex: 1; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center;}
        .stat-card h4 { margin: 0 0 10px 0; color: #7f8c8d; text-transform: uppercase; letter-spacing: 1px; font-size: 0.85em;}
        .stat-card .amount { font-size: 1.8em; font-weight: bold; margin: 0; color: #2c3e50; }
        
        .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .card h3 { border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; margin-top: 0; color: #34495e; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9em; }
        th, td { border-bottom: 1px solid #ecf0f1; padding: 12px 8px; text-align: left; }
        th { background-color: #f8f9fa; color: #7f8c8d; text-transform: uppercase; font-size: 0.8em; }
        tr:hover { background-color: #f1f4f6; }
        
        .text-green { color: #27ae60; font-weight: bold; }
        .text-red { color: #e74c3c; font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Dashboard Administrator Pusat</h1>
        <p style="margin: 5px 0 0 0; color: #bdc3c7;">Pemantauan Konsolidasi Unit Usaha Pesantren</p>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; margin: 0;">
            
            <div>
                <label style="display: block; font-size: 12px; font-weight: bold; color: #7f8c8d; text-transform: uppercase; margin-bottom: 5px;">Mulai Tanggal</label>
                <input type="date" name="mulai" value="<?php echo $tanggal_mulai; ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; outline: none;">
            </div>
            
            <div>
                <label style="display: block; font-size: 12px; font-weight: bold; color: #7f8c8d; text-transform: uppercase; margin-bottom: 5px;">Sampai Tanggal</label>
                <input type="date" name="akhir" value="<?php echo $tanggal_akhir; ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; outline: none;">
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" style="background: #34495e; color: white; border: none; padding: 11px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Tampilkan Data</button>
                <a href="admin_dashboard.php" style="color: #e74c3c; text-decoration: none; padding: 11px 10px; font-size: 0.9em;">Reset</a>
                
                <button type="submit" formaction="export_excel.php" style="background: #27ae60; color: white; border: none; padding: 11px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-left: 15px;">Ekspor Excel</button>
                <button type="submit" formaction="export_pdf.php" formtarget="_blank" style="background: #e74c3c; color: white; border: none; padding: 11px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Ekspor PDF</button>
            </div>
            
            <div style="margin-left: auto;">
                 <a href="backup_db.php" style="display: inline-flex; align-items: center; gap: 8px; background: #2c3e50; color: white; text-decoration: none; padding: 11px 15px; border-radius: 4px; font-weight: bold;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Backup Database
                </a>
            </div>
            
        </form>
    </div>

    <div class="summary-cards">
        <div class="stat-card">
            <h4>Total Aset Bersih (Seluruh Unit)</h4>
            <p class="amount">Rp <?php echo number_format($aset_bersih, 0, ',', '.'); ?></p>
        </div>
        <div class="stat-card">
            <h4>Total Omset (Pemasukan)</h4>
            <p class="amount text-green">Rp <?php echo number_format($total_masuk, 0, ',', '.'); ?></p>
        </div>
        <div class="stat-card">
            <h4>Total Beban (Pengeluaran)</h4>
            <p class="amount text-red">Rp <?php echo number_format($total_keluar, 0, ',', '.'); ?></p>
        </div>
    </div>

    <div class="grid-container">
        <div class="card">
            <h3>Kesehatan Kas Unit Usaha</h3>
            <table>
                <tr>
                    <th>Nama Unit</th>
                    <th class="text-right">Saldo Saat Ini</th>
                </tr>
                <?php while($row = mysqli_fetch_assoc($hasil_unit)): 
                    $saldo_unit = $row['saldo_awal'] + $row['masuk'] - $row['keluar'];
                ?>
                <tr>
                    <td><strong><?php echo $row['nama_unit']; ?></strong></td>
                    <td class="text-right">Rp <?php echo number_format($saldo_unit, 0, ',', '.'); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="card">
            <h3>Rekam Jejak Transaksi Terkini</h3>
            <table>
                <tr>
                    <th>Unit</th>
                    <th>Keterangan</th>
                    <th class="text-right">Nominal</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
                <?php while($row = mysqli_fetch_assoc($hasil_riwayat)): ?>
                <tr>
                    <td><?php echo $row['nama_unit']; ?><br>
                        <small style="color:#95a5a6;"><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></small>
                    </td>
                    <td><?php echo $row['keterangan']; ?></td>
                    <td class="text-right <?php echo $row['jenis_transaksi'] == 'pemasukan' ? 'text-green' : 'text-red'; ?>">
                        <?php echo $row['jenis_transaksi'] == 'pemasukan' ? '+' : '-'; ?> 
                        Rp <?php echo number_format($row['nominal'], 0, ',', '.'); ?>
                    </td>
                    <td style="text-align: center;">
                        <a href="admin_dashboard.php?hapus_id=<?php echo $row['id_transaksi']; ?>" 
                        onclick="return confirm('PERINGATAN MANAJERIAL:\n\nYakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan dan saldo akan otomatis terkoreksi.');"
                        style="background: #e74c3c; color: white; text-decoration: none; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">
                        Hapus
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

</body>
</html>