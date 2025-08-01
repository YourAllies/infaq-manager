<?php
// profil.php
require_once 'config/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data pengguna
$stmt = $pdo->prepare("SELECT nama, username, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Pengguna tidak ditemukan.");
}

// Proses ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    $error = null;

    // Cek password lama
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $stored_password = $stmt->fetchColumn();

    if (!password_verify($password_lama, $stored_password)) {
        $error = "Password lama salah.";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password baru minimal 6 karakter.";
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        // Update password
        $hashed = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
        $_SESSION['success'] = "Password berhasil diubah!";
        header('Location: profil.php');
        exit;
    }

    if ($error) {
        $_SESSION['error'] = $error;
    }
}
?>

<?php include 'includes/header.php'; ?>

<main class="content">
    <!-- Pesan -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="text-success" style="background: hsl(150, 45%, 18%); padding: 12px; border-radius: 6px; margin-bottom: 16px;">
            ✅ <?= $_SESSION['success'] ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="text-danger" style="background: hsl(360, 60%, 15%); padding: 12px; border-radius: 6px; margin-bottom: 16px;">
            ❌ <?= $_SESSION['error'] ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Informasi Profil -->
    <div class="card" style="padding: 16px; margin-bottom: 30px;">
        <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">Informasi Akun</h3>
        <table style="width: 100%; border-collapse: collapse; color: white;">
            <tr>
                <td style="padding: 8px 0; width: 150px;"><strong>Nama</strong></td>
                <td style="padding: 8px 0;"><?= e($user['nama']) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 0;"><strong>Username</strong></td>
                <td style="padding: 8px 0;"><?= e($user['username']) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 0;"><strong>Role</strong></td>
                <td style="padding: 8px 0;"><?= e(ucfirst($user['role'])) ?></td>
            </tr>
        </table>
    </div>

    <!-- Ganti Password -->
    <div class="card" style="padding: 16px;">
        <h3 style="margin: 0 0 16px; color: hsl(0, 0%, 90%);">Ganti Password</h3>
        <form method="POST">
            <input type="hidden" name="ganti_password" value="1">
            <div class="form-group">
                <label>Password Lama</label>
                <input type="password" name="password_lama" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="password_baru" class="form-control" minlength="6" required>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="konfirmasi_password" class="form-control" required>
            </div>
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Ubah Password</button>
            </div>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>