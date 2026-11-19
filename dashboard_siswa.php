<?php
require_once __DIR__ . '/../auth.php';
require_role('siswa');
require_once __DIR__ . '/../koneksi.php';

$username = $_SESSION['user']['username'] ?? $_SESSION['username'] ?? 'Siswa';
$userId = (int) ($_SESSION['user']['id'] ?? 0);
$profileStatement = mysqli_prepare($koneksi, 'SELECT nama, nis, kelas FROM siswa WHERE user_id = ? LIMIT 1');
if (!$profileStatement) {
    die('Gagal memuat profil siswa: ' . htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
}
mysqli_stmt_bind_param($profileStatement, 'i', $userId);
if (!mysqli_stmt_execute($profileStatement)) {
    die('Gagal memuat profil siswa: ' . htmlspecialchars(mysqli_stmt_error($profileStatement), ENT_QUOTES, 'UTF-8'));
}
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($profileStatement));
mysqli_stmt_close($profileStatement);
$displayName = $profile['nama'] ?? $username;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1339">
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    <main class="container dashboard-page">
        <section class="dashboard-hero dashboard-hero-student">
            <div class="dashboard-hero-copy">
                <span class="dashboard-eyebrow">RUANG SISWA</span>
                <h1>Terus melangkah, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>.</h1>
                <p>Setiap hal yang kamu pelajari hari ini adalah langkah kecil menuju cita-cita besar.</p>
                <span class="dashboard-welcome-chip"><span aria-hidden="true">✦</span> Belajar. Bertumbuh. Berprestasi.</span>
            </div>
            <div class="dashboard-hero-art" aria-hidden="true">
                <div class="hero-orbit hero-orbit-one"></div><div class="hero-orbit hero-orbit-two"></div>
                <div class="student-art-card"><div class="student-sun"></div><div class="student-hill student-hill-back"></div><div class="student-hill student-hill-front"></div><span>HARI INI ADALAH AWAL</span></div>
            </div>
        </section>
        <section class="dashboard-section">
            <div class="dashboard-section-heading">
                <div><span class="dashboard-eyebrow">PROFIL ANDA</span><h2>Informasi akun</h2></div>
                <span class="dashboard-role-pill role-pill-student"><span aria-hidden="true"></span> Siswa</span>
            </div>
            <div class="dashboard-profile-card">
                <span class="profile-avatar profile-avatar-student" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr($displayName, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                <div class="dashboard-profile-copy">
                    <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span>@<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="dashboard-profile-detail"><small>NIS</small><strong><?= htmlspecialchars($profile['nis'] ?? 'Belum terhubung', ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="dashboard-profile-detail"><small>Kelas</small><strong><?= htmlspecialchars($profile['kelas'] ?? 'Belum terhubung', ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <a href="<?= htmlspecialchars(app_url('logout.php'), ENT_QUOTES, 'UTF-8'); ?>" class="dashboard-logout-link">Keluar dari akun <span aria-hidden="true">→</span></a>
            </div>
        </section>
        <section class="dashboard-section">
            <a class="dashboard-action-card" href="<?= htmlspecialchars(app_url('Nilai/nilai_siswa.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <span class="dashboard-action-icon action-icon-sage" aria-hidden="true">↗</span>
                <span><strong>Melihat Nilai Saya</strong><small>Lihat detail nilai dan status ketuntasan setiap mata pelajaran</small></span>
                <span class="dashboard-action-arrow" aria-hidden="true">↗</span>
            </a>
        </section>
        <?php if (!$profile): ?>
            <div class="dashboard-notice">Profil siswa belum terhubung dengan akun ini. Hubungi administrator untuk memperbarui data profil.</div>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>
