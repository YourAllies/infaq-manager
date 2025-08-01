<?php
// register-admin.php
// Hanya untuk membuat admin pertama kali
// Setelah digunakan, HARAP HAPUS atau RENAME file ini

// Cek dan koneksi ke database
$host = 'localhost';
$dbname = 'infaq_manager_app'; // Sesuaikan jika berbeda
$username_db = 'root';         // Sesuaikan
$password_db = '';             // Sesuaikan

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<h3>❌ Koneksi Database Gagal</h3><p>" . $e->getMessage() . "</p>");
}

// Cek apakah sudah ada user
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$user_count = $stmt->fetchColumn();

// Jika sudah ada user, tampilkan halaman blokir
if ($user_count > 0) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Akses Ditolak</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <style>
            body {
                font-family: 'Segoe UI', sans-serif;
                background: hsl(240, 10%, 8%);
                color: hsl(0, 0%, 90%);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
            }
            .lock-box {
                background: hsl(240, 12%, 16%);
                border: 1px solid hsl(240, 10%, 30%);
                border-radius: 10px;
                padding: 30px;
                text-align: center;
                max-width: 500px;
                width: 100%;
            }
            .lock-icon {
                font-size: 3em;
                color: hsl(360, 60%, 50%);
                margin-bottom: 16px;
            }
            h3 {
                color: hsl(360, 60%, 50%);
                margin-top: 0;
            }
            .btn {
                display: inline-block;
                padding: 10px 20px;
                background: hsl(150, 45%, 42%);
                color: white;
                text-decoration: none;
                border-radius: 6px;
                margin-top: 15px;
                font-size: 0.95em;
            }
            .btn:hover {
                background: hsl(150, 50%, 50%);
            }
            code {
                background: hsl(240, 10%, 16%);
                padding: 2px 6px;
                border-radius: 4px;
                font-family: monospace;
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    </head>
    <body>
        <div class="lock-box">
            <div class="lock-icon">
                <i data-feather="lock"></i>
            </div>
            <h3>Registrasi Dibatasi</h3>
            <p>Sudah ada akun di sistem. Untuk keamanan, fitur pembuatan admin hanya bisa dijalankan sekali.</p>
            <p><strong>Harap segera hapus atau rename file ini:</strong></p>
            <code>register-admin.php</code>
            <br><br>
            <a href="login.php" class="btn">Kembali ke Login</a>
        </div>
        <script>feather.replace();</script>
    </body>
    </html>
    <?php
    exit;
}

// Inisialisasi variabel
$error = '';
$success = '';
$username = '';

// Proses form saat disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input
    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek apakah username sudah ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username sudah digunakan. Silakan pilih yang lain.';
        } else {
            // Hash password dengan argon2id
            $hashed = password_hash($password, PASSWORD_ARGON2ID);

            // Simpan ke database
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, is_active, timestamp) VALUES (?, ?, 1, NOW())");
                $stmt->execute([$username, $hashed]);
                $success = "Admin berhasil dibuat! Anda akan dialihkan ke halaman login...";
                // Redirect otomatis setelah 3 detik
                header('refresh:3;url=login.php');
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan saat menyimpan ke database: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Admin - Infaq Manager</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: hsl(240, 10%, 8%);
            color: hsl(0, 0%, 90%);
            margin: 0;
            padding: 0;
        }
        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .register-box {
            width: 100%;
            max-width: 450px;
            background: hsl(240, 12%, 16%);
            border: 1px solid hsl(240, 10%, 30%);
            border-radius: 10px;
            padding: 30px;
        }
        .register-box h2 {
            text-align: center;
            margin-bottom: 12px;
            color: hsl(150, 45%, 42%);
        }
        .register-box p {
            text-align: center;
            font-size: 0.9em;
            color: hsl(0, 0%, 60%);
            margin-bottom: 24px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.95em;
            color: hsl(0, 0%, 85%);
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid hsl(240, 10%, 30%);
            background: hsl(240, 10%, 12%);
            color: hsl(0, 0%, 90%);
            border-radius: 6px;
            font-size: 1em;
        }
        .form-control:focus {
            outline: none;
            border-color: hsl(150, 45%, 42%);
            box-shadow: 0 0 0 2px hsla(150, 45%, 42%, 0.2);
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: hsl(150, 45%, 42%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover {
            background: hsl(150, 50%, 50%);
        }
        .text-danger {
            color: hsl(360, 60%, 50%);
            font-size: 0.9em;
            margin: 8px 0;
            text-align: center;
        }
        .text-success {
            color: hsl(150, 45%, 42%);
            font-size: 0.95em;
            margin: 8px 0;
            text-align: center;
            padding: 10px;
            background: hsla(150, 45%, 42%, 0.1);
            border-radius: 6px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 0.9em;
            color: hsl(150, 45%, 42%);
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <h2>🔐 Buat Admin Pertama</h2>
            <p>
                Ini adalah kesempatan satu-satunya untuk membuat akun admin.<br>
                Setelah selesai, <strong>hapus atau rename file ini</strong> untuk keamanan.
            </p>

            <?php if ($error): ?>
                <div class="text-danger">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="text-success">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-control"
                            value="<?= htmlspecialchars($username) ?>"
                            required
                            autocomplete="off"
                        >
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password</label>
                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            class="form-control"
                            required
                        >
                    </div>
                    <button type="submit" class="btn">Buat Akun Admin</button>
                </form>
                <a href="login.php" class="back-link">← Sudah punya akun? Masuk</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Inisialisasi Feather Icons
        feather.replace();
    </script>
</body>
</html>