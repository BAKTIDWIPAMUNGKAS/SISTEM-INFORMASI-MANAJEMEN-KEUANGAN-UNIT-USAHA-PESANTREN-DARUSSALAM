<?php
session_start();
include 'koneksi.php';

// ── AUTH GUARD ───────────────────────────────────────────────────────────
if (!isset($_SESSION['id_unit'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$id_unit_aktif   = (int) $_SESSION['id_unit'];
$nama_unit_aktif = $_SESSION['nama_unit'];

// ── SIMPAN TRANSAKSI ─────────────────────────────────────────────────────
$success = '';
$error   = '';

if (isset($_POST['simpan_transaksi'])) {
    $jenis      = in_array($_POST['jenis_transaksi'], ['pemasukan','pengeluaran'])
                    ? $_POST['jenis_transaksi'] : '';
    $nominal    = filter_var($_POST['nominal'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $keterangan = mysqli_real_escape_string($koneksi, trim($_POST['keterangan']));
    $tanggal    = date('Y-m-d');

    if (!$jenis || !$nominal || $keterangan === '') {
        $error = "Semua kolom wajib diisi dengan nilai yang valid.";
    } else {
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO transaksi (id_unit, jenis_transaksi, nominal, keterangan, tanggal_transaksi)
             VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "isiss", $id_unit_aktif, $jenis, $nominal, $keterangan, $tanggal);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Transaksi berhasil dicatat.";
        } else {
            $error = "Gagal menyimpan: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt);
    }
}

// ── FILTER DARI GET (untuk tampilan tabel & ekspor) ───────────────────────
$filter_aktif  = false;
$tgl_mulai_val = '';
$tgl_akhir_val = '';
$jenis_val     = 'semua';
$kondisi_extra = '';

if (!empty($_GET['tgl_mulai']) && !empty($_GET['tgl_akhir'])) {
    $tgl_mulai_val = date('Y-m-d', strtotime($_GET['tgl_mulai'])) ?: '';
    $tgl_akhir_val = date('Y-m-d', strtotime($_GET['tgl_akhir'])) ?: '';
    if ($tgl_mulai_val && $tgl_akhir_val) {
        $kondisi_extra .= " AND tanggal_transaksi BETWEEN '$tgl_mulai_val' AND '$tgl_akhir_val'";
        $filter_aktif   = true;
    }
}
if (!empty($_GET['jenis']) && in_array($_GET['jenis'], ['pemasukan','pengeluaran'])) {
    $jenis_val      = $_GET['jenis'];
    $kondisi_extra .= " AND jenis_transaksi = '$jenis_val'";
    $filter_aktif   = true;
}

// ── KALKULASI RINGKASAN (selalu all-time untuk stat cards) ────────────────
$q_awal          = mysqli_query($koneksi, "SELECT saldo_awal FROM unit_usaha WHERE id_unit = $id_unit_aktif");
$saldo_awal      = (float)(mysqli_fetch_assoc($q_awal)['saldo_awal'] ?? 0);

$q_masuk         = mysqli_query($koneksi, "SELECT SUM(nominal) AS t FROM transaksi WHERE id_unit = $id_unit_aktif AND jenis_transaksi = 'pemasukan'");
$total_pemasukan = (float)(mysqli_fetch_assoc($q_masuk)['t'] ?? 0);

$q_keluar        = mysqli_query($koneksi, "SELECT SUM(nominal) AS t FROM transaksi WHERE id_unit = $id_unit_aktif AND jenis_transaksi = 'pengeluaran'");
$total_pengeluaran = (float)(mysqli_fetch_assoc($q_keluar)['t'] ?? 0);

$saldo_akhir     = $saldo_awal + $total_pemasukan - $total_pengeluaran;
$saldo_positif   = $saldo_akhir >= 0;

// ── RIWAYAT TRANSAKSI (terapkan filter GET) ───────────────────────────────
$query_riwayat  = "SELECT * FROM transaksi
                   WHERE id_unit = $id_unit_aktif $kondisi_extra
                   ORDER BY tanggal_transaksi DESC, id_transaksi DESC";
$hasil_transaksi = mysqli_query($koneksi, $query_riwayat);
$jumlah_baris    = mysqli_num_rows($hasil_transaksi);

// ── HITUNG TRANSAKSI HARI INI ─────────────────────────────────────────────
$q_hari          = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM transaksi WHERE id_unit = $id_unit_aktif AND tanggal_transaksi = CURDATE()");
$trx_hari_ini    = (int)(mysqli_fetch_assoc($q_hari)['c'] ?? 0);

$hari_id   = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
              'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$hari_ini  = $hari_id[date('l')] ?? date('l');
$tgl_display = $hari_ini . ', ' . date('d') . ' ' .
    ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
     'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
     'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'][date('F')]
    . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?php echo htmlspecialchars($nama_unit_aktif); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
    /* ══ BRAND TOKENS ════════════════════════════════════════════════ */
    :root {
        --green-900: #092c10;
        --green-800: #0f4a18;
        --green-700: #1a6b23;
        --green-600: #22882e;
        --green-500: #2ea83b;
        --green-200: #a7d9ad;
        --green-100: #d4edda;
        --green-50:  #f0f9f1;

        --red-700:   #991b1b;
        --red-600:   #c0392b;
        --red-100:   #fbd5d2;
        --red-50:    #fdf0ee;

        --amber-600: #d97706;
        --amber-100: #fde68a;
        --amber-50:  #fffbeb;

        --n900: #0d1117;
        --n800: #1f2937;
        --n700: #374151;
        --n600: #4b5563;
        --n500: #6b7280;
        --n400: #9ca3af;
        --n300: #d1d5db;
        --n200: #e5e7eb;
        --n100: #f3f4f6;
        --n50:  #f9fafb;
        --white:#ffffff;

        --sidebar-w: 280px;
        --font-body: 'DM Sans', sans-serif;
        --font-serif:'Amiri', serif;
        --font-mono: 'DM Mono', monospace;
        --radius-sm: 6px;
        --radius-md: 10px;
        --radius-lg: 14px;
        --radius-xl: 20px;
        --ease:      cubic-bezier(0.22, 1, 0.36, 1);
    }

    /* ══ RESET ═══════════════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { height: 100%; scroll-behavior: smooth; }

    body {
        font-family: var(--font-body);
        font-size: 14px;
        color: var(--n800);
        background: #eef2ed;
        min-height: 100%;
        -webkit-font-smoothing: antialiased;
    }

    /* ══ APP SHELL ═══════════════════════════════════════════════════ */
    .app-shell {
        display: flex;
        min-height: 100vh;
    }

    /* ══ SIDEBAR ═════════════════════════════════════════════════════ */
    .sidebar {
        width: var(--sidebar-w);
        flex-shrink: 0;
        background: var(--green-800);
        position: fixed;
        top: 0; left: 0;
        height: 100vh;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        z-index: 100;
        animation: slideInLeft 0.6s var(--ease) both;
    }

    /* Subtle geometric pattern */
    .sidebar::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            repeating-linear-gradient(45deg,  transparent, transparent 24px, rgba(255,255,255,0.025) 24px, rgba(255,255,255,0.025) 25px),
            repeating-linear-gradient(-45deg, transparent, transparent 24px, rgba(255,255,255,0.025) 24px, rgba(255,255,255,0.025) 25px);
        pointer-events: none;
    }

    .sidebar-glow {
        position: absolute;
        bottom: -80px; right: -80px;
        width: 260px; height: 260px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(46,168,59,0.2) 0%, transparent 70%);
        pointer-events: none;
    }

    /* ── Sidebar header ── */
    .sidebar-header {
        position: relative;
        padding: 28px 24px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }

    .sidebar-brand-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }

    .brand-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #4ade80;
        box-shadow: 0 0 8px #4ade80;
        flex-shrink: 0;
    }

    .brand-label {
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.45);
    }

    .sidebar-unit-name {
        font-family: var(--font-serif);
        font-size: 1.45rem;
        color: var(--white);
        line-height: 1.25;
        word-break: break-word;
    }

    .sidebar-date {
        font-size: 11px;
        color: rgba(255,255,255,0.4);
        margin-top: 6px;
    }

    /* ── Sidebar body (form) ── */
    .sidebar-body {
        position: relative;
        flex: 1;
        padding: 20px 20px 0;
    }

    .sidebar-section-title {
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.35);
        margin-bottom: 14px;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }

    /* ── Form inside sidebar ── */
    .mutasi-form { display: flex; flex-direction: column; gap: 13px; }

    .form-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: rgba(255,255,255,0.55);
        letter-spacing: 0.04em;
        margin-bottom: 5px;
    }

    .form-input,
    .form-select {
        width: 100%;
        height: 40px;
        padding: 0 12px;
        border-radius: var(--radius-sm);
        border: 1.5px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.07);
        color: var(--white);
        font-family: var(--font-body);
        font-size: 13px;
        outline: none;
        transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        appearance: none;
        -webkit-appearance: none;
    }

    .form-input::placeholder { color: rgba(255,255,255,0.25); }

    .form-input:focus,
    .form-select:focus {
        border-color: var(--green-500);
        background: rgba(255,255,255,0.12);
        box-shadow: 0 0 0 3px rgba(46,168,59,0.25);
    }

    .form-select option { background: var(--green-800); color: var(--white); }

    .select-wrapper { position: relative; }

    .select-wrapper::after {
        content: '';
        position: absolute;
        right: 12px; top: 50%;
        transform: translateY(-50%);
        width: 0; height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid rgba(255,255,255,0.4);
        pointer-events: none;
    }

    .nominal-wrapper { position: relative; }

    .nominal-prefix {
        position: absolute;
        left: 12px; top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        color: rgba(255,255,255,0.4);
        font-weight: 500;
        pointer-events: none;
    }

    .nominal-wrapper .form-input { padding-left: 34px; font-family: var(--font-mono); }

    .btn-submit-sidebar {
        width: 100%;
        height: 42px;
        margin-top: 4px;
        background: var(--green-600);
        color: var(--white);
        border: none;
        border-radius: var(--radius-sm);
        font-family: var(--font-body);
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        letter-spacing: 0.02em;
        transition: background 0.18s, transform 0.1s, box-shadow 0.18s;
        position: relative;
        overflow: hidden;
    }

    .btn-submit-sidebar::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to right, transparent, rgba(255,255,255,0.08), transparent);
        transform: translateX(-100%);
        transition: transform 0.5s ease;
    }

    .btn-submit-sidebar:hover {
        background: var(--green-500);
        box-shadow: 0 4px 16px rgba(46,168,59,0.4);
    }

    .btn-submit-sidebar:hover::after { transform: translateX(100%); }
    .btn-submit-sidebar:active { transform: scale(0.98); }

    /* ── Sidebar footer ── */
    .sidebar-footer {
        position: relative;
        padding: 16px 20px 24px;
        margin-top: auto;
    }

    .btn-logout {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 10px 14px;
        border-radius: var(--radius-sm);
        background: rgba(192,57,43,0.15);
        border: 1px solid rgba(192,57,43,0.25);
        color: #fca5a5;
        font-family: var(--font-body);
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.18s, border-color 0.18s;
        letter-spacing: 0.02em;
    }

    .btn-logout:hover {
        background: rgba(192,57,43,0.28);
        border-color: rgba(192,57,43,0.45);
        color: #fecaca;
    }

    .btn-logout svg { flex-shrink: 0; }

    /* ══ MAIN CONTENT ════════════════════════════════════════════════ */
    .main-content {
        margin-left: var(--sidebar-w);
        flex: 1;
        padding: 28px 28px 40px;
        min-width: 0;
    }

    /* ── Topbar ── */
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        animation: fadeUp 0.5s var(--ease) 0.1s both;
    }

    .topbar-left h1 {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--n900);
        letter-spacing: -0.01em;
    }

    .topbar-left p {
        font-size: 12.5px;
        color: var(--n500);
        margin-top: 2px;
    }

    .topbar-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: var(--green-50);
        border: 1px solid var(--green-100);
        border-radius: 999px;
        padding: 5px 12px;
        font-size: 11.5px;
        font-weight: 600;
        color: var(--green-700);
    }

    .topbar-badge-dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--green-500);
    }

    /* ── Alert banners ── */
    .alert {
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: var(--radius-md);
        padding: 11px 14px;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 20px;
        animation: slideDown 0.4s var(--ease) both;
    }

    .alert-success {
        background: var(--green-50);
        border: 1px solid var(--green-100);
        color: var(--green-700);
        border-left: 3px solid var(--green-600);
    }

    .alert-error {
        background: var(--red-50);
        border: 1px solid var(--red-100);
        color: var(--red-600);
        border-left: 3px solid var(--red-600);
    }

    /* ── Stat cards ── */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 22px;
    }

    .stat-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        padding: 18px 20px;
        border: 1px solid var(--n200);
        position: relative;
        overflow: hidden;
        animation: fadeUp 0.5s var(--ease) both;
    }

    .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .stat-card:nth-child(2) { animation-delay: 0.17s; }
    .stat-card:nth-child(3) { animation-delay: 0.24s; }
    .stat-card:nth-child(4) { animation-delay: 0.31s; }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    }

    .stat-card.modal::before  { background: var(--n300); }
    .stat-card.masuk::before  { background: var(--green-600); }
    .stat-card.keluar::before { background: var(--red-600); }
    .stat-card.saldo-pos::before { background: linear-gradient(90deg, var(--green-700), var(--green-500)); }
    .stat-card.saldo-neg::before { background: linear-gradient(90deg, var(--red-700), var(--red-600)); }

    .stat-card.saldo-pos { background: var(--green-800); border-color: var(--green-700); }
    .stat-card.saldo-neg { background: var(--red-700);   border-color: var(--red-600); }

    .stat-icon {
        width: 34px; height: 34px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }

    .stat-icon.modal  { background: var(--n100); }
    .stat-icon.masuk  { background: var(--green-50); }
    .stat-icon.keluar { background: var(--red-50); }
    .stat-icon.saldo  { background: rgba(255,255,255,0.12); }

    .stat-label {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--n500);
        margin-bottom: 5px;
    }

    .stat-card.saldo-pos .stat-label,
    .stat-card.saldo-neg .stat-label { color: rgba(255,255,255,0.55); }

    .stat-value {
        font-family: var(--font-mono);
        font-size: 1.2rem;
        font-weight: 500;
        color: var(--n900);
        letter-spacing: -0.02em;
        word-break: break-all;
    }

    .stat-card.saldo-pos .stat-value,
    .stat-card.saldo-neg .stat-value { color: var(--white); font-size: 1.05rem; }

    .stat-value.masuk  { color: var(--green-700); }
    .stat-value.keluar { color: var(--red-600); }

    .stat-sub {
        font-size: 11px;
        color: var(--n400);
        margin-top: 3px;
    }

    .stat-card.saldo-pos .stat-sub { color: rgba(255,255,255,0.4); }
    .stat-card.saldo-neg .stat-sub { color: rgba(255,255,255,0.4); }

    /* ── Filter + Export bar ── */
    .filter-bar {
        background: var(--white);
        border: 1px solid var(--n200);
        border-radius: var(--radius-lg);
        padding: 16px 20px;
        margin-bottom: 18px;
        animation: fadeUp 0.5s var(--ease) 0.38s both;
    }

    .filter-bar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .filter-bar-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: var(--n500);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .filter-active-dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--amber-600);
        display: none;
    }

    <?php if ($filter_aktif): ?>
    .filter-active-dot { display: block; }
    <?php endif; ?>

    .filter-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }

    .filter-field { display: flex; flex-direction: column; gap: 5px; }

    .filter-label {
        font-size: 10.5px;
        font-weight: 600;
        color: var(--n500);
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .filter-input,
    .filter-select {
        height: 36px;
        padding: 0 10px;
        border: 1.5px solid var(--n200);
        border-radius: var(--radius-sm);
        font-family: var(--font-body);
        font-size: 12.5px;
        color: var(--n800);
        background: var(--n50);
        outline: none;
        transition: border-color 0.18s, box-shadow 0.18s;
        appearance: none;
        -webkit-appearance: none;
    }

    .filter-input:focus,
    .filter-select:focus {
        border-color: var(--green-600);
        background: var(--white);
        box-shadow: 0 0 0 3px rgba(26,107,35,0.1);
    }

    .filter-actions { display: flex; gap: 7px; align-items: flex-end; flex-wrap: wrap; }

    .btn {
        height: 36px;
        padding: 0 16px;
        border-radius: var(--radius-sm);
        font-family: var(--font-body);
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        border: 1.5px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.18s, border-color 0.18s, box-shadow 0.18s, transform 0.1s;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn:active { transform: scale(0.97); }

    .btn-primary {
        background: var(--green-700);
        color: var(--white);
        border-color: var(--green-700);
    }
    .btn-primary:hover { background: var(--green-600); border-color: var(--green-600); box-shadow: 0 3px 10px rgba(26,107,35,0.25); }

    .btn-ghost {
        background: transparent;
        color: var(--n500);
        border-color: var(--n200);
    }
    .btn-ghost:hover { background: var(--n50); color: var(--n700); border-color: var(--n300); }

    .btn-excel {
        background: #16a34a;
        color: var(--white);
        border-color: #16a34a;
    }
    .btn-excel:hover { background: #15803d; box-shadow: 0 3px 10px rgba(22,163,74,0.3); }

    .btn-pdf {
        background: var(--red-600);
        color: var(--white);
        border-color: var(--red-600);
    }
    .btn-pdf:hover { background: var(--red-700); box-shadow: 0 3px 10px rgba(192,57,43,0.3); }

    /* ── Transaction table card ── */
    .table-card {
        background: var(--white);
        border: 1px solid var(--n200);
        border-radius: var(--radius-lg);
        overflow: hidden;
        animation: fadeUp 0.5s var(--ease) 0.44s both;
    }

    .table-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 22px;
        border-bottom: 1px solid var(--n100);
    }

    .table-card-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--n900);
    }

    .table-card-count {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--n100);
        border-radius: 999px;
        padding: 3px 10px;
        font-size: 11px;
        font-weight: 600;
        color: var(--n600);
    }

    <?php if ($filter_aktif): ?>
    .table-card-count { background: var(--amber-50); color: var(--amber-600); }
    <?php endif; ?>

    .table-wrap { overflow-x: auto; }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    thead tr {
        background: var(--n50);
        border-bottom: 1.5px solid var(--n200);
    }

    th {
        padding: 10px 16px;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--n500);
        text-align: left;
        white-space: nowrap;
    }

    th.right, td.right { text-align: right; }

    td {
        padding: 13px 16px;
        border-bottom: 1px solid var(--n100);
        vertical-align: middle;
    }

    tbody tr { transition: background 0.15s; }
    tbody tr:hover { background: var(--n50); }
    tbody tr:last-child td { border-bottom: none; }

    .td-date {
        font-family: var(--font-mono);
        font-size: 12px;
        color: var(--n500);
        white-space: nowrap;
    }

    .td-keterangan {
        font-weight: 500;
        color: var(--n800);
        max-width: 260px;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .badge-masuk   { background: var(--green-100); color: var(--green-700); }
    .badge-keluar  { background: var(--red-100);   color: var(--red-700); }

    .td-nominal {
        font-family: var(--font-mono);
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
    }

    .td-nominal.masuk  { color: var(--green-700); }
    .td-nominal.keluar { color: var(--red-600); }

    .empty-state {
        padding: 52px 20px;
        text-align: center;
    }

    .empty-icon {
        width: 48px; height: 48px;
        background: var(--n100);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 14px;
    }

    .empty-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--n700);
        margin-bottom: 4px;
    }

    .empty-sub {
        font-size: 12.5px;
        color: var(--n400);
    }

    /* ══ ANIMATIONS ══════════════════════════════════════════════════ */
    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-20px); }
        to   { opacity: 1; transform: translateX(0); }
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ══ RESPONSIVE ══════════════════════════════════════════════════ */
    @media (max-width: 900px) {
        :root { --sidebar-w: 0px; }

        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
            flex-direction: row;
            flex-wrap: wrap;
        }

        .sidebar-header,
        .sidebar-body,
        .sidebar-footer { width: 100%; }

        .app-shell { flex-direction: column; }
        .main-content { margin-left: 0; padding: 16px; }
        .stat-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 540px) {
        .stat-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
        .filter-controls { flex-direction: column; align-items: stretch; }
        .filter-actions { flex-wrap: wrap; }
    }
    </style>
