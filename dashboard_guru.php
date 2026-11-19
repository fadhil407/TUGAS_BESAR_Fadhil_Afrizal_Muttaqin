<?php
require_once __DIR__ . '/../auth.php';
require_role('guru');
require_once __DIR__ . '/../koneksi.php';

$username = $_SESSION['user']['username'] ?? $_SESSION['username'] ?? 'Guru';
$userId = (int) ($_SESSION['user']['id'] ?? 0);
$profileStatement = mysqli_prepare($koneksi, 'SELECT nama, nip FROM guru WHERE user_id = ? LIMIT 1');
if (!$profileStatement) {
    die('Gagal memuat profil guru: ' . htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
}
mysqli_stmt_bind_param($profileStatement, 'i', $userId);
if (!mysqli_stmt_execute($profileStatement)) {
    die('Gagal memuat profil guru: ' . htmlspecialchars(mysqli_stmt_error($profileStatement), ENT_QUOTES, 'UTF-8'));
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
    <title>Dashboard Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1339">
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    <main class="container dashboard-page">
        <section class="dashboard-hero dashboard-hero-teacher">
            <div class="dashboard-hero-copy">
                <span class="dashboard-eyebrow">RUANG GURU</span>
                <h1>Halo, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>.</h1>
                <p>Selamat datang kembali. Semoga hari ini menjadi hari yang baik untuk mengajar dan menginspirasi.</p>
                <span class="dashboard-welcome-chip"><span aria-hidden="true">✦</span> Senang melihat Anda kembali</span>
            </div>
            <div class="dashboard-hero-art" aria-hidden="true">
                <div class="hero-orbit hero-orbit-one"></div><div class="hero-orbit hero-orbit-two"></div>
                <div class="teacher-art-card"><span>RUANG GURU</span><strong>Menginspirasi<br>setiap hari.</strong><div class="teacher-book"><i></i><i></i><i></i></div></div>
            </div>
        </section>
        <section class="dashboard-section teacher-assignment-cta">
            <a class="dashboard-action-card" href="<?= htmlspecialchars(app_url('mengajar/kelola_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <span class="dashboard-action-icon action-icon-sage" aria-hidden="true">▤</span>
                <span><strong>Penugasan mengajar saya</strong><small>Lihat mata pelajaran, kelas, dan periode yang ditugaskan</small></span>
                <span class="dashboard-action-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="dashboard-action-card mt-3" href="<?= htmlspecialchars(app_url('Nilai/kelola_nilai.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <span class="dashboard-action-icon action-icon-caramel" aria-hidden="true">▤</span>
                <span><strong>Melihat Nilai Siswa</strong><small>Kelola nilai untuk siswa dalam penugasan Anda</small></span>
                <span class="dashboard-action-arrow" aria-hidden="true">↗</span>
            </a>
            <a class="dashboard-action-card mt-3" href="<?= htmlspecialchars(app_url('Nilai/rekap_nilai.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <span class="dashboard-action-icon action-icon-sage" aria-hidden="true">↗</span>
                <span><strong>Rekap Nilai Otomatis</strong><small>Pantau rata-rata dan ketuntasan nilai siswa</small></span>
                <span class="dashboard-action-arrow" aria-hidden="true">↗</span>
            </a>
        </section>
        <section class="dashboard-section">
            <div class="dashboard-section-heading">
                <div><span class="dashboard-eyebrow">PROFIL ANDA</span><h2>Informasi akun</h2></div>
                <span class="dashboard-role-pill role-pill-teacher"><span aria-hidden="true"></span> Guru</span>
            </div>
            <div class="dashboard-profile-card">
                <span class="profile-avatar profile-avatar-teacher" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr($displayName, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                <div class="dashboard-profile-copy">
                    <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span>@<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="dashboard-profile-detail"><small>Nomor induk guru</small><strong><?= htmlspecialchars($profile['nip'] ?? 'Belum terhubung', ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <a href="<?= htmlspecialchars(app_url('logout.php'), ENT_QUOTES, 'UTF-8'); ?>" class="dashboard-logout-link">Keluar dari akun <span aria-hidden="true">→</span></a>
            </div>
        </section>
        <?php if (!$profile): ?>
            <div class="dashboard-notice">Profil guru belum terhubung dengan akun ini. Hubungi administrator untuk memperbarui data profil.</div>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>
