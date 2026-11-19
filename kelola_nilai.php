<?php
require_once __DIR__ . '/../auth.php';
require_role('guru');
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/nilai_support.php';

$teacherUserId = (int) ($_SESSION['user']['id'] ?? 0);
$mode = $_GET['mode'] ?? 'list';
if (!is_string($mode) || !in_array($mode, ['list', 'create', 'edit', 'detail'], true)) {
    $mode = 'list';
}
$gradeId = isset($_GET['id']) && is_scalar($_GET['id'])
    ? filter_var($_GET['id'], FILTER_VALIDATE_INT)
    : false;

$grade = null;
if (in_array($mode, ['edit', 'detail'], true)) {
    if (!$gradeId || $gradeId < 1) {
        $_SESSION['nilai_error'] = 'Data nilai yang dipilih tidak valid.';
        nilai_redirect();
    }
    $grade = nilai_find_grade($koneksi, $gradeId, $teacherUserId);
    if (!$grade) {
        $_SESSION['nilai_error'] = 'Nilai tidak ditemukan atau Anda tidak berwenang melihatnya.';
        nilai_redirect();
    }
}

$assignments = [];
$classes = [];
$subjects = [];
$formValues = [];
$errorMessage = $_SESSION['nilai_error'] ?? '';
$successMessage = $_SESSION['nilai_success'] ?? '';
unset($_SESSION['nilai_error'], $_SESSION['nilai_success']);

if (in_array($mode, ['create', 'edit'], true)) {
    $assignments = nilai_assignment_options($koneksi, $teacherUserId);
    $storedValues = $_SESSION['nilai_form_values'] ?? [];
    $storedValues = is_array($storedValues) ? $storedValues : [];
    foreach (['assignment', 'nilai_kkm', 'nilai_pengetahuan', 'nilai_keterampilan', 'keterangan'] as $key) {
        if (isset($storedValues[$key]) && is_scalar($storedValues[$key])) {
            $formValues[$key] = (string) $storedValues[$key];
        }
    }
    unset($_SESSION['nilai_form_values']);
    $assignmentPairs = [];
    foreach ($assignments as $assignment) {
        $assignmentPairs[$assignment['siswa_id'] . ':' . $assignment['mapel_id']] = true;
    }
    if (
        $mode === 'create'
        && !$formValues
        && isset($_GET['assignment'])
        && is_string($_GET['assignment'])
        && isset($assignmentPairs[$_GET['assignment']])
    ) {
        $formValues['assignment'] = $_GET['assignment'];
    }
    if ($grade && !$formValues) {
        $formValues = [
            'assignment' => $grade['siswa_id'] . ':' . $grade['mapel_id'],
            'nilai_kkm' => $grade['nilai_kkm'],
            'nilai_pengetahuan' => $grade['nilai_pengetahuan'],
            'nilai_keterampilan' => $grade['nilai_keterampilan'],
            'keterangan' => $grade['keterangan'],
        ];
    }
}

