<?php
require_once __DIR__ . '/../auth.php';
require_login();
$currentRole = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');
if (!in_array($currentRole, ['admin', 'guru'], true)) {
    redirect_to_role_dashboard();
}
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/mengajar_support.php';

$options = mengajar_fetch_options($koneksi);
$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$classFilter = isset($_GET['kelas']) && is_string($_GET['kelas']) ? trim($_GET['kelas']) : '';
$validClasses = array_column($options['kelas'], 'nama');
if (!in_array($classFilter, $validClasses, true)) {
    $classFilter = '';
}
$teacherFilter = isset($_GET['guru_id']) && is_scalar($_GET['guru_id']) ? filter_var($_GET['guru_id'], FILTER_VALIDATE_INT) : 0;
$validTeacherIds = array_map('intval', array_column($options['guru'], 'id'));
if (!$teacherFilter || !in_array((int) $teacherFilter, $validTeacherIds, true) || $currentRole !== 'admin') {
    $teacherFilter = 0;
}

$limit = 10;
$page = isset($_GET['page']) && is_scalar($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$teacherGate = $currentRole === 'guru' ? 1 : 0;
$teacherUserId = (int) ($_SESSION['user']['id'] ?? 0);
$pattern = '%' . $search . '%';
$groupedSql = "
    SELECT MIN(m.id) AS id, g.id AS guru_id, g.nama AS nama_guru, g.nip,
           mp.id AS mapel_id, mp.nama_mapel, mp.kode_mapel,
           TRIM(s.kelas) AS kelas, COUNT(DISTINCT s.id) AS jumlah_siswa
    FROM mengajar m
    INNER JOIN siswa s ON s.id = m.siswa_id
    INNER JOIN mapel mp ON mp.id = m.mapel_id
    INNER JOIN guru g ON g.id = mp.guru_id
    INNER JOIN users u ON u.id = g.user_id AND u.role = 'guru'
    WHERE (? = 0 OR g.user_id = ?)
      AND (? = 0 OR g.id = ?)
      AND (? = '' OR TRIM(s.kelas) = ?)
    GROUP BY mp.id, g.id, TRIM(s.kelas)
    HAVING (? = '' OR nama_guru LIKE ? OR nama_mapel LIKE ? OR kode_mapel LIKE ? OR kelas LIKE ?)
";
$bindValues = [
    $teacherGate, $teacherUserId, $teacherFilter, $teacherFilter,
    $classFilter, $classFilter, $search, $pattern, $pattern, $pattern, $pattern,
];
$bindTypes = 'iiiisssssss';

$countStatement = mengajar_prepare(
    $koneksi,
    'SELECT COUNT(*) AS total FROM (' . $groupedSql . ') AS grouped_assignments',
    'Gagal menyiapkan daftar penugasan'
);
mysqli_stmt_bind_param($countStatement, $bindTypes, ...$bindValues);
if (!mysqli_stmt_execute($countStatement)) {
    error_log('Assignment count failed: ' . mysqli_stmt_error($countStatement));
    http_response_code(500);
    die('Gagal menghitung penugasan.');
}
$totalRows = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStatement))['total'];
mysqli_stmt_close($countStatement);
$totalPages = max(1, (int) ceil($totalRows / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

$listStatement = mengajar_prepare(
    $koneksi,
    $groupedSql . ' ORDER BY kelas ASC, nama_guru ASC, nama_mapel ASC LIMIT ? OFFSET ?',
    'Gagal menyiapkan daftar penugasan'
);
$listTypes = $bindTypes . 'ii';
$listValues = array_merge($bindValues, [$limit, $offset]);
mysqli_stmt_bind_param($listStatement, $listTypes, ...$listValues);
if (!mysqli_stmt_execute($listStatement)) {
    error_log('Assignment listing failed: ' . mysqli_stmt_error($listStatement));
    http_response_code(500);
    die('Gagal memuat penugasan.');
}
$assignments = mysqli_fetch_all(mysqli_stmt_get_result($listStatement), MYSQLI_ASSOC);
mysqli_stmt_close($listStatement);

$successMessage = $_SESSION['mengajar_success'] ?? '';
$errorMessage = $_SESSION['mengajar_error'] ?? '';
unset($_SESSION['mengajar_success'], $_SESSION['mengajar_error'], $_SESSION['mengajar_values']);
$csrfToken = mengajar_csrf_token();
$queryWithoutPage = $_GET;
unset($queryWithoutPage['page']);
$paginationBase = http_build_query($queryWithoutPage);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Guru Mengajar - SIPN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>
<main class="container assignment-page">
    <header class="assignment-heading">
        <div>
            <span class="assignment-eyebrow"><?= $currentRole === 'admin' ? 'ADMINISTRASI AKADEMIK' : 'RUANG GURU'; ?></span>
            <h1><?= $currentRole === 'admin' ? 'Daftar Guru Mengajar' : 'Daftar Mengajar Saya'; ?></h1>
            <p><?= $currentRole === 'admin' ? 'Kelola penugasan berdasarkan guru, kelas, dan mata pelajaran.' : 'Mata pelajaran dan kelas yang ditugaskan kepada Anda.'; ?></p>
        </div>
        <?php if ($currentRole === 'admin'): ?>
            <a class="btn btn-primary assignment-add-button" href="<?= htmlspecialchars(app_url('mengajar/form_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>"><span aria-hidden="true">＋</span> Tambah penugasan</a>
        <?php endif; ?>
    </header>

    <?php if ($successMessage !== ''): ?><div class="alert alert-success" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($errorMessage !== ''): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <div class="alert alert-info" role="status">Setiap baris menggabungkan seluruh siswa pada kelas yang sama untuk guru dan mata pelajaran terkait.</div>

    <section class="assignment-card assignment-list-card">
        <div class="assignment-card-heading">
            <div><h2>Daftar guru mengajar</h2><p><?= $totalRows; ?> penugasan ditemukan.</p></div>
        </div>
        <form class="assignment-filters" method="GET" action="<?= htmlspecialchars(app_url('mengajar/kelola_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="assignment-search">
                <span class="visually-hidden">Cari penugasan</span>
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari guru, kelas, atau mata pelajaran...">
            </label>
            <select name="kelas" class="form-select" aria-label="Filter kelas">
                <option value="">Semua kelas</option>
                <?php foreach ($options['kelas'] as $class): ?>
                    <option value="<?= htmlspecialchars($class['nama'], ENT_QUOTES, 'UTF-8'); ?>" <?= $classFilter === $class['nama'] ? 'selected' : ''; ?>><?= htmlspecialchars($class['nama'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($currentRole === 'admin'): ?>
                <select name="guru_id" class="form-select" aria-label="Filter guru">
                    <option value="">Semua guru</option>
                    <?php foreach ($options['guru'] as $teacher): ?>
                        <option value="<?= (int) $teacher['id']; ?>" <?= (int) $teacherFilter === (int) $teacher['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($teacher['nama'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <div class="assignment-filter-actions">
                <button class="btn btn-primary" type="submit">Cari</button>
                <a class="btn btn-light" href="<?= htmlspecialchars(app_url('mengajar/kelola_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>">Reset</a>
            </div>
        </form>

        <div class="table-responsive assignment-table-wrap">
            <table class="table table-striped align-middle assignment-table">
                <thead><tr><th>No</th><th>Nama Guru</th><th>Kelas</th><th>Nama Mata Pelajaran</th><th>Kode Mata Pelajaran</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if ($assignments): ?>
                    <?php foreach ($assignments as $index => $assignment): ?>
                        <tr>
                            <td><span class="assignment-row-number"><?= $offset + $index + 1; ?></span></td>
                            <td><div class="assignment-teacher-name"><strong><?= htmlspecialchars($assignment['nama_guru'], ENT_QUOTES, 'UTF-8'); ?></strong><small>NIP <?= htmlspecialchars($assignment['nip'], ENT_QUOTES, 'UTF-8'); ?></small></div></td>
                            <td><span class="assignment-class-pill"><?= htmlspecialchars($assignment['kelas'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><strong class="assignment-subject"><a class="assignment-subject-link" href="<?= htmlspecialchars(app_url('mengajar/detail_mengajar.php?id=' . (int) $assignment['id']), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($assignment['nama_mapel'], ENT_QUOTES, 'UTF-8'); ?></a></strong></td>
                            <td><span class="assignment-subject-code"><?= htmlspecialchars($assignment['kode_mapel'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td>
                                <div class="assignment-row-actions">
                                    <?php if ($currentRole === 'admin'): ?>
                                        <a class="btn btn-sm btn-warning" href="<?= htmlspecialchars(app_url('Edit/edit_mengajar.php?id=' . (int) $assignment['id']), ENT_QUOTES, 'UTF-8'); ?>">Edit</a>
                                        <form action="<?= htmlspecialchars(app_url('action/aksi_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" onsubmit="return confirm('Yakin ingin menghapus penugasan guru, kelas, dan mata pelajaran ini?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $assignment['id']; ?>">
                                            <button class="btn btn-sm btn-danger" type="submit">Hapus</button>
                                        </form>
                                    <?php else: ?>
                                        <a class="assignment-detail-link" href="<?= htmlspecialchars(app_url('mengajar/detail_mengajar.php?id=' . (int) $assignment['id']), ENT_QUOTES, 'UTF-8'); ?>">Lihat detail</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6"><div class="assignment-empty"><span aria-hidden="true">▤</span><strong>Data guru mengajar belum ditemukan</strong><p><?= $currentRole === 'admin' ? 'Coba ubah pencarian atau tambahkan penugasan mengajar.' : 'Belum ada penugasan untuk akun guru ini.'; ?></p><?php if ($currentRole === 'admin'): ?><a class="btn btn-primary" href="<?= htmlspecialchars(app_url('mengajar/form_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>">Tambah penugasan</a><?php endif; ?></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="assignment-pagination">
                <span>Menampilkan halaman <?= $page; ?> dari <?= $totalPages; ?></span>
                <nav aria-label="Navigasi halaman penugasan"><ul class="pagination mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?<?= htmlspecialchars($paginationBase . ($paginationBase !== '' ? '&' : '') . 'page=' . max(1, $page - 1), ENT_QUOTES, 'UTF-8'); ?>">Sebelumnya</a></li>
                    <li class="page-item active"><span class="page-link"><?= $page; ?></span></li>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>"><a class="page-link" href="?<?= htmlspecialchars($paginationBase . ($paginationBase !== '' ? '&' : '') . 'page=' . min($totalPages, $page + 1), ENT_QUOTES, 'UTF-8'); ?>">Selanjutnya</a></li>
                </ul></nav>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>
