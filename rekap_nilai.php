<?php
require_once __DIR__ . '/../auth.php';
require_login();

$currentRole = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');
if (!in_array($currentRole, ['guru', 'siswa'], true)) {
    redirect_to_role_dashboard();
}
require_role($currentRole);
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/nilai_support.php';

$userId = (int) ($_SESSION['user']['id'] ?? 0);
if ($userId < 1) {
    redirect_to_role_dashboard();
}

if ($currentRole === 'guru') {
    $assignmentSql = "
        SELECT DISTINCT mg.siswa_id, mg.mapel_id
        FROM mengajar mg
        INNER JOIN mapel assigned_mapel ON assigned_mapel.id = mg.mapel_id
        INNER JOIN guru assigned_guru ON assigned_guru.id = assigned_mapel.guru_id
        INNER JOIN users assigned_user ON assigned_user.id = assigned_guru.user_id
            AND assigned_user.role = 'guru'
        WHERE assigned_guru.user_id = ?
    ";
    $scopeParams = [$userId];
    $scopeTypes = 'i';
} else {
    $assignmentSql = "
        SELECT own_assignments.siswa_id, own_assignments.mapel_id
        FROM (
            SELECT s.id AS siswa_id, mg.mapel_id
            FROM siswa s
            INNER JOIN mengajar mg ON mg.siswa_id = s.id
            WHERE s.user_id = ?
            UNION
            SELECT s.id AS siswa_id, nr.mapel_id
            FROM siswa s
            INNER JOIN nilai_rapor nr ON nr.siswa_id = s.id
            WHERE s.user_id = ?
        ) own_assignments
    ";
    $scopeParams = [$userId, $userId];
    $scopeTypes = 'ii';
}

$optionsStatement = nilai_prepare(
    $koneksi,
    "SELECT DISTINCT TRIM(s.kelas) AS kelas, mp.id AS mapel_id, mp.nama_mapel
     FROM ($assignmentSql) assigned
     INNER JOIN siswa s ON s.id = assigned.siswa_id
     INNER JOIN mapel mp ON mp.id = assigned.mapel_id
     ORDER BY kelas, mp.nama_mapel",
    'Could not prepare grade recap filters'
);
mysqli_stmt_bind_param($optionsStatement, $scopeTypes, ...$scopeParams);
nilai_execute($optionsStatement, 'Could not load grade recap filters');
$filterRows = mysqli_fetch_all(mysqli_stmt_get_result($optionsStatement), MYSQLI_ASSOC);
mysqli_stmt_close($optionsStatement);

$classes = array_values(array_unique(array_filter(array_column($filterRows, 'kelas'), 'strlen')));
$subjects = [];
foreach ($filterRows as $filterRow) {
    $subjects[(int) $filterRow['mapel_id']] = $filterRow['nama_mapel'];
}

$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$classFilter = isset($_GET['kelas']) && is_string($_GET['kelas']) ? trim($_GET['kelas']) : '';
$subjectFilter = isset($_GET['mapel']) && is_scalar($_GET['mapel'])
    ? filter_var($_GET['mapel'], FILTER_VALIDATE_INT)
    : 0;
if (!in_array($classFilter, $classes, true)) {
    $classFilter = '';
}
if (!$subjectFilter || !isset($subjects[(int) $subjectFilter])) {
    $subjectFilter = 0;
}

$conditions = [];
$filterParams = $scopeParams;
$filterTypes = $scopeTypes;
if ($classFilter !== '') {
    $conditions[] = 'TRIM(s.kelas) = ?';
    $filterParams[] = $classFilter;
    $filterTypes .= 's';
}
if ($subjectFilter > 0) {
    $conditions[] = 'mp.id = ?';
    $filterParams[] = (int) $subjectFilter;
    $filterTypes .= 'i';
}
if ($search !== '') {
    $conditions[] = '(s.nama LIKE ? OR s.nis LIKE ? OR mp.nama_mapel LIKE ?)';
    $searchPattern = '%' . $search . '%';
    array_push($filterParams, $searchPattern, $searchPattern, $searchPattern);
    $filterTypes .= 'sss';
}

$whereSql = $conditions ? ' AND ' . implode(' AND ', $conditions) : '';
$groupedSql = "
    SELECT s.id AS siswa_id, s.nama AS nama_siswa, s.nis, TRIM(s.kelas) AS kelas,
           mp.id AS mapel_id, mp.nama_mapel,
           COUNT(nr.id) AS jumlah_nilai,
           AVG(nr.nilai_pengetahuan) AS rata_pengetahuan,
           AVG(nr.nilai_keterampilan) AS rata_keterampilan,
           AVG((nr.nilai_pengetahuan + nr.nilai_keterampilan) / 2) AS nilai_akhir,
           AVG(NULLIF(nr.nilai_kkm, 0)) AS kkm
    FROM ($assignmentSql) assigned
    INNER JOIN siswa s ON s.id = assigned.siswa_id
    INNER JOIN mapel mp ON mp.id = assigned.mapel_id
    LEFT JOIN nilai_rapor nr
        ON nr.siswa_id = assigned.siswa_id AND nr.mapel_id = assigned.mapel_id
    WHERE 1 = 1 $whereSql
    GROUP BY s.id, s.nama, s.nis, TRIM(s.kelas), mp.id, mp.nama_mapel
