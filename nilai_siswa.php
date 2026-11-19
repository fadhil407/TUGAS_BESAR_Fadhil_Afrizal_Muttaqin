<?php
require_once __DIR__ . '/../auth.php';
require_role('siswa');
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/nilai_support.php';

$userId = (int) ($_SESSION['user']['id'] ?? 0);
if ($userId < 1) {
    redirect_to_role_dashboard();
}

$profileStatement = nilai_prepare(
    $koneksi,
    'SELECT id, nama, nis, TRIM(kelas) AS kelas FROM siswa WHERE user_id = ? LIMIT 1',
    'Could not prepare student grade profile'
);
mysqli_stmt_bind_param($profileStatement, 'i', $userId);
nilai_execute($profileStatement, 'Could not load student grade profile');
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($profileStatement)) ?: null;
mysqli_stmt_close($profileStatement);

$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$subjectFilter = isset($_GET['mapel']) && is_scalar($_GET['mapel'])
    ? filter_var($_GET['mapel'], FILTER_VALIDATE_INT)
    : 0;

$subjects = [];
$gradeRows = [];
$summary = [
    'jumlah_mapel' => 0,
    'mapel_dengan_nilai' => 0,
    'rata_rata' => null,
];

if ($student) {
    $studentId = (int) $student['id'];
    $subjectsStatement = nilai_prepare(
        $koneksi,
        "SELECT DISTINCT mp.id, mp.nama_mapel
         FROM (
             SELECT mapel_id FROM mengajar WHERE siswa_id = ?
             UNION
             SELECT mapel_id FROM nilai_rapor WHERE siswa_id = ?
         ) own_subjects
         INNER JOIN mapel mp ON mp.id = own_subjects.mapel_id
         ORDER BY mp.nama_mapel",
        'Could not prepare student grade subjects'
    );
    mysqli_stmt_bind_param($subjectsStatement, 'ii', $studentId, $studentId);
    nilai_execute($subjectsStatement, 'Could not load student grade subjects');
    $subjects = mysqli_fetch_all(mysqli_stmt_get_result($subjectsStatement), MYSQLI_ASSOC);
    mysqli_stmt_close($subjectsStatement);

    $validSubjectIds = array_map('intval', array_column($subjects, 'id'));
    if (!$subjectFilter || !in_array((int) $subjectFilter, $validSubjectIds, true)) {
        $subjectFilter = 0;
    }

    $conditions = [];
    if ($subjectFilter > 0) {
        $conditions[] = 'mp.id = ?';
    }
    if ($search !== '') {
        $conditions[] = 'mp.nama_mapel LIKE ?';
    }
    $whereSql = $conditions ? ' AND ' . implode(' AND ', $conditions) : '';
    $groupedSql = "
        SELECT mp.id AS mapel_id, mp.nama_mapel,
               COUNT(nr.id) AS jumlah_nilai,
               AVG(nr.nilai_pengetahuan) AS rata_pengetahuan,
               AVG(nr.nilai_keterampilan) AS rata_keterampilan,
               AVG((nr.nilai_pengetahuan + nr.nilai_keterampilan) / 2) AS nilai_akhir,
               AVG(NULLIF(nr.nilai_kkm, 0)) AS kkm,
               GROUP_CONCAT(DISTINCT NULLIF(TRIM(nr.keterangan), '')
                   ORDER BY nr.keterangan SEPARATOR ' · ') AS keterangan
        FROM (
            SELECT mapel_id FROM mengajar WHERE siswa_id = ?
            UNION
            SELECT mapel_id FROM nilai_rapor WHERE siswa_id = ?
        ) own_subjects
        INNER JOIN mapel mp ON mp.id = own_subjects.mapel_id
        LEFT JOIN nilai_rapor nr
            ON nr.siswa_id = ? AND nr.mapel_id = mp.id
        WHERE 1 = 1 $whereSql
        GROUP BY mp.id, mp.nama_mapel
    ";
    $params = [$studentId, $studentId, $studentId];
    $types = 'iii';
    if ($subjectFilter > 0) {
        $params[] = (int) $subjectFilter;
        $types .= 'i';
    }
    if ($search !== '') {
        $params[] = '%' . $search . '%';
        $types .= 's';
    }

    $summaryStatement = nilai_prepare(
        $koneksi,
        "SELECT COUNT(*) AS jumlah_mapel,
                SUM(nilai_akhir IS NOT NULL) AS mapel_dengan_nilai,
                AVG(nilai_akhir) AS rata_rata
         FROM ($groupedSql) own_recap",
        'Could not prepare student grade summary'
    );
    mysqli_stmt_bind_param($summaryStatement, $types, ...$params);
    nilai_execute($summaryStatement, 'Could not load student grade summary');
    $summary = mysqli_fetch_assoc(mysqli_stmt_get_result($summaryStatement));
    mysqli_stmt_close($summaryStatement);

    $listStatement = nilai_prepare(
        $koneksi,
        $groupedSql . ' ORDER BY mp.nama_mapel',
        'Could not prepare student grade details'
    );
    mysqli_stmt_bind_param($listStatement, $types, ...$params);
    nilai_execute($listStatement, 'Could not load student grade details');
    $gradeRows = mysqli_fetch_all(mysqli_stmt_get_result($listStatement), MYSQLI_ASSOC);
    mysqli_stmt_close($listStatement);
}

