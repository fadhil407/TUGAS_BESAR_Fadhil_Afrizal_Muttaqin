<?php
require_once __DIR__ . '/../auth.php';
require_login();
$currentRole = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');
if (!in_array($currentRole, ['admin', 'guru'], true)) {
    redirect_to_role_dashboard();
}
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/mengajar_support.php';

$id = isset($_GET['id']) && is_scalar($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : false;
if (!$id || $id < 1) {
    header('Location: ' . app_url('mengajar/kelola_mengajar.php'));
    exit();
}
$sql = "
    SELECT MIN(m.id) AS id, mp.id AS mapel_id, g.id AS guru_id,
           g.nama AS nama_guru, g.nip, u.username,
           mp.nama_mapel, mp.kode_mapel, TRIM(s.kelas) AS kelas,
           COUNT(DISTINCT s.id) AS jumlah_siswa
    FROM mengajar selected
    INNER JOIN siswa selected_student ON selected_student.id = selected.siswa_id
    INNER JOIN mengajar m ON m.mapel_id = selected.mapel_id
    INNER JOIN siswa s ON s.id = m.siswa_id AND TRIM(s.kelas) = TRIM(selected_student.kelas)
    INNER JOIN mapel mp ON mp.id = m.mapel_id
    INNER JOIN guru g ON g.id = mp.guru_id
    INNER JOIN users u ON u.id = g.user_id AND u.role = 'guru'
    WHERE selected.id = ?
    GROUP BY mp.id, g.id, g.user_id, TRIM(s.kelas)
    HAVING MIN(m.id) = ?
";
if ($currentRole === 'guru') {
    $sql .= ' AND g.user_id = ?';
}
$statement = mengajar_prepare($koneksi, $sql, 'Gagal menyiapkan detail penugasan');
if ($currentRole === 'guru') {
    $teacherUserId = (int) ($_SESSION['user']['id'] ?? 0);
    mysqli_stmt_bind_param($statement, 'iii', $id, $id, $teacherUserId);
} else {
    mysqli_stmt_bind_param($statement, 'ii', $id, $id);
}
if (!mysqli_stmt_execute($statement)) {
    error_log('Assignment detail query failed: ' . mysqli_stmt_error($statement));
    http_response_code(500);
    die('Gagal memuat detail penugasan.');
}
$assignment = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));
mysqli_stmt_close($statement);
if (!$assignment) {
    $_SESSION['mengajar_error'] = 'Penugasan tidak ditemukan atau Anda tidak memiliki akses.';
    header('Location: ' . app_url('mengajar/kelola_mengajar.php'));
    exit();
}

$csrfToken = mengajar_csrf_token();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Guru Mengajar - SIPN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>
<main class="container assignment-detail-page">
    <div class="assignment-breadcrumb"><a href="<?= htmlspecialchars(app_url('mengajar/kelola_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>">Daftar guru mengajar</a><span aria-hidden="true">/</span><span>Detail penugasan</span></div>
    <section class="assignment-detail-hero">
        <span class="assignment-detail-icon" aria-hidden="true">▤</span>
        <div><span class="assignment-eyebrow">DETAIL PENUGASAN</span><h1><?= htmlspecialchars($assignment['nama_mapel'], ENT_QUOTES, 'UTF-8'); ?></h1><p><?= htmlspecialchars($assignment['nama_guru'], ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">·</span> Kelas <?= htmlspecialchars($assignment['kelas'], ENT_QUOTES, 'UTF-8'); ?></p></div>
    </section>

    <section class="assignment-detail-card">
        <header class="assignment-form-card-heading"><span>✓</span><div><h2>Informasi guru mengajar</h2><p>Ringkasan penugasan untuk satu kelas.</p></div></header>
        <dl class="assignment-detail-grid">
            <div><dt>Nama guru</dt><dd><?= htmlspecialchars($assignment['nama_guru'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>NIP</dt><dd><?= htmlspecialchars($assignment['nip'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Akun guru</dt><dd>@<?= htmlspecialchars($assignment['username'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Kelas</dt><dd><span class="assignment-class-pill"><?= htmlspecialchars($assignment['kelas'], ENT_QUOTES, 'UTF-8'); ?></span></dd></div>
            <div><dt>Nama mata pelajaran</dt><dd><?= htmlspecialchars($assignment['nama_mapel'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Kode mata pelajaran</dt><dd><?= htmlspecialchars($assignment['kode_mapel'] ?: 'Belum diisi', ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Jumlah siswa</dt><dd><?= (int) $assignment['jumlah_siswa']; ?> siswa</dd></div>
        </dl>
        <div class="assignment-detail-footer">
            <a class="btn btn-light" href="<?= htmlspecialchars(app_url('mengajar/kelola_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>">Kembali ke daftar</a>
            <?php if ($currentRole === 'admin'): ?>
                <div class="assignment-detail-actions">
                    <a class="btn btn-primary" href="<?= htmlspecialchars(app_url('Edit/edit_mengajar.php?id=' . (int) $assignment['id']), ENT_QUOTES, 'UTF-8'); ?>">Edit penugasan</a>
                    <form action="<?= htmlspecialchars(app_url('action/aksi_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" onsubmit="return confirm('Yakin ingin menghapus penugasan guru, kelas, dan mata pelajaran ini?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $assignment['id']; ?>">
                        <button class="btn btn-danger" type="submit">Hapus penugasan</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>
