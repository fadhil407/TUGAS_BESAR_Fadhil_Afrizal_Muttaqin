<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/mengajar_support.php';

$assignmentId = $assignmentId ?? null;
$isEdit = $assignmentId !== null;
$assignment = null;
if ($isEdit) {
    $assignmentStatement = mengajar_prepare(
        $koneksi,
        "SELECT MIN(m.id) AS id, mp.id AS mapel_id, mp.guru_id, TRIM(s.kelas) AS kelas
         FROM mengajar selected
         INNER JOIN siswa selected_student ON selected_student.id = selected.siswa_id
         INNER JOIN mengajar m ON m.mapel_id = selected.mapel_id
         INNER JOIN siswa s ON s.id = m.siswa_id AND TRIM(s.kelas) = TRIM(selected_student.kelas)
         INNER JOIN mapel mp ON mp.id = m.mapel_id
         WHERE selected.id = ?
         GROUP BY mp.id, mp.guru_id, TRIM(s.kelas)
         LIMIT 1",
        'Gagal menyiapkan data penugasan'
    );
    mysqli_stmt_bind_param($assignmentStatement, 'i', $assignmentId);
    if (!mysqli_stmt_execute($assignmentStatement)) {
        error_log('Could not load assignment for editing: ' . mysqli_stmt_error($assignmentStatement));
        http_response_code(500);
        die('Gagal memuat penugasan.');
    }
    $assignment = mysqli_fetch_assoc(mysqli_stmt_get_result($assignmentStatement));
    mysqli_stmt_close($assignmentStatement);
    if (!$assignment || (int) $assignment['id'] !== $assignmentId) {
        $_SESSION['mengajar_error'] = 'Penugasan tidak ditemukan.';
        header('Location: ' . app_url('mengajar/kelola_mengajar.php'));
        exit();
    }
}

