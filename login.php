<?php
session_start();
include 'koneksi.php';

// === AUTH GUARD: Harus di baris pertama, sebelum query apapun ===
if (isset($_SESSION['id_unit'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if (isset($_POST['submit'])) {
    // Sanitasi input sebelum digunakan
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password_input = $_POST['password'];

    if (empty($username) || empty($password_input)) {
        $error = "Username dan kata sandi tidak boleh kosong.";
    } else {
        // Query hanya berdasarkan username; password diverifikasi via password_verify()
        $query = "SELECT id_unit, nama_unit, password, role FROM unit_usaha WHERE username = ? AND status = 'aktif'";
        $stmt  = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($hasil) > 0) {
            $data = mysqli_fetch_assoc($hasil);

            if (password_verify($password_input, $data['password'])) {
                $_SESSION['id_unit']   = $data['id_unit'];
                $_SESSION['nama_unit'] = $data['nama_unit'];
                $_SESSION['role']      = $data['role'];

                header("Location: " . ($data['role'] === 'admin' ? 'admin_dashboard.php' : 'index.php'));
                exit();
            } else {
                $error = "Kata sandi salah. Silakan coba lagi.";
            }
        } else {
            $error = "Username tidak ditemukan atau akun tidak aktif.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Sistem Keuangan Darussalam</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ── BRAND TOKENS ─────────────────────────────── */
        :root {
            --green-900:  #0a2f10;
            --green-800:  #0f4a18;
            --green-700:  #1a6b23;
            --green-600:  #22882e;
            --green-100:  #d4edda;
            --green-50:   #f0f9f1;

            --red-600:    #c0392b;
            --red-50:     #fdf0ee;
            --red-100:    #fbd5d2;

            --neutral-900: #111827;
            --neutral-700: #374151;
            --neutral-500: #6b7280;
            --neutral-300: #d1d5db;
            --neutral-200: #e5e7eb;
            --neutral-100: #f3f4f6;
            --neutral-50:  #f9fafb;
            --white:       #ffffff;

            --font-sans:   'Plus Jakarta Sans', sans-serif;
            --font-serif:  'Amiri', serif;

            --transition:  0.2s ease;
            --radius-sm:   6px;
            --radius-md:   10px;
            --radius-lg:   16px;
        }

        /* ── RESET & BASE ─────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: var(--font-sans);
            font-size: 15px;
            color: var(--neutral-900);
            background: var(--neutral-100);
            -webkit-font-smoothing: antialiased;
        }

        /* ── LAYOUT: SPLIT PANEL ──────────────────────── */
        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        /* ── LEFT PANEL ───────────────────────────────── */
        .panel-brand {
            position: relative;
            background: var(--green-800);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 52px;
            animation: slideInLeft 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        /* Geometric Islamic pattern overlay */
        .panel-brand::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                repeating-linear-gradient(
                    60deg,
                    transparent,
                    transparent 38px,
                    rgba(255,255,255,0.04) 38px,
                    rgba(255,255,255,0.04) 39px
                ),
                repeating-linear-gradient(
                    -60deg,
                    transparent,
                    transparent 38px,
                    rgba(255,255,255,0.04) 38px,
                    rgba(255,255,255,0.04) 39px
                ),
                repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 38px,
                    rgba(255,255,255,0.03) 38px,
                    rgba(255,255,255,0.03) 39px
                );
            pointer-events: none;
        }

        /* Radial glow accent */
        .panel-brand::after {
            content: '';
            position: absolute;
            bottom: -120px;
            right: -120px;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(34,136,46,0.35) 0%, transparent 70%);
            pointer-events: none;
        }

        .brand-top { position: relative; z-index: 1; }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 999px;
            padding: 6px 14px 6px 8px;
            margin-bottom: 40px;
        }

        .brand-badge-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 6px #4ade80;
        }

        .brand-badge span {
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.9);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* Mosque SVG icon */
        .mosque-icon {
            width: 64px;
            height: 64px;
            margin-bottom: 24px;
        }

        .brand-name-ar {
            font-family: var(--font-serif);
            font-size: 3.2rem;
            line-height: 1.1;
            color: var(--white);
            margin-bottom: 8px;
        }

        .brand-name-en {
            font-family: var(--font-serif);
            font-size: 1.15rem;
            color: rgba(255,255,255,0.65);
            letter-spacing: 0.04em;
            margin-bottom: 20px;
        }

        .brand-tagline {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            line-height: 1.6;
            max-width: 320px;
        }

        .brand-bottom {
            position: relative;
            z-index: 1;
        }

        .stat-row {
            display: flex;
            gap: 32px;
        }

        .stat-item {}

        .stat-num {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--white);
            line-height: 1;
        }

        .stat-label {
            font-size: 11px;
            color: rgba(255,255,255,0.5);
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .stat-divider {
            width: 1px;
            background: rgba(255,255,255,0.15);
            align-self: stretch;
        }

        /* ── RIGHT PANEL ──────────────────────────────── */
        .panel-form {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            background: var(--white);
            animation: slideInRight 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .form-container {
            width: 100%;
            max-width: 380px;
        }

        .form-header {
            margin-bottom: 36px;
        }

        .form-eyebrow {
            font-size: 11px;
            font-weight: 700;
            color: var(--green-700);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 8px;
        }

        .form-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--neutral-900);
            line-height: 1.2;
            margin-bottom: 8px;
        }

        .form-subtitle {
            font-size: 13.5px;
            color: var(--neutral-500);
            line-height: 1.55;
        }

        /* ── ERROR ALERT ──────────────────────────────── */
        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--red-50);
            border: 1px solid var(--red-100);
            border-left: 3px solid var(--red-600);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            margin-bottom: 24px;
            animation: shakeX 0.4s ease;
        }

        .alert-icon {
            flex-shrink: 0;
            width: 16px; height: 16px;
            margin-top: 1px;
        }

        .alert-text {
            font-size: 13px;
            color: var(--red-600);
            font-weight: 500;
            line-height: 1.45;
        }

        /* ── FORM FIELDS ──────────────────────────────── */
        .field-group {
            margin-bottom: 18px;
        }

        .field-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--neutral-700);
            margin-bottom: 7px;
            letter-spacing: 0.01em;
        }

        .field-wrapper {
            position: relative;
        }

        .field-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--neutral-400, #9ca3af);
            pointer-events: none;
            transition: color var(--transition);
        }

        .field-input {
            width: 100%;
            height: 44px;
            padding: 0 14px 0 40px;
            border: 1.5px solid var(--neutral-200);
            border-radius: var(--radius-md);
            font-family: var(--font-sans);
            font-size: 14px;
            color: var(--neutral-900);
            background: var(--neutral-50);
            outline: none;
            transition: border-color var(--transition), background var(--transition), box-shadow var(--transition);
        }

        .field-input::placeholder {
            color: var(--neutral-300);
        }

        .field-input:hover {
            border-color: var(--neutral-300);
            background: var(--white);
        }

        .field-input:focus {
            border-color: var(--green-700);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(26,107,35,0.12);
        }

        .field-input:focus + .field-icon,
        .field-wrapper:focus-within .field-icon {
            color: var(--green-700);
        }

        /* Password toggle */
        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: var(--neutral-400, #9ca3af);
            transition: color var(--transition);
            display: flex;
            align-items: center;
        }

        .pw-toggle:hover { color: var(--neutral-700); }

        /* ── SUBMIT BUTTON ────────────────────────────── */
        .btn-submit {
            width: 100%;
            height: 46px;
            margin-top: 8px;
            background: var(--green-700);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-family: var(--font-sans);
            font-size: 14.5px;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.02em;
            position: relative;
            overflow: hidden;
            transition: background var(--transition), transform 0.1s ease, box-shadow var(--transition);
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, transparent 0%, rgba(255,255,255,0.08) 50%, transparent 100%);
            transform: translateX(-100%);
            transition: transform 0.5s ease;
        }

        .btn-submit:hover {
            background: var(--green-600);
            box-shadow: 0 4px 16px rgba(26,107,35,0.35);
        }

        .btn-submit:hover::after { transform: translateX(100%); }
        .btn-submit:active { transform: scale(0.985); }

        /* ── FOOTER ───────────────────────────────────── */
        .form-footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--neutral-200);
            text-align: center;
        }

        .form-footer p {
            font-size: 11.5px;
            color: var(--neutral-500);
            line-height: 1.6;
        }

        .form-footer strong {
            color: var(--neutral-700);
            font-weight: 600;
        }

        /* ── ANIMATIONS ───────────────────────────────── */
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-24px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(24px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @keyframes shakeX {
            0%, 100% { transform: translateX(0); }
            20%       { transform: translateX(-6px); }
            40%       { transform: translateX(6px); }
            60%       { transform: translateX(-4px); }
            80%       { transform: translateX(4px); }
        }

        /* ── RESPONSIVE ───────────────────────────────── */
        @media (max-width: 768px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }

            .panel-brand {
                padding: 36px 28px;
                min-height: auto;
            }

            .brand-name-ar { font-size: 2.2rem; }

            .stat-row { gap: 20px; }

            .panel-form {
                padding: 36px 24px;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">

    <!-- ═══ LEFT: BRAND PANEL ═══════════════════════════════════════ -->
    <div class="panel-brand">
        <div class="brand-top">
            <div class="brand-badge">
                <div class="brand-badge-dot"></div>
                <span>Sistem Aktif</span>
            </div>

            <!-- Mosque Icon (SVG inline) -->
            <svg class="mosque-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="32" cy="10" r="5" stroke="rgba(255,255,255,0.8)" stroke-width="1.5" fill="none"/>
                <path d="M32 5 L32 1" stroke="rgba(255,255,255,0.8)" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M20 30 Q20 18 32 18 Q44 18 44 30" stroke="rgba(255,255,255,0.7)" stroke-width="1.5" fill="none"/>
                <rect x="14" y="30" width="36" height="20" rx="2" stroke="rgba(255,255,255,0.7)" stroke-width="1.5" fill="rgba(255,255,255,0.06)"/>
                <rect x="8" y="30" width="8" height="20" rx="1" stroke="rgba(255,255,255,0.5)" stroke-width="1.2" fill="rgba(255,255,255,0.04)"/>
                <rect x="48" y="30" width="8" height="20" rx="1" stroke="rgba(255,255,255,0.5)" stroke-width="1.2" fill="rgba(255,255,255,0.04)"/>
                <rect x="27" y="36" width="10" height="14" rx="5" stroke="rgba(255,255,255,0.6)" stroke-width="1.2" fill="rgba(255,255,255,0.06)"/>
                <line x1="8" y1="50" x2="56" y2="50" stroke="rgba(255,255,255,0.4)" stroke-width="1.2"/>
            </svg>

            <h1 class="brand-name-ar">دار السلام</h1>
            <p class="brand-name-en">Darussalam · Rajapolah, Tasikmalaya</p>
            <p class="brand-tagline">
                Platform pengelolaan keuangan terpadu seluruh unit usaha Pondok Pesantren Darussalam.
            </p>
        </div>

        <div class="brand-bottom">
            <div class="stat-row">
                <div class="stat-item">
                    <div class="stat-num">11</div>
                    <div class="stat-label">Unit Usaha</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-num">1</div>
                    <div class="stat-label">Admin Pusat</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-num">v2.0</div>
                    <div class="stat-label">Versi Sistem</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ RIGHT: FORM PANEL ════════════════════════════════════════ -->
    <div class="panel-form">
        <div class="form-container">

            <div class="form-header">
                <p class="form-eyebrow">Gerbang Akses</p>
                <h2 class="form-title">Selamat Datang</h2>
                <p class="form-subtitle">Masuk menggunakan kredensial unit usaha atau akun administrator yang telah diberikan.</p>
            </div>

            <?php if ($error !== ''): ?>
            <div class="alert-error" role="alert">
                <svg class="alert-icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="8" cy="8" r="7" stroke="#C0392B" stroke-width="1.5"/>
                    <path d="M8 4.5V8.5" stroke="#C0392B" stroke-width="1.5" stroke-linecap="round"/>
                    <circle cx="8" cy="11" r="0.75" fill="#C0392B"/>
                </svg>
                <p class="alert-text"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>

                <div class="field-group">
                    <label class="field-label" for="username">Username</label>
                    <div class="field-wrapper">
                        <input
                            class="field-input"
                            type="text"
                            id="username"
                            name="username"
                            placeholder="contoh: dn_water_admin"
                            autocomplete="username"
                            required
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        >
                        <svg class="field-icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="8" cy="5.5" r="2.5" stroke="currentColor" stroke-width="1.25"/>
                            <path d="M2.5 13.5C2.5 11 5 9 8 9C11 9 13.5 11 13.5 13.5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label" for="password">Kata Sandi</label>
                    <div class="field-wrapper">
                        <input
                            class="field-input"
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan kata sandi"
                            autocomplete="current-password"
                            required
                        >
                        <svg class="field-icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="2.5" y="7" width="11" height="7.5" rx="1.5" stroke="currentColor" stroke-width="1.25"/>
                            <path d="M5 7V5C5 3.34 6.34 2 8 2C9.66 2 11 3.34 11 5V7" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                            <circle cx="8" cy="10.5" r="1" fill="currentColor"/>
                        </svg>
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Tampilkan kata sandi">
                            <svg id="eyeIcon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 8C1 8 3.5 3 8 3C12.5 3 15 8 15 8C15 8 12.5 13 8 13C3.5 13 1 8 1 8Z" stroke="currentColor" stroke-width="1.25"/>
                                <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.25"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-submit">
                    Masuk ke Sistem
                </button>

            </form>

            <div class="form-footer">
                <p>Lupa kata sandi? Hubungi <strong>Administrator Pusat</strong>.<br>
                Hak akses dikelola oleh pengelola sistem pesantren.</p>
            </div>

        </div>
    </div>
</div>

<script>
    // Password visibility toggle
    const pwToggle = document.getElementById('pwToggle');
    const pwField  = document.getElementById('password');
    const eyeIcon  = document.getElementById('eyeIcon');

    const eyeOpen = `<path d="M1 8C1 8 3.5 3 8 3C12.5 3 15 8 15 8C15 8 12.5 13 8 13C3.5 13 1 8 1 8Z" stroke="currentColor" stroke-width="1.25"/><circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.25"/>`;
    const eyeShut = `<path d="M2 2L14 14M6.5 6.7A2 2 0 0 0 9.3 9.5M4.2 4.5C2.7 5.6 1.5 7.5 1.5 8C1.5 8 4 13 8 13C9.4 13 10.7 12.4 11.8 11.6M7 3.1C7.3 3 7.7 3 8 3C12 3 14.5 8 14.5 8C14.5 8 13.9 9.3 12.9 10.5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>`;

    pwToggle.addEventListener('click', () => {
        const isPassword = pwField.type === 'password';
        pwField.type    = isPassword ? 'text' : 'password';
        eyeIcon.innerHTML = isPassword ? eyeShut : eyeOpen;
    });

    // Auto-focus username field
    document.getElementById('username').focus();
</script>

</body>
</html>