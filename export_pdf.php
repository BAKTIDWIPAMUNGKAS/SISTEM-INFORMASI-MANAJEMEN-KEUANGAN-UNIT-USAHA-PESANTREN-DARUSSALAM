<?php
require('fpdf/fpdf.php');
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_unit'])) { die("Akses ditolak"); }

$id_unit   = $_SESSION['id_unit'];
$nama_unit = $_SESSION['nama_unit'];
$is_admin  = ($_SESSION['role'] === 'admin');

// 1. Tangkap Parameter Filter
// Dashboard Unit mengirim tgl_mulai/tgl_akhir, Dashboard Admin mengirim mulai/akhir.
$tgl_mulai = $_GET['tgl_mulai'] ?? ($_GET['mulai'] ?? '');
$tgl_akhir = $_GET['tgl_akhir'] ?? ($_GET['akhir'] ?? '');

$teks_filter = "Periode: Seluruh Waktu";

if ($is_admin) {
    // Admin: rekap SEMUA unit usaha
    $kondisi_sql = "WHERE 1=1";
} else {
    // Unit: hanya transaksi miliknya sendiri
    $kondisi_sql = "WHERE t.id_unit = '" . (int)$id_unit . "'";
}

if (!empty($tgl_mulai) && !empty($tgl_akhir)) {
    $mulai = mysqli_real_escape_string($koneksi, $tgl_mulai);
    $akhir = mysqli_real_escape_string($koneksi, $tgl_akhir);
    $kondisi_sql .= " AND t.tanggal_transaksi BETWEEN '$mulai' AND '$akhir'";
    $teks_filter = "Periode: " . date('d/m/Y', strtotime($mulai)) . " s/d " . date('d/m/Y', strtotime($akhir));
}

if (!empty($_GET['jenis']) && $_GET['jenis'] != 'semua') {
    $jenis = mysqli_real_escape_string($koneksi, $_GET['jenis']);
    $kondisi_sql .= " AND t.jenis_transaksi = '$jenis'";
    $teks_filter .= " | Filter: " . strtoupper($jenis);
}

$judul_unit = $is_admin ? "Seluruh Unit Usaha (Konsolidasi)" : $nama_unit;

// 2. Tarik Data Kalkulasi
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

// 3. Konfigurasi FPDF
class PDF extends FPDF {
    public $judul_unit = '';

    function Header() {
        $this->SetFont('Arial','B',16);
        $this->Cell(0,8,'LAPORAN KEUANGAN OPERASIONAL',0,1,'C');
        $this->SetFont('Arial','B',12);
        $this->Cell(0,6,'PONDOK PESANTREN DARUSSALAM',0,1,'C');
        $this->SetFont('Arial','I',10);
        $this->Cell(0,5,'Unit Usaha: ' . $this->judul_unit,0,1,'C');
        $this->Ln(2);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Halaman '.$this->PageNo().'/{nb} | Dicetak oleh sistem pada: '.date('d/m/Y H:i'),0,0,'C');
    }
}

// Inisiasi Dokumen
$pdf = new PDF('P','mm','A4');
$pdf->judul_unit = $judul_unit;
$pdf->AliasNbPages();
$pdf->AddPage();

// Informasi Filter Laporan
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,$teks_filter,0,1,'L');
$pdf->Ln(5);

// Blok Ringkasan Keuangan
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(230, 240, 255);
$pdf->Cell(190,8,'RINGKASAN KAS',1,1,'C',true);

$pdf->SetFont('Arial','',11);
$pdf->Cell(95,8, $is_admin ? 'Modal Awal (Total Semua Unit)' : 'Modal Awal',1,0,'L');
$pdf->Cell(95,8,'Rp ' . number_format($saldo_awal, 0, ',', '.'),1,1,'R');

$pdf->Cell(95,8,'Total Pemasukan',1,0,'L');
$pdf->SetTextColor(0, 128, 0);
$pdf->Cell(95,8,'+ Rp ' . number_format($total_masuk, 0, ',', '.'),1,1,'R');
$pdf->SetTextColor(0, 0, 0);

$pdf->Cell(95,8,'Total Pengeluaran',1,0,'L');
$pdf->SetTextColor(255, 0, 0);
$pdf->Cell(95,8,'- Rp ' . number_format($total_keluar, 0, ',', '.'),1,1,'R');
$pdf->SetTextColor(0, 0, 0);

$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(95,8, $is_admin ? 'SALDO KAS KONSOLIDASI' : 'SALDO KAS SAAT INI',1,0,'L',true);
$pdf->Cell(95,8,'Rp ' . number_format($saldo_akhir, 0, ',', '.'),1,1,'R',true);
$pdf->Ln(8);

// Tabel Riwayat Transaksi
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(44, 62, 80);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(28,8,'TANGGAL',1,0,'C',true);
if ($is_admin) { $pdf->Cell(42,8,'UNIT USAHA',1,0,'C',true); }
$pdf->Cell($is_admin ? 60 : 90,8,'KETERANGAN',1,0,'C',true);
$pdf->Cell(30,8,'JENIS',1,0,'C',true);
$pdf->Cell($is_admin ? 30 : 35,8,'NOMINAL',1,1,'C',true);

// Data Transaksi
$pdf->SetFont('Arial','',9);
$pdf->SetTextColor(0, 0, 0);

if ($is_admin) {
    $query_transaksi = mysqli_query($koneksi, "
        SELECT t.*, u.nama_unit
        FROM transaksi t
        JOIN unit_usaha u ON t.id_unit = u.id_unit
        $kondisi_sql
        ORDER BY t.tanggal_transaksi ASC
    ");
} else {
    $query_transaksi = mysqli_query($koneksi, "
        SELECT t.*
        FROM transaksi t
        $kondisi_sql
        ORDER BY t.tanggal_transaksi ASC
    ");
}

if(mysqli_num_rows($query_transaksi) > 0) {
    while($row = mysqli_fetch_assoc($query_transaksi)){
        $pdf->Cell(28,7,date('d/m/Y', strtotime($row['tanggal_transaksi'])),1,0,'C');
        if ($is_admin) { $pdf->Cell(42,7,$row['nama_unit'],1,0,'L'); }
        $pdf->Cell($is_admin ? 60 : 90,7,$row['keterangan'],1,0,'L');

        $jenis = strtoupper($row['jenis_transaksi']);
        $pdf->Cell(30,7,$jenis,1,0,'C');

        $nominal = 'Rp ' . number_format($row['nominal'], 0, ',', '.');
        $pdf->Cell($is_admin ? 30 : 35,7,$nominal,1,1,'R');
    }
} else {
    $pdf->Cell(190,8,'Tidak ada data transaksi pada periode ini.',1,1,'C');
}

// Blok Tanda Tangan
$pdf->Ln(20);
$pdf->SetFont('Arial','',10);
$pdf->Cell(95,6,'Mengetahui,',0,0,'C');
$pdf->Cell(95,6,'Tasikmalaya, ' . date('d F Y'),0,1,'C');
$pdf->Cell(95,6,'Pimpinan Pondok Pesantren',0,0,'C');
$pdf->Cell(95,6, $is_admin ? 'Administrator Pusat' : 'Penanggung Jawab Unit',0,1,'C');

$pdf->Ln(25);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(95,6,'( ......................................... )',0,0,'C');
$pdf->Cell(95,6,'( ......................................... )',0,1,'C');

$nama_file_pdf = $is_admin ? 'Laporan_Keuangan_Seluruh_Unit.pdf' : 'Laporan_Keuangan_'.$nama_unit.'.pdf';
$pdf->Output('I', $nama_file_pdf);
?>