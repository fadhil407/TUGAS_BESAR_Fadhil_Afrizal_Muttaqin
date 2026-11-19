<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';

$username = $_SESSION['user']['username'] ?? $_SESSION['username'] ?? 'Admin';
$countsQuery = mysqli_query(
    $koneksi,
    "SELECT
        SUM(role = 'admin') AS admin_count,
        SUM(role = 'guru') AS guru_count,
        SUM(role = 'siswa') AS siswa_count,
        COUNT(*) AS user_count
     FROM users"
);
if (!$countsQuery) {
    die('Gagal memuat ringkasan pengguna: ' . htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
}
$counts = mysqli_fetch_assoc($countsQuery);
$mapelQuery = mysqli_query($koneksi, 'SELECT COUNT(*) AS total FROM mapel');
if (!$mapelQuery) {
    die('Gagal memuat jumlah mata pelajaran: ' . htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
}
$mapelCount = (int) mysqli_fetch_assoc($mapelQuery)['total'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    <main class="container dashboard-page">
        <section class="dashboard-hero dashboard-hero-admin">
            <div class="dashboard-hero-copy">
                <span class="dashboard-eyebrow">DASHBOARD ADMINISTRATOR</span>
                <h1>Selamat datang, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>.</h1>
                <p>Pantau data sekolah dan kelola kebutuhan administrasi dari satu tempat.</p>
                <a class="dashboard-hero-link" href="<?= htmlspecialchars(app_url('tabel_user/daftar_users.php'), ENT_QUOTES, 'UTF-8'); ?>">Kelola pengguna <span aria-hidden="true">→</span></a>
            </div>
            <div class="dashboard-hero-art" aria-hidden="true">
                <div class="hero-orbit hero-orbit-one"></div>
                <div class="hero-orbit hero-orbit-two"></div>
                <div class="hero-art-card"><span>SEKOLAH</span><strong>Ruang<br>bertumbuh.</strong><i></i></div>
            </div>
        </section>

        <section class="dashboard-section">
            <div class="dashboard-section-heading">
                <div><span class="dashboard-eyebrow">RINGKASAN</span><h2>Data sekolah</h2></div>
                <span class="dashboard-updated"><span aria-hidden="true"></span> Data saat ini</span>
            </div>
            <div class="dashboard-stat-grid">
                <article class="dashboard-stat dashboard-stat-all"><span class="dashboard-stat-icon" aria-hidden="true">◎</span><span>Total pengguna</span><strong><?= (int) $counts['user_count']; ?></strong><small>Semua akun terdaftar</small></article>
                <article class="dashboard-stat dashboard-stat-teacher"><span class="dashboard-stat-icon" aria-hidden="true">▤</span><span>Guru</span><strong><?= (int) $counts['guru_count']; ?></strong><small>Akun guru</small></article>
                <article class="dashboard-stat dashboard-stat-student"><span class="dashboard-stat-icon" aria-hidden="true">◇</span><span>Siswa</span><strong><?= (int) $counts['siswa_count']; ?></strong><small>Akun siswa</small></article>
                <article class="dashboard-stat dashboard-stat-subject"><span class="dashboard-stat-icon" aria-hidden="true">▧</span><span>Mata pelajaran</span><strong><?= $mapelCount; ?></strong><small>Data mata pelajaran</small></article>
            </div>
        </section>

        <section class="dashboard-section dashboard-quick-section">
            <div class="dashboard-section-heading">
                <div><span class="dashboard-eyebrow">AKSES CEPAT</span><h2>Mulai dari sini</h2></div>
            </div>
            <div class="dashboard-action-grid">
                <a class="dashboard-action-card" href="<?= htmlspecialchars(app_url('tabel_user/daftar_users.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="dashboard-action-icon action-icon-caramel" aria-hidden="true">♙</span>
                    <span><strong>Kelola pengguna</strong><small>Lihat dan atur akun sekolah</small></span>
                    <span class="dashboard-action-arrow" aria-hidden="true">↗</span>
                </a>
                <a class="dashboard-action-card" href="<?= htmlspecialchars(app_url('tabel_user/daftar_mapel.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="dashboard-action-icon action-icon-sage" aria-hidden="true">▤</span>
                    <span><strong>Mata pelajaran</strong><small>Kelola daftar mata pelajaran</small></span>
                    <span class="dashboard-action-arrow" aria-hidden="true">↗</span>
                </a>
                <a class="dashboard-action-card" href="<?= htmlspecialchars(app_url('Tambah_user/tambah_users.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="dashboard-action-icon action-icon-amber" aria-hidden="true">＋</span>
                    <span><strong>Buat akun baru</strong><small>Tambahkan admin, guru, atau siswa</small></span>
                    <span class="dashboard-action-arrow" aria-hidden="true">↗</span>
                </a>
                <a class="dashboard-action-card" href="<?= htmlspecialchars(app_url('mengajar/kelola_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="dashboard-action-icon action-icon-caramel" aria-hidden="true">▤</span>
                    <span><strong>Kelola mengajar</strong><small>Atur penugasan guru dan kelas</small></span>
                    <span class="dashboard-action-arrow" aria-hidden="true">↗</span>
                </a>
            </div>
        </section>
    </main>
    <?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>