";

$summaryStatement = nilai_prepare(
    $koneksi,
    "SELECT COUNT(*) AS jumlah_mapel,
            SUM(nilai_akhir IS NOT NULL) AS mapel_dengan_nilai,
            AVG(nilai_akhir) AS rata_rata_umum
     FROM ($groupedSql) rekap",
    'Could not prepare grade recap summary'
);
mysqli_stmt_bind_param($summaryStatement, $filterTypes, ...$filterParams);
nilai_execute($summaryStatement, 'Could not load grade recap summary');
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($summaryStatement));
mysqli_stmt_close($summaryStatement);

$limit = 20;
$page = isset($_GET['page']) && is_scalar($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$totalRows = (int) ($summary['jumlah_mapel'] ?? 0);
$totalPages = max(1, (int) ceil($totalRows / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

$listStatement = nilai_prepare(
    $koneksi,
    $groupedSql . ' ORDER BY kelas ASC, nama_siswa ASC, nama_mapel ASC LIMIT ? OFFSET ?',
    'Could not prepare grade recap rows'
);
$listParams = array_merge($filterParams, [$limit, $offset]);
$listTypes = $filterTypes . 'ii';
mysqli_stmt_bind_param($listStatement, $listTypes, ...$listParams);
nilai_execute($listStatement, 'Could not load grade recap rows');
$recapRows = mysqli_fetch_all(mysqli_stmt_get_result($listStatement), MYSQLI_ASSOC);
mysqli_stmt_close($listStatement);

$queryWithoutPage = $_GET;
unset($queryWithoutPage['page']);
$paginationBase = http_build_query($queryWithoutPage);
$average = $summary['rata_rata_umum'] !== null
    ? number_format((float) $summary['rata_rata_umum'], 2, ',', '.')
    : '—';
$heading = $currentRole === 'guru' ? 'Rekap Nilai Siswa' : 'Rekap Nilai Saya';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= nilai_escape($heading); ?> - SIPN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= nilai_escape(app_url('Css/style.css?v=20261021-1339')); ?>">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>
<main class="container recap-page">
    <header class="recap-hero">
        <div>
            <span class="assignment-eyebrow"><?= $currentRole === 'guru' ? 'RUANG GURU' : 'RUANG SISWA'; ?></span>
            <h1><?= nilai_escape($heading); ?></h1>
            <p>
                <?= $currentRole === 'guru'
                    ? 'Pantau hasil belajar siswa pada kelas dan mata pelajaran yang menjadi penugasan Anda.'
                    : 'Lihat perkembangan nilaimu. Rekap ini hanya menampilkan nilai yang terhubung dengan akunmu.'; ?>
            </p>
        </div>
        <span class="recap-hero-mark" aria-hidden="true">▤</span>
    </header>

    <section class="recap-summary-grid" aria-label="Ringkasan nilai">
        <article class="recap-summary-card recap-summary-average">
            <span class="recap-summary-icon" aria-hidden="true">↗</span>
            <div><span>Rata-rata <?= $currentRole === 'guru' ? 'hasil terfilter' : 'nilai saya'; ?></span><strong><?= $average; ?></strong></div>
        </article>
        <article class="recap-summary-card">
            <span class="recap-summary-icon" aria-hidden="true">▤</span>
            <div><span>Rekap mata pelajaran</span><strong><?= (int) ($summary['jumlah_mapel'] ?? 0); ?></strong></div>
        </article>
        <article class="recap-summary-card">
            <span class="recap-summary-icon recap-icon-sage" aria-hidden="true">✓</span>
            <div><span>Memiliki nilai</span><strong><?= (int) ($summary['mapel_dengan_nilai'] ?? 0); ?></strong></div>
        </article>
    </section>

    <section class="assignment-card recap-card">
        <div class="assignment-card-heading">
            <div><h2>Rincian rekap</h2><p>Nilai akhir dihitung dari rata-rata nilai pengetahuan dan keterampilan yang tersedia.</p></div>
        </div>
        <form class="assignment-filters recap-filters" method="GET" action="<?= nilai_escape(app_url('Nilai/rekap_nilai.php')); ?>">
            <label class="assignment-search">
                <span class="visually-hidden">Cari siswa atau mata pelajaran</span>
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" name="q" value="<?= nilai_escape($search); ?>" placeholder="<?= $currentRole === 'guru' ? 'Cari nama siswa, NIS, atau mapel...' : 'Cari mata pelajaran...'; ?>">
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
                <button class="btn btn-primary" type="submit">Terapkan</button>
                <a class="btn btn-light" href="<?= nilai_escape(app_url('Nilai/rekap_nilai.php')); ?>">Reset</a>
            </div>
        </form>

        <div class="table-responsive assignment-table-wrap recap-table-wrap">
            <table class="table align-middle assignment-table recap-table">
                <thead><tr>
                    <?php if ($currentRole === 'guru'): ?><th>Siswa</th><th>Kelas</th><?php endif; ?>
                    <th>Mata pelajaran</th><th>Rata-rata pengetahuan</th><th>Rata-rata keterampilan</th><th>Nilai akhir</th><th>KKM</th><th>Status</th>
                </tr></thead>
                <tbody>
                <?php if ($recapRows): ?>
                    <?php foreach ($recapRows as $row): ?>
                    <?php
                    $hasGrade = (int) $row['jumlah_nilai'] > 0 && $row['nilai_akhir'] !== null;
                    $hasKkm = $row['kkm'] !== null;
                    $isComplete = $hasGrade && $hasKkm;
                    $isPassed = $isComplete && (float) $row['nilai_akhir'] >= (float) $row['kkm'];
                    ?>
                    <tr>
                        <?php if ($currentRole === 'guru'): ?>
                            <td data-label="Siswa"><div class="recap-student"><strong><?= nilai_escape($row['nama_siswa']); ?></strong><small>NIS <?= nilai_escape($row['nis']); ?></small></div></td>
                            <td data-label="Kelas"><span class="assignment-class-pill"><?= nilai_escape($row['kelas']); ?></span></td>
                        <?php endif; ?>
                        <td data-label="Mata pelajaran"><strong class="recap-subject"><?= nilai_escape($row['nama_mapel']); ?></strong></td>
                        <td data-label="Rata-rata pengetahuan"><?= $hasGrade ? number_format((float) $row['rata_pengetahuan'], 2, ',', '.') : '—'; ?></td>
                        <td data-label="Rata-rata keterampilan"><?= $hasGrade ? number_format((float) $row['rata_keterampilan'], 2, ',', '.') : '—'; ?></td>
                        <td data-label="Nilai akhir"><span class="recap-final-score"><?= $hasGrade ? number_format((float) $row['nilai_akhir'], 2, ',', '.') : '—'; ?></span></td>
                        <td data-label="KKM"><?= $hasKkm ? number_format((float) $row['kkm'], 0, ',', '.') : '—'; ?></td>
                        <td data-label="Status">
                            <?php if (!$hasGrade): ?>
                                <span class="recap-status recap-status-empty">Belum ada nilai</span>
                            <?php elseif (!$hasKkm): ?>
                                <span class="recap-status recap-status-empty">KKM belum tersedia</span>
                            <?php elseif ($isPassed): ?>
                                <span class="recap-status recap-status-pass">Tuntas</span>
                            <?php else: ?>
                                <span class="recap-status recap-status-remedial">Belum tuntas</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?= $currentRole === 'guru' ? 8 : 6; ?>">
                        <div class="users-empty-state">
                            <span aria-hidden="true">▤</span>
                            <strong>Rekap nilai belum ditemukan</strong>
                            <p><?= $search !== '' || $classFilter !== '' || $subjectFilter
                                ? 'Coba ubah pencarian atau filter yang dipilih.'
                                : ($currentRole === 'guru'
                                    ? 'Belum ada siswa atau mata pelajaran yang terhubung dengan penugasan Anda.'
                                    : 'Belum ada nilai atau penugasan yang terhubung dengan akun Anda.'); ?></p>
                        </div>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="users-pagination recap-pagination">
                <span class="users-pagination-copy">Halaman <?= $page; ?> dari <?= $totalPages; ?></span>
                <nav aria-label="Navigasi halaman rekap"><ul class="pagination mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?<?= nilai_escape($paginationBase . ($paginationBase !== '' ? '&' : '') . 'page=' . max(1, $page - 1)); ?>">Sebelumnya</a></li>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>"><a class="page-link" href="?<?= nilai_escape($paginationBase . ($paginationBase !== '' ? '&' : '') . 'page=' . min($totalPages, $page + 1)); ?>">Berikutnya</a></li>
                </ul></nav>
            </div>
        <?php endif; ?>
    </section>
    <p class="recap-footnote">Rekap dihitung langsung dari data nilai yang tersimpan dan akan ikut berubah saat guru menambah, mengedit, atau menghapus nilai.</p>
</main>
<?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>