if ($mode === 'list') {
    $filterSql = "
        FROM (
            SELECT DISTINCT mg.siswa_id, mg.mapel_id
            FROM mengajar mg
            INNER JOIN mapel assigned_mapel ON assigned_mapel.id = mg.mapel_id
            INNER JOIN guru assigned_guru ON assigned_guru.id = assigned_mapel.guru_id
            INNER JOIN users assigned_user ON assigned_user.id = assigned_guru.user_id AND assigned_user.role = 'guru'
            WHERE assigned_guru.user_id = ?
        ) assigned
        INNER JOIN siswa s ON s.id = assigned.siswa_id
        INNER JOIN mapel mp ON mp.id = assigned.mapel_id
        LEFT JOIN nilai_rapor nr ON nr.siswa_id = assigned.siswa_id AND nr.mapel_id = assigned.mapel_id
        WHERE 1 = 1
          AND (? = '' OR s.nama LIKE ? OR s.nis LIKE ?)
          AND (? = '' OR TRIM(s.kelas) = ?)
          AND (? = 0 OR mp.id = ?)
    ";

    $search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
    $classFilter = isset($_GET['kelas']) && is_string($_GET['kelas']) ? trim($_GET['kelas']) : '';
    $subjectFilter = isset($_GET['mapel']) && is_scalar($_GET['mapel'])
        ? filter_var($_GET['mapel'], FILTER_VALIDATE_INT)
        : 0;
    $filterStatement = nilai_prepare(
        $koneksi,
        "SELECT DISTINCT TRIM(s.kelas) AS kelas, mp.id AS mapel_id, mp.nama_mapel
         FROM mengajar mg
         INNER JOIN siswa s ON s.id = mg.siswa_id
         INNER JOIN mapel mp ON mp.id = mg.mapel_id
         INNER JOIN guru g ON g.id = mp.guru_id
         INNER JOIN users u ON u.id = g.user_id AND u.role = 'guru'
         WHERE g.user_id = ?
         ORDER BY kelas, mp.nama_mapel",
        'Could not prepare grade filters'
    );
    mysqli_stmt_bind_param($filterStatement, 'i', $teacherUserId);
    nilai_execute($filterStatement, 'Could not load grade filters');
    $filterRows = mysqli_fetch_all(mysqli_stmt_get_result($filterStatement), MYSQLI_ASSOC);
    mysqli_stmt_close($filterStatement);

    $classes = array_values(array_unique(array_column($filterRows, 'kelas')));
    $subjectMap = [];
    foreach ($filterRows as $filterRow) {
        $subjectMap[(int) $filterRow['mapel_id']] = $filterRow['nama_mapel'];
    }
    $subjects = $subjectMap;
    if (!in_array($classFilter, $classes, true)) {
        $classFilter = '';
    }
    if (!isset($subjects[(int) $subjectFilter])) {
        $subjectFilter = 0;
    }

    $search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
    $pattern = '%' . $search . '%';
    $emptyClass = '';
    $emptySearch = '';
    $zeroSubject = 0;
    $limit = 15;
    $page = isset($_GET['page']) && is_scalar($_GET['page'])
        ? max(1, (int) $_GET['page'])
        : 1;

    $countStatement = nilai_prepare(
        $koneksi,
        'SELECT COUNT(*) AS total ' . $filterSql,
        'Could not prepare grade count'
    );
    mysqli_stmt_bind_param(
        $countStatement,
        'isssssii',
        $teacherUserId,
        $emptySearch,
        $pattern,
        $pattern,
        $emptyClass,
        $classFilter,
        $zeroSubject,
        $subjectFilter
    );
    nilai_execute($countStatement, 'Could not count grade records');
    $totalRows = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStatement))['total'];
    mysqli_stmt_close($countStatement);
    $totalPages = max(1, (int) ceil($totalRows / $limit));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $limit;

    $listStatement = nilai_prepare(
        $koneksi,
        'SELECT nr.id, nr.nilai_kkm, nr.nilai_pengetahuan, nr.nilai_keterampilan, nr.keterangan,
                s.id AS siswa_id, s.nis, s.nama AS nama_siswa, TRIM(s.kelas) AS kelas,
                mp.id AS mapel_id, mp.nama_mapel
         ' . $filterSql . '
         ORDER BY kelas ASC, nama_siswa ASC, mp.nama_mapel ASC, nr.id DESC
         LIMIT ? OFFSET ?',
        'Could not prepare grade list'
    );
    mysqli_stmt_bind_param(
        $listStatement,
        'isssssiiii',
        $teacherUserId,
        $emptySearch,
        $pattern,
        $pattern,
        $emptyClass,
        $classFilter,
        $zeroSubject,
        $subjectFilter,
        $limit,
        $offset
    );
    nilai_execute($listStatement, 'Could not load grade records');
    $grades = mysqli_fetch_all(mysqli_stmt_get_result($listStatement), MYSQLI_ASSOC);
    mysqli_stmt_close($listStatement);

    $queryWithoutPage = $_GET;
    unset($queryWithoutPage['page']);
    $paginationBase = http_build_query($queryWithoutPage);
}

