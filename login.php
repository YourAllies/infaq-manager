<?php
// login.php

// Jangan include config.php secara langsung agar tidak redirect otomatis
// Tapi kita butuh $pdo dan helper → kita include config.php dengan proteksi

// Cek jika sudah login
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Koneksi database dan load helper
$host = 'localhost';
$dbname = 'infaq_manager_app';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Include helper
require_once 'config/helper.php';

$error = '';
$remembered_username = '';

// Cek session expired
$show_expired = isset($_GET['expired']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, is_active FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['is_active'] == 0) {
                $error = 'Akun ini dinonaktifkan. Hubungi administrator.';
            } elseif (password_verify($password, $user['password'])) {
                session_start();
                if ($user && password_verify($password, $user['password'])) {
				$_SESSION['user_id'] = $user['id'];
				$_SESSION['nama'] = $user['nama'];
				$_SESSION['username'] = $user['username'];
				$_SESSION['role'] = $user['role']; 

                // Cookie "Ingat Saya" untuk username
                if (isset($_POST['remember'])) {
                    setcookie('login_username', $username, time() + (30 * 24 * 60 * 60), '/', '', false, true); // HttpOnly
                } else {
                    setcookie('login_username', '', time() - 3600, '/');
                }

                header('Location: index.php');
                exit;
            } else {
                $error = 'Username atau password salah.';
            }
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
}

// Ambil username dari cookie jika ada
$remembered_username = $_COOKIE['login_username'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Infaq Manager</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: hsl(240, 10%, 8%);
            color: hsl(0, 0%, 90%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 30px;
            background: hsl(240, 12%, 16%);
            border: 1px solid hsl(240, 10%, 30%);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: hsl(150, 45%, 42%);
            font-weight: 600;
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
        .input-group {
            position: relative;
        }
        .form-control {
            width: 100%;
            padding: 12px 12px 12px 14px;
            border: 1px solid hsl(240, 10%, 30%);
            background: hsl(240, 10%, 12%);
            color: hsl(0, 0%, 90%);
            border-radius: 6px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: hsl(150, 45%, 42%);
            box-shadow: 0 0 0 2px hsla(150, 45%, 42%, 0.25);
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: hsl(0, 0%, 60%);
            background: none;
            border: none;
        }
        .toggle-password:hover {
            color: hsl(0, 0%, 80%);
        }
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        .form-check input {
            margin: 0;
        }
        .form-check label {
            font-size: 0.9em;
            color: hsl(0, 0%, 70%);
            cursor: pointer;
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
            text-align: center;
            margin: 8px 0;
        }
        .text-success {
            color: hsl(150, 45%, 42%);
            font-size: 0.9em;
            text-align: center;
            margin: 8px 0;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
            color: hsl(0, 0%, 60%);
        }
        .login-footer a {
            color: hsl(150, 45%, 42%);
            text-decoration: none;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        .expired-notice {
            background: hsla(360, 60%, 50%, 0.1);
            border: 1px solid hsl(360, 60%, 50%);
            padding: 10px;
            border-radius: 6px;
            color: hsl(360, 60%, 50%);
            font-size: 0.9em;
            text-align: center;
            margin-bottom: 16px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
</head>
<body>
    <div class="login-container">
        <h2>Masuk ke Infaq Manager</h2>

        <!-- Notifikasi Session Habis -->
        <?php if ($show_expired): ?>
            <div class="expired-notice">
                ⏳ Sesi Anda telah berakhir karena tidak aktif selama 4 jam.
            </div>
        <?php endif; ?>

        <!-- Pesan Error -->
        <?php if ($error): ?>
            <div class="text-danger">❌ <?= e($error) ?></div>
        <?php endif; ?>

        <!-- Form Login -->
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    name="username"
                    id="username"
                    class="form-control"
                    value="<?= e($remembered_username) ?>"
                    required
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword()" title="Tampilkan password">
                        <i data-feather="eye-off" id="toggle-icon"></i>
                    </button>
                </div>
            </div>

            <div class="form-check">
                <input type="checkbox" name="remember" id="remember" <?= $remembered_username ? 'checked' : '' ?>>
                <label for="remember">Ingat saya</label>
            </div>

            <button type="submit" class="btn">Masuk</button>
        </form>

        <div class="login-footer">
            <a href="#">Lupa password?</a>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggle-icon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.setAttribute('data-feather', 'eye');
            } else {
                pwd.type = 'password';
                icon.setAttribute('data-feather', 'eye-off');
            }
            feather.replace();
        }

        // Inisialisasi Feather Icons
        feather.replace();
    </script>
</body>
</html>