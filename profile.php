<?php
require_once __DIR__ . '/../auth.php';
require_login();
require_once __DIR__ . '/../koneksi.php';

$userId = (int) ($_SESSION['user']['id'] ?? 0);
$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');

$userStatement = mysqli_prepare($koneksi, 'SELECT id, username, role, created_at FROM users WHERE id = ? LIMIT 1');
if (!$userStatement) {
    die('Gagal memuat akun: ' . htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
}
mysqli_stmt_bind_param($userStatement, 'i', $userId);
if (!mysqli_stmt_execute($userStatement)) {
    die('Gagal memuat akun: ' . htmlspecialchars(mysqli_stmt_error($userStatement), ENT_QUOTES, 'UTF-8'));
}
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($userStatement));
mysqli_stmt_close($userStatement);

if (!$user || $user['role'] !== $role) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . app_url('login.php'));
    exit();
}

$profile = null;
$profileStatement = null;
if ($role === 'guru') {
    $profileStatement = mysqli_prepare($koneksi, 'SELECT nama, nip, jenis_kelamin FROM guru WHERE user_id = ? LIMIT 1');
} elseif ($role === 'siswa') {
    $profileStatement = mysqli_prepare($koneksi, 'SELECT nama, nis, kelas, jenis_kelamin FROM siswa WHERE user_id = ? LIMIT 1');
}

if ($profileStatement) {
    mysqli_stmt_bind_param($profileStatement, 'i', $userId);
    if (!mysqli_stmt_execute($profileStatement)) {
        die('Gagal memuat profil: ' . htmlspecialchars(mysqli_stmt_error($profileStatement), ENT_QUOTES, 'UTF-8'));
    }
    $profile = mysqli_fetch_assoc(mysqli_stmt_get_result($profileStatement));
    mysqli_stmt_close($profileStatement);
} elseif ($role !== 'admin') {
    die('Gagal menyiapkan data profil: ' . htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
}

$roleLabels = ['admin' => 'Administrator', 'guru' => 'Guru', 'siswa' => 'Siswa'];
$displayName = $profile['nama'] ?? $user['username'];
$initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($displayName, 0, 1));
$monthNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$createdAt = strtotime($user['created_at']);
$createdAtLabel = date('j', $createdAt) . ' ' . $monthNames[(int) date('n', $createdAt)] . ' ' . date('Y', $createdAt);
$successMessage = $_SESSION['profile_success'] ?? '';
$errorMessage = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Saya - Ruang Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    <main class="container profile-page">
        <div class="profile-breadcrumb"><a href="<?= htmlspecialchars(role_dashboard_url($role), ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a><span aria-hidden="true">/</span><span>Profil saya</span></div>

        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success profile-flash" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger profile-flash" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="profile-hero">
            <div class="profile-hero-main">
                <span class="profile-hero-avatar"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?></span>
                <div>
                    <span class="profile-eyebrow">PROFIL PENGGUNA</span>
                    <h1><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p>@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
            <div class="profile-hero-actions">
                <span class="profile-role-badge profile-role-<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>"><span aria-hidden="true"></span><?= htmlspecialchars($roleLabels[$role] ?? ucfirst($role), ENT_QUOTES, 'UTF-8'); ?></span>
                <a class="btn btn-primary" href="<?= htmlspecialchars(app_url('Profile/profile_edit.php'), ENT_QUOTES, 'UTF-8'); ?>">Edit profil <span aria-hidden="true">↗</span></a>
            </div>
            <div class="profile-hero-decoration" aria-hidden="true"></div>
        </section>

        <div class="profile-content-grid">
            <section class="profile-info-card">
                <header class="profile-card-heading">
                    <span class="profile-card-icon" aria-hidden="true">✦</span>
                    <div><h2>Informasi pribadi</h2><p>Informasi yang terhubung ke akun sekolah Anda.</p></div>
                </header>
                <?php if ($role !== 'admin' && !$profile): ?>
                    <div class="profile-missing-notice">Data profil belum terhubung ke akun ini. Hubungi administrator untuk melengkapi profil.</div>
                <?php else: ?>
                    <dl class="profile-details">
                        <div class="profile-detail-row"><dt>Nama lengkap</dt><dd><?= htmlspecialchars($profile['nama'] ?? $user['username'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div class="profile-detail-row"><dt>Username</dt><dd><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <?php if ($role === 'guru'): ?>
                            <div class="profile-detail-row"><dt>Nomor induk guru (NIP)</dt><dd><?= htmlspecialchars($profile['nip'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></dd></div>
                            <div class="profile-detail-row"><dt>Jenis kelamin</dt><dd><?= ($profile['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : 'Perempuan'; ?></dd></div>
                        <?php elseif ($role === 'siswa'): ?>
                            <div class="profile-detail-row"><dt>Nomor induk siswa (NIS)</dt><dd><?= htmlspecialchars($profile['nis'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></dd></div>
                            <div class="profile-detail-row"><dt>Kelas</dt><dd><?= htmlspecialchars($profile['kelas'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></dd></div>
                            <div class="profile-detail-row"><dt>Jenis kelamin</dt><dd><?= ($profile['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : 'Perempuan'; ?></dd></div>
                        <?php else: ?>
                            <div class="profile-detail-row"><dt>Jenis akun</dt><dd>Administrator sekolah</dd></div>
                        <?php endif; ?>
                        <div class="profile-detail-row"><dt>Bergabung sejak</dt><dd><?= htmlspecialchars($createdAtLabel, ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    </dl>
                <?php endif; ?>
            </section>

            <aside class="profile-security-card">
                <span class="security-icon" aria-hidden="true">⌑</span>
                <span class="profile-eyebrow">KEAMANAN AKUN</span>
                <h2>Jaga akun tetap aman</h2>
                <p>Gunakan password yang kuat dan jangan bagikan informasi masuk akun Anda kepada orang lain.</p>
                <a href="<?= htmlspecialchars(app_url('Profile/profile_edit.php#ubah-password'), ENT_QUOTES, 'UTF-8'); ?>">Ubah password <span aria-hidden="true">→</span></a>
            </aside>
        </div>
    </main>
    <?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>