$average = $summary['rata_rata'] !== null
    ? number_format((float) $summary['rata_rata'], 2, ',', '.')
    : '—';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Melihat Nilai Saya - SIPN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= nilai_escape(app_url('Css/style.css?v=20261021-1352')); ?>">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>
<main class="container student-grades-page">
    <header class="student-grades-hero">
        <div>
            <span class="assignment-eyebrow">RUANG SISWA</span>
            <h1>Melihat Nilai Saya</h1>
            <p>Halo, <?= nilai_escape($student['nama'] ?? ($_SESSION['user']['username'] ?? 'Siswa')); ?>. Pantau nilai dan perkembangan belajarmu di sini.</p>
            <?php if ($student): ?>
                <div class="student-grade-identity">
                    <span><?= nilai_escape($student['kelas'] !== '' ? $student['kelas'] : 'Kelas belum tersedia'); ?></span>
                    <?php if ($student['nis'] !== ''): ?><span>NIS <?= nilai_escape($student['nis']); ?></span><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <span class="student-grades-hero-icon" aria-hidden="true">✦</span>
    </header>

    <section class="student-grade-summary" aria-label="Ringkasan nilai saya">
        <article class="student-grade-summary-card">
            <span class="student-grade-summary-icon" aria-hidden="true">↗</span>
            <div><span>Rata-rata nilai akhir</span><strong><?= $average; ?></strong></div>
        </article>
        <article class="student-grade-summary-card">
            <span class="student-grade-summary-icon student-grade-icon-sage" aria-hidden="true">▤</span>
            <div><span>Mata pelajaran</span><strong><?= (int) ($summary['jumlah_mapel'] ?? 0); ?></strong></div>
        </article>
        <article class="student-grade-summary-card">
            <span class="student-grade-summary-icon student-grade-icon-amber" aria-hidden="true">✓</span>
            <div><span>Sudah memiliki nilai</span><strong><?= (int) ($summary['mapel_dengan_nilai'] ?? 0); ?></strong></div>
        </article>
    </section>

    <div class="student-grade-info" role="note">
        <strong>Komponen nilai yang tersedia</strong>
        <span>Database saat ini menyimpan nilai pengetahuan, keterampilan, KKM, dan keterangan. Nilai tugas, kuis, praktik, UTS, atau UAS hanya dapat ditampilkan jika komponen tersebut tersedia di data SIPN.</span>
    </div>

    <section class="assignment-card student-grade-list">
        <div class="assignment-card-heading">
            <div><h2>Detail nilai per mata pelajaran</h2><p>Nilai akhir adalah rata-rata nilai pengetahuan dan keterampilan yang tersimpan.</p></div>
        </div>
        <form class="student-grade-filters" method="GET" action="<?= nilai_escape(app_url('Nilai/nilai_siswa.php')); ?>">
            <label class="assignment-search">
                <span class="visually-hidden">Cari mata pelajaran</span>
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" name="q" value="<?= nilai_escape($search); ?>" placeholder="Cari nama mata pelajaran...">
            </label>
            <select class="form-select" name="mapel" aria-label="Filter mata pelajaran">
                <option value="">Semua mata pelajaran</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?= (int) $subject['id']; ?>" <?= (int) $subjectFilter === (int) $subject['id'] ? 'selected' : ''; ?>><?= nilai_escape($subject['nama_mapel']); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="assignment-filter-actions">
                <button class="btn btn-primary" type="submit">Cari nilai</button>
                <a class="btn btn-light" href="<?= nilai_escape(app_url('Nilai/nilai_siswa.php')); ?>">Reset</a>
            </div>
        </form>

        <div class="student-grade-cards">
            <?php if (!$student): ?>
                <div class="users-empty-state student-grade-empty">
                    <span aria-hidden="true">▤</span>
                    <strong>Profil siswa belum terhubung</strong>
                    <p>Hubungi administrator untuk menghubungkan akun ini dengan data siswa.</p>
                </div>
            <?php elseif ($gradeRows): ?>
                <?php foreach ($gradeRows as $row): ?>
                    <?php
                    $hasGrade = (int) $row['jumlah_nilai'] > 0 && $row['nilai_akhir'] !== null;
                    $hasKkm = $row['kkm'] !== null;
                    $passed = $hasGrade && $hasKkm && (float) $row['nilai_akhir'] >= (float) $row['kkm'];
                    ?>
                    <article class="student-grade-card">
                        <header class="student-grade-card-heading">
                            <div>
                                <span class="student-grade-card-label">MATA PELAJARAN</span>
                                <h3><?= nilai_escape($row['nama_mapel']); ?></h3>
                            </div>
                            <?php if (!$hasGrade): ?>
                                <span class="recap-status recap-status-empty">Belum ada nilai</span>
                            <?php elseif (!$hasKkm): ?>
                                <span class="recap-status recap-status-empty">KKM belum tersedia</span>
                            <?php elseif ($passed): ?>
                                <span class="recap-status recap-status-pass">Tuntas</span>
                            <?php else: ?>
                                <span class="recap-status recap-status-remedial">Belum tuntas</span>
                            <?php endif; ?>
                        </header>
                        <div class="student-grade-components">
                            <div><span>Pengetahuan</span><strong><?= $hasGrade ? number_format((float) $row['rata_pengetahuan'], 2, ',', '.') : '—'; ?></strong></div>
                            <div><span>Keterampilan</span><strong><?= $hasGrade ? number_format((float) $row['rata_keterampilan'], 2, ',', '.') : '—'; ?></strong></div>
                            <div><span>KKM</span><strong><?= $hasKkm ? number_format((float) $row['kkm'], 0, ',', '.') : '—'; ?></strong></div>
                            <div class="student-grade-final"><span>Nilai akhir</span><strong><?= $hasGrade ? number_format((float) $row['nilai_akhir'], 2, ',', '.') : '—'; ?></strong></div>
                        </div>
                        <?php if (!empty($row['keterangan'])): ?>
                            <p class="student-grade-note"><strong>Keterangan:</strong> <?= nilai_escape($row['keterangan']); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="users-empty-state student-grade-empty">
                    <span aria-hidden="true">▤</span>
                    <strong>Nilai mata pelajaran belum ditemukan</strong>
                    <p><?= $search !== '' || $subjectFilter
                        ? 'Coba ubah pencarian atau filter mata pelajaran.'
                        : 'Belum ada mata pelajaran atau nilai yang terhubung dengan akunmu.'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>