$csrfToken = nilai_csrf_token();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $mode === 'detail' ? 'Detail Nilai Siswa' : ($mode === 'list' ? 'Melihat Nilai Siswa' : ($mode === 'edit' ? 'Edit Nilai Siswa' : 'Tambah Nilai Siswa')); ?> - SIPN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= nilai_escape(app_url('Css/style.css')); ?>">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>
<?php if ($mode === 'list'): ?>
<main class="container assignment-page">
    <header class="assignment-heading">
        <div>
            <span class="assignment-eyebrow">RUANG GURU</span>
            <h1>Melihat Nilai Siswa</h1>
            <p>Lihat dan kelola nilai siswa pada kelas serta mata pelajaran yang menjadi penugasan Anda.</p>
        </div>
        <a class="btn btn-primary assignment-add-button" href="<?= nilai_escape(app_url('Nilai/kelola_nilai.php?mode=create')); ?>"><span aria-hidden="true">＋</span> Tambah nilai</a>
    </header>
    <?php if ($successMessage !== ''): ?><div class="alert alert-success" role="status"><?= nilai_escape($successMessage); ?></div><?php endif; ?>
    <?php if ($errorMessage !== ''): ?><div class="alert alert-danger" role="alert"><?= nilai_escape($errorMessage); ?></div><?php endif; ?>
    <section class="assignment-card assignment-list-card">
        <div class="assignment-card-heading">
            <div><h2>Daftar nilai siswa</h2><p><?= $totalRows; ?> siswa/penugasan ditemukan.</p></div>
        </div>
        <form class="assignment-filters" method="GET" action="<?= nilai_escape(app_url('Nilai/kelola_nilai.php')); ?>">
            <label class="assignment-search">
                <span class="visually-hidden">Cari siswa</span>
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" name="q" value="<?= nilai_escape($search); ?>" placeholder="Cari nama siswa atau NIS...">
            </label>
            <select class="form-select" name="kelas" aria-label="Filter kelas">
                <option value="">Semua kelas</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= nilai_escape($class); ?>" <?= $classFilter === $class ? 'selected' : ''; ?>><?= nilai_escape($class); ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="mapel" aria-label="Filter mata pelajaran">
                <option value="">Semua mata pelajaran</option>
                <?php foreach ($subjects as $id => $subjectName): ?>
                    <option value="<?= (int) $id; ?>" <?= (int) $subjectFilter === (int) $id ? 'selected' : ''; ?>><?= nilai_escape($subjectName); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="assignment-filter-actions">
                <button class="btn btn-primary" type="submit">Cari</button>
                <a class="btn btn-light" href="<?= nilai_escape(app_url('Nilai/kelola_nilai.php')); ?>">Reset</a>
            </div>
        </form>
        <div class="table-responsive assignment-table-wrap">
            <table class="table align-middle assignment-table grade-table">
                <thead><tr><th>No</th><th>Siswa</th><th>Kelas</th><th>Mata pelajaran</th><th>KKM</th><th>Pengetahuan</th><th>Keterampilan</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if ($grades): ?>
                    <?php foreach ($grades as $index => $row): ?>
                    <tr>
                        <td><span class="assignment-row-number"><?= $offset + $index + 1; ?></span></td>
                        <td><div class="assignment-teacher-name"><strong><?= nilai_escape($row['nama_siswa']); ?></strong><small>NIS <?= nilai_escape($row['nis']); ?></small></div></td>
                        <td><span class="assignment-class-pill"><?= nilai_escape($row['kelas']); ?></span></td>
                        <td><?= nilai_escape($row['nama_mapel']); ?></td>
                        <td><?= $row['id'] === null ? '—' : (int) $row['nilai_kkm']; ?></td>
                        <td><?= $row['id'] === null ? '—' : (int) $row['nilai_pengetahuan']; ?></td>
                        <td><?= $row['id'] === null ? '—' : (int) $row['nilai_keterampilan']; ?></td>
                        <td>
                            <div class="users-actions">
                            <?php if ($row['id'] === null): ?>
                                <a class="users-action users-action-edit" href="<?= nilai_escape(app_url('Nilai/kelola_nilai.php?mode=create&assignment=' . $row['siswa_id'] . ':' . $row['mapel_id'])); ?>">Tambah nilai</a>
                            <?php else: ?>
                                <a class="users-action users-action-detail" href="<?= nilai_escape(app_url('Nilai/kelola_nilai.php?mode=detail&id=' . (int) $row['id'])); ?>">Detail</a>
                                <a class="users-action users-action-edit" href="<?= nilai_escape(app_url('Nilai/kelola_nilai.php?mode=edit&id=' . (int) $row['id'])); ?>">Edit</a>
                                <form method="POST" action="<?= nilai_escape(app_url('action/aksi_nilai.php')); ?>" onsubmit="return confirm('Hapus nilai siswa ini?');">
                                    <input type="hidden" name="csrf_token" value="<?= nilai_escape($csrfToken); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                    <button class="users-action users-action-delete" type="submit">Hapus</button>
                                </form>
                            <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8"><div class="users-empty-state"><span aria-hidden="true">▤</span><strong>Nilai siswa belum ditemukan</strong><p>Sesuaikan filter atau tambahkan nilai untuk siswa dalam penugasan Anda.</p><a class="btn btn-primary" href="<?= nilai_escape(app_url('Nilai/kelola_nilai.php?mode=create')); ?>">Tambah nilai siswa</a></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="users-pagination">
                <span class="users-pagination-copy">Halaman <?= $page; ?> dari <?= $totalPages; ?></span>
                <nav aria-label="Navigasi halaman nilai"><ul class="pagination mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?<?= nilai_escape($paginationBase . ($paginationBase !== '' ? '&' : '') . 'page=' . max(1, $page - 1)); ?>">Sebelumnya</a></li>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>"><a class="page-link" href="?<?= nilai_escape($paginationBase . ($paginationBase !== '' ? '&' : '') . 'page=' . min($totalPages, $page + 1)); ?>">Berikutnya</a></li>
                </ul></nav>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php elseif ($mode === 'detail'): ?>