$options = mengajar_fetch_options($koneksi);
$errorMessage = $_SESSION['mengajar_error'] ?? '';
$oldValues = $_SESSION['mengajar_values'] ?? [];
unset($_SESSION['mengajar_error'], $_SESSION['mengajar_values']);
$values = [
    'guru_id' => (int) ($oldValues['guru_id'] ?? $assignment['guru_id'] ?? 0),
    'mapel_id' => (string) ($oldValues['mapel_id'] ?? $assignment['mapel_id'] ?? ''),
    'nama_mapel_baru' => (string) ($oldValues['nama_mapel_baru'] ?? ''),
    'kelas' => $oldValues['kelas'] ?? $assignment['kelas'] ?? '',
];
$csrfToken = mengajar_csrf_token();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isEdit ? 'Edit' : 'Tambah'; ?> Guru Mengajar - SIPN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1401">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>
<main class="container assignment-form-page">
    <div class="assignment-breadcrumb"><a href="<?= htmlspecialchars(app_url('mengajar/kelola_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>">Daftar guru mengajar</a><span aria-hidden="true">/</span><span><?= $isEdit ? 'Edit penugasan' : 'Tambah penugasan'; ?></span></div>

    <section class="assignment-form-hero">
        <span class="assignment-form-hero-icon" aria-hidden="true">▤</span>
        <div><span class="assignment-eyebrow">PENUGASAN AKADEMIK</span><h1><?= $isEdit ? 'Edit guru mengajar' : 'Tambah guru mengajar'; ?></h1><p>Tentukan guru, kelas, dan mata pelajaran untuk penugasan ini.</p></div>
    </section>

    <?php if ($errorMessage !== ''): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if (!$options['guru']): ?><div class="alert alert-warning" role="alert">Data guru belum tersedia. Tambahkan data guru terlebih dahulu.</div><?php endif; ?>
    <?php if (!$options['mapel']): ?><div class="alert alert-info" role="status">Belum ada mata pelajaran yang terhubung ke guru. Admin dapat mengetik nama mata pelajaran baru di formulir ini.</div><?php endif; ?>
    <?php if (!$options['kelas']): ?><div class="alert alert-warning" role="alert">Belum ada kelas dari data siswa. Tambahkan data siswa beserta kelasnya terlebih dahulu.</div><?php endif; ?>
    <div class="alert alert-info" role="status">Sesuai struktur database, penugasan untuk satu kelas disimpan sebagai relasi mata pelajaran dengan setiap siswa di kelas tersebut.</div>

    <form action="<?= htmlspecialchars(app_url('action/aksi_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="assignment-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create'; ?>">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $assignmentId; ?>"><?php endif; ?>

        <section class="assignment-form-card">
            <header class="assignment-form-card-heading">
                <span>01</span><div><h2>Guru dan mata pelajaran</h2><p>Guru pengampu mengikuti data guru yang terhubung ke mata pelajaran.</p></div>
            </header>
            <div class="assignment-form-grid">
                <div class="assignment-field">
                    <label for="guru_id">Nama guru</label>
                    <select class="form-select" id="guru_id" name="guru_id" required>
                        <option value="">Pilih guru</option>
                        <?php foreach ($options['guru'] as $teacher): ?>
                            <option value="<?= (int) $teacher['id']; ?>" <?= $values['guru_id'] === (int) $teacher['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($teacher['nama'] . ' · NIP ' . $teacher['nip'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="assignment-field">
                    <label for="mapel_id">Nama mata pelajaran</label>
                    <select class="form-select" id="mapel_id" name="mapel_id" required>
                        <option value="">Pilih atau ketik mata pelajaran</option>
                        <?php foreach ($options['mapel'] as $subject): ?>
                            <option value="<?= (int) $subject['id']; ?>" data-guru-id="<?= (int) $subject['guru_id']; ?>" <?= $values['mapel_id'] === (string) $subject['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($subject['nama_mapel'] . ' · ' . ($subject['kode_mapel'] ?: 'Tanpa kode'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                        <option value="__new__" <?= $values['mapel_id'] === '__new__' ? 'selected' : ''; ?>>＋ Ketik mata pelajaran baru</option>
                    </select>
                    <div class="new-subject-field" id="new_subject_field" <?= $values['mapel_id'] === '__new__' ? '' : 'hidden'; ?>>
                        <label for="nama_mapel_baru">Nama mata pelajaran baru</label>
                        <input class="form-control" type="text" id="nama_mapel_baru" name="nama_mapel_baru" maxlength="100" value="<?= htmlspecialchars($values['nama_mapel_baru'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: Dasar-Dasar Pemrograman" <?= $values['mapel_id'] === '__new__' ? 'required' : 'disabled'; ?>>
                        <small>Mata pelajaran akan disimpan dan ditautkan ke guru yang dipilih.</small>
                    </div>
                </div>
                <div class="assignment-field">
                    <label for="kelas">Kelas</label>
                    <select class="form-select" id="kelas" name="kelas" required>
                        <option value="">Pilih kelas</option>
                        <?php foreach ($options['kelas'] as $class): ?>
                            <option value="<?= htmlspecialchars($class['nama'], ENT_QUOTES, 'UTF-8'); ?>" <?= $values['kelas'] === $class['nama'] ? 'selected' : ''; ?>><?= htmlspecialchars($class['nama'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </section>

        <div class="assignment-form-footer">
            <a class="btn btn-light" href="<?= htmlspecialchars(app_url('mengajar/kelola_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>">Batal</a>
            <button class="btn btn-primary" type="submit" <?= (!$options['guru'] || !$options['kelas']) ? 'disabled' : ''; ?>><?= $isEdit ? 'Simpan perubahan' : 'Simpan penugasan'; ?></button>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const teacher = document.getElementById('guru_id');
    const subject = document.getElementById('mapel_id');
    const newSubjectField = document.getElementById('new_subject_field');
    const newSubjectInput = document.getElementById('nama_mapel_baru');

    function filterSubjects() {
        const selectedSubject = subject.value;
        Array.from(subject.options).forEach(function (option) {
            if (option.value === '' || option.value === '__new__') return;
            option.hidden = option.dataset.guruId !== teacher.value;
        });
        if (selectedSubject && selectedSubject !== '__new__' && subject.selectedOptions[0].hidden) subject.value = '';
        const isNewSubject = subject.value === '__new__';
        newSubjectField.hidden = !isNewSubject;
        newSubjectInput.disabled = !isNewSubject;
        newSubjectInput.required = isNewSubject;
    }

    teacher.addEventListener('change', filterSubjects);
    subject.addEventListener('change', filterSubjects);
    filterSubjects();
});
</script>
</body>
</html>