</head>
<body>
<div class="app-shell">

    <!-- ══════════════════════════════════════════════════════════════════
         SIDEBAR
    ═══════════════════════════════════════════════════════════════════ -->
    <aside class="sidebar">
        <div class="sidebar-glow"></div>

        <!-- Header -->
        <div class="sidebar-header">
            <div class="sidebar-brand-row">
                <div class="brand-dot"></div>
                <span class="brand-label">Unit Usaha Aktif</span>
            </div>
            <div class="sidebar-unit-name"><?php echo htmlspecialchars($nama_unit_aktif); ?></div>
            <div class="sidebar-date"><?php echo $tgl_display; ?></div>
        </div>

        <!-- Form Catat Mutasi -->
        <div class="sidebar-body">
            <p class="sidebar-section-title">Catat Mutasi Kas</p>

            <form method="POST" action="" class="mutasi-form" id="formMutasi">

                <div>
                    <label class="form-label" for="jenis_transaksi">Jenis Mutasi</label>
                    <div class="select-wrapper">
                        <select name="jenis_transaksi" id="jenis_transaksi" class="form-select" required>
                            <option value="pemasukan">Pemasukan (+)</option>
                            <option value="pengeluaran">Pengeluaran (−)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="nominal">Nominal (Rp)</label>
                    <div class="nominal-wrapper">
                        <span class="nominal-prefix">Rp</span>
                        <input type="number" name="nominal" id="nominal" min="1"
                               class="form-input" placeholder="0" required>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <input type="text" name="keterangan" id="keterangan"
                           class="form-input" placeholder="Mis: Jual 5 galon air"
                           autocomplete="off" required>
                </div>

                <button type="submit" name="simpan_transaksi" class="btn-submit-sidebar">
                    Simpan Transaksi
                </button>

            </form>
        </div>

        <!-- Footer logout -->
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout"
               onclick="return confirm('Yakin ingin keluar dari sesi ini?');">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M6 14H3a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M11 11l3-3-3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 8H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                Keluar dari Sesi
            </a>
        </div>
    </aside>

    <!-- ══════════════════════════════════════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════════════════════════════════════ -->
    <main class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Dashboard Operasional</h1>
                <p>Rekap keuangan real-time berdasarkan seluruh transaksi tercatat</p>
            </div>
            <div class="topbar-badge">
                <div class="topbar-badge-dot"></div>
                <?php echo $trx_hari_ini; ?> transaksi hari ini
            </div>
        </div>

        <!-- Alert banners -->
        <?php if ($success): ?>
        <div class="alert alert-success" role="status">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.4"/>
                <path d="M5 8l2 2 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error" role="alert">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.4"/>
                <path d="M8 5v3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                <circle cx="8" cy="11" r="0.7" fill="currentColor"/>
            </svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="stat-grid">

            <!-- Modal Awal -->
            <div class="stat-card modal">
                <div class="stat-icon modal">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect x="1.5" y="4" width="13" height="9" rx="1.5" stroke="#6b7280" stroke-width="1.3"/>
                        <path d="M4.5 4V3a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v1" stroke="#6b7280" stroke-width="1.3"/>
                        <circle cx="8" cy="8.5" r="1.5" stroke="#6b7280" stroke-width="1.2"/>
                    </svg>
                </div>
                <div class="stat-label">Modal Awal</div>
                <div class="stat-value">Rp <?php echo number_format($saldo_awal, 0, ',', '.'); ?></div>
                <div class="stat-sub">Saldo pembukaan unit</div>
            </div>

            <!-- Total Pemasukan -->
            <div class="stat-card masuk">
                <div class="stat-icon masuk">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M8 13V3" stroke="#1a6b23" stroke-width="1.4" stroke-linecap="round"/>
                        <path d="M4 7l4-4 4 4" stroke="#1a6b23" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 13h12" stroke="#1a6b23" stroke-width="1.4" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="stat-label">Total Pemasukan</div>
                <div class="stat-value masuk">Rp <?php echo number_format($total_pemasukan, 0, ',', '.'); ?></div>
                <div class="stat-sub">Kumulatif seluruh waktu</div>
            </div>

            <!-- Total Pengeluaran -->
            <div class="stat-card keluar">
                <div class="stat-icon keluar">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M8 3v10" stroke="#c0392b" stroke-width="1.4" stroke-linecap="round"/>
                        <path d="M4 9l4 4 4-4" stroke="#c0392b" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 3h12" stroke="#c0392b" stroke-width="1.4" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="stat-label">Total Pengeluaran</div>
                <div class="stat-value keluar">Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></div>
                <div class="stat-sub">Kumulatif seluruh waktu</div>
            </div>

            <!-- Saldo Kas -->
            <div class="stat-card <?php echo $saldo_positif ? 'saldo-pos' : 'saldo-neg'; ?>">
                <div class="stat-icon saldo">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect x="1.5" y="5" width="13" height="8.5" rx="1.5" stroke="rgba(255,255,255,0.75)" stroke-width="1.3"/>
                        <path d="M4.5 5V4a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v1" stroke="rgba(255,255,255,0.75)" stroke-width="1.3"/>
                        <path d="M6.5 9h3" stroke="rgba(255,255,255,0.75)" stroke-width="1.3" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="stat-label">Saldo Kas Aktual</div>
                <div class="stat-value">Rp <?php echo number_format(abs($saldo_akhir), 0, ',', '.'); ?></div>
                <div class="stat-sub"><?php echo $saldo_positif ? 'Kondisi kas sehat' : 'Peringatan: saldo negatif'; ?></div>
            </div>

        </div>

        <!-- Filter & Export Bar -->
        <div class="filter-bar">
            <div class="filter-bar-header">
                <div class="filter-bar-title">
                    <svg width="12" height="12" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M1 2.5h12M3.5 7h7M6 11.5h2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                    </svg>
                    Filter & Ekspor Laporan
                    <div class="filter-active-dot" title="Filter aktif"></div>
                </div>
                <?php if ($filter_aktif): ?>
                <a href="index.php" class="btn btn-ghost" style="height: 28px; padding: 0 10px; font-size: 11px;">
                    Reset Filter
                </a>
                <?php endif; ?>
            </div>

            <form method="GET" action="" class="filter-controls">
                <div class="filter-field">
                    <label class="filter-label" for="f_mulai">Mulai Tanggal</label>
                    <input type="date" name="tgl_mulai" id="f_mulai" class="filter-input"
                           value="<?php echo htmlspecialchars($tgl_mulai_val); ?>">
                </div>

                <div class="filter-field">
                    <label class="filter-label" for="f_akhir">Sampai Tanggal</label>
                    <input type="date" name="tgl_akhir" id="f_akhir" class="filter-input"
                           value="<?php echo htmlspecialchars($tgl_akhir_val); ?>">
                </div>

                <div class="filter-field">
                    <label class="filter-label" for="f_jenis">Jenis</label>
                    <select name="jenis" id="f_jenis" class="filter-select" style="min-width: 150px;">
                        <option value="semua"      <?php echo $jenis_val === 'semua'      ? 'selected' : ''; ?>>Semua Transaksi</option>
                        <option value="pemasukan"  <?php echo $jenis_val === 'pemasukan'  ? 'selected' : ''; ?>>Hanya Pemasukan</option>
                        <option value="pengeluaran"<?php echo $jenis_val === 'pengeluaran'? 'selected' : ''; ?>>Hanya Pengeluaran</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <svg width="12" height="12" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="6" cy="6" r="4.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M10 10l2.5 2.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                        Terapkan
                    </button>
                    <button type="submit" formaction="export_excel.php" class="btn btn-excel">
                        <svg width="12" height="12" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M2 10.5v1.5h10v-1.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                            <path d="M7 2v7M4.5 6.5L7 9l2.5-2.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Excel
                    </button>
                    <button type="submit" formaction="export_pdf.php" formtarget="_blank" class="btn btn-pdf">
                        <svg width="12" height="12" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M2 10.5v1.5h10v-1.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                            <path d="M7 2v7M4.5 6.5L7 9l2.5-2.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        PDF
                    </button>
                </div>
            </form>
        </div>

        <!-- Transaction Table -->
        <div class="table-card">
            <div class="table-card-header">
                <div class="table-card-title">Riwayat Transaksi</div>
                <div class="table-card-count">
                    <?php echo $jumlah_baris; ?> entri<?php echo $filter_aktif ? ' (terfilter)' : ''; ?>
                </div>
            </div>

            <div class="table-wrap">
                <?php if ($jumlah_baris > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 110px;">Tanggal</th>
                            <th>Keterangan</th>
                            <th style="width: 110px;">Jenis</th>
                            <th class="right" style="width: 155px;">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($hasil_transaksi)):
                            $is_masuk = $row['jenis_transaksi'] === 'pemasukan';
                        ?>
                        <tr>
                            <td class="td-date">
                                <?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?>
                            </td>
                            <td class="td-keterangan">
                                <?php echo htmlspecialchars($row['keterangan']); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $is_masuk ? 'badge-masuk' : 'badge-keluar'; ?>">
                                    <?php echo $is_masuk ? 'Pemasukan' : 'Pengeluaran'; ?>
                                </span>
                            </td>
                            <td class="right td-nominal <?php echo $is_masuk ? 'masuk' : 'keluar'; ?>">
                                <?php echo $is_masuk ? '+' : '−'; ?>
                                Rp <?php echo number_format($row['nominal'], 0, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="3" y="5" width="16" height="14" rx="2" stroke="#9ca3af" stroke-width="1.4"/>
                            <path d="M7 2v3M15 2v3" stroke="#9ca3af" stroke-width="1.4" stroke-linecap="round"/>
                            <path d="M3 9h16" stroke="#9ca3af" stroke-width="1.4"/>
                        </svg>
                    </div>
                    <p class="empty-title">Belum ada transaksi</p>
                    <p class="empty-sub"><?php echo $filter_aktif
                        ? 'Tidak ada transaksi pada rentang tanggal atau filter yang dipilih.'
                        : 'Gunakan form di samping untuk mencatat mutasi kas pertama.'; ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<script>
    // Auto-dismiss alerts setelah 4 detik
    (function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(el) {
            setTimeout(function() {
                el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                el.style.opacity    = '0';
                el.style.transform  = 'translateY(-6px)';
                setTimeout(function() { el.remove(); }, 400);
            }, 4000);
        });
    })();

    // Format nominal input dengan pemisah ribuan saat mengetik
    (function() {
        const input = document.getElementById('nominal');
        if (!input) return;
        input.addEventListener('input', function() {
            // Hanya tampilkan visual — value tetap angka murni untuk POST
            const raw = this.value.replace(/\D/g, '');
            this.value = raw;
        });
    })();

    // Validasi: tgl_akhir tidak boleh sebelum tgl_mulai
    (function() {
        const mulai = document.getElementById('f_mulai');
        const akhir = document.getElementById('f_akhir');
        if (!mulai || !akhir) return;
        mulai.addEventListener('change', function() {
            if (akhir.value && akhir.value < this.value) {
                akhir.value = this.value;
            }
            akhir.min = this.value;
        });
    })();
</script>
</body>
</html>