<main class="container assignment-detail-page">
    <div class="assignment-breadcrumb"><a href="<?= nilai_escape(app_url('Nilai/kelola_nilai.php')); ?>">Melihat Nilai Siswa</a><span aria-hidden="true">/</span><span>Detail nilai</span></div>
    <section class="assignment-detail-hero">
        <span class="assignment-form-hero-icon" aria-hidden="true">▤</span>
        <div><span class="assignment-eyebrow">HASIL BELAJAR</span><h1>Detail nilai siswa</h1><p>Informasi nilai sesuai dengan penugasan mengajar Anda.</p></div>
    </section>
    <section class="assignment-detail-card p-4">
        <dl class="row mb-0">
            <dt class="col-sm-4">Nama siswa</dt><dd class="col-sm-8"><?= nilai_escape($grade['nama_siswa']); ?></dd>
            <dt class="col-sm-4">NIS</dt><dd class="col-sm-8"><?= nilai_escape($grade['nis']); ?></dd>
            <dt class="col-sm-4">Kelas</dt><dd class="col-sm-8"><?= nilai_escape($grade['kelas']); ?></dd>
            <dt class="col-sm-4">Mata pelajaran</dt><dd class="col-sm-8"><?= nilai_escape($grade['nama_mapel']); ?></dd>
            <dt class="col-sm-4">KKM</dt><dd class="col-sm-8"><?= (int) $grade['nilai_kkm']; ?></dd>
            <dt class="col-sm-4">Nilai pengetahuan</dt><dd class="col-sm-8"><?= (int) $grade['nilai_pengetahuan']; ?></dd>
            <dt class="col-sm-4">Nilai keterampilan</dt><dd class="col-sm-8"><?= (int) $grade['nilai_keterampilan']; ?></dd>
            <dt class="col-sm-4">Keterangan</dt><dd class="col-sm-8"><?= nilai_escape($grade['keterangan']); ?></dd>
        </dl>
        <div class="assignment-form-footer">
            <a class="btn btn-light" href="<?= nilai_escape(app_url('Nilai/kelola_nilai.php')); ?>">Kembali ke daftar</a>
            <a class="btn btn-primary" href="<?= nilai_escape(app_url('Nilai/kelola_nilai.php?mode=edit&id=' . (int) $grade['id'])); ?>">Edit nilai</a>
        </div>
    </section>
</main>
<?php else: ?>
<main class="container assignment-form-page">
    <div class="assignment-breadcrumb"><a href="<?= nilai_escape(app_url('Nilai/kelola_nilai.php')); ?>">Melihat Nilai Siswa</a><span aria-hidden="true">/</span><span><?= $mode === 'edit' ? 'Edit nilai' : 'Tambah nilai'; ?></span></div>
    <section class="assignment-form-hero">
        <span class="assignment-form-hero-icon" aria-hidden="true">▤</span>
        <div><span class="assignment-eyebrow">HASIL BELAJAR</span><h1><?= $mode === 'edit' ? 'Edit nilai siswa' : 'Tambah nilai siswa'; ?></h1><p>Nilai hanya dapat dibuat untuk siswa dan mata pelajaran dalam penugasan Anda.</p></div>
    </section>
    <?php if ($errorMessage !== ''): ?><div class="alert alert-danger" role="alert"><?= nilai_escape($errorMessage); ?></div><?php endif; ?>
    <?php if (!$assignments): ?><div class="alert alert-warning" role="alert">Belum ada siswa dan mata pelajaran yang terhubung dengan penugasan Anda.</div><?php endif; ?>
    <form class="assignment-form" method="POST" action="<?= nilai_escape(app_url('action/aksi_nilai.php')); ?>">
        <input type="hidden" name="csrf_token" value="<?= nilai_escape($csrfToken); ?>">
        <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create'; ?>">
        <?php if ($mode === 'edit'): ?><input type="hidden" name="id" value="<?= (int) $grade['id']; ?>"><?php endif; ?>
        <section class="assignment-form-card">
            <header class="assignment-form-card-heading"><span>01</span><div><h2>Data nilai</h2><p>Pilih penugasan siswa dan isi seluruh komponen nilai.</p></div></header>
            <div class="assignment-form-grid">
                <div class="assignment-field assignment-field-wide">
                    <label for="assignment">Siswa dan mata pelajaran</label>
                    <select class="form-select" id="assignment" name="assignment" required>
                        <option value="">Pilih siswa dan mata pelajaran</option>
                        <?php foreach ($assignments as $assignment): ?>
                            <?php $pairValue = $assignment['siswa_id'] . ':' . $assignment['mapel_id']; ?>
                            <option value="<?= nilai_escape($pairValue); ?>" <?= (string) ($formValues['assignment'] ?? '') === $pairValue ? 'selected' : ''; ?>>
                                <?= nilai_escape($assignment['nama_siswa'] . ' · ' . $assignment['nis'] . ' · ' . $assignment['kelas'] . ' — ' . $assignment['nama_mapel']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php foreach (['nilai_kkm' => 'KKM', 'nilai_pengetahuan' => 'Nilai pengetahuan', 'nilai_keterampilan' => 'Nilai keterampilan'] as $field => $label): ?>
                    <div class="assignment-field">
                        <label for="<?= nilai_escape($field); ?>"><?= nilai_escape($label); ?></label>
                        <input class="form-control" id="<?= nilai_escape($field); ?>" name="<?= nilai_escape($field); ?>" type="number" min="0" max="100" step="1" value="<?= nilai_escape($formValues[$field] ?? ''); ?>" required>
                    </div>
                <?php endforeach; ?>
                <div class="assignment-field assignment-field-wide">
                    <label for="keterangan">Keterangan <span class="form-hint">(maksimal 50 karakter)</span></label>
                    <input class="form-control" id="keterangan" name="keterangan" type="text" maxlength="50" value="<?= nilai_escape($formValues['keterangan'] ?? ''); ?>" required>
                </div>
            </div>
        </section>
        <div class="assignment-form-footer">
            <a class="btn btn-light" href="<?= nilai_escape(app_url('Nilai/kelola_nilai.php')); ?>">Batal</a>
            <button class="btn btn-primary" type="submit" <?= !$assignments ? 'disabled' : ''; ?>><?= $mode === 'edit' ? 'Simpan perubahan' : 'Simpan nilai'; ?></button>
        </div>
    </form>
</main>
<?php endif; ?>
<?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>
