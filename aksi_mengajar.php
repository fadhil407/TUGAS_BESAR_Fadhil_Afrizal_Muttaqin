<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../mengajar/mengajar_support.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('mengajar/kelola_mengajar.php'));
    exit();
}
if (!mengajar_validate_csrf($_POST['csrf_token'] ?? null)) {
    mengajar_redirect_error('Sesi formulir berakhir. Muat ulang halaman lalu coba lagi.');
}

$action = mengajar_post_string('action');
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!in_array($action, ['create', 'update', 'delete'], true)) {
    mengajar_redirect_error('Tindakan yang diminta tidak valid.');
}
if (($action === 'update' || $action === 'delete') && (!$id || $id < 1)) {
    mengajar_redirect_error('Penugasan yang dipilih tidak valid.');
}

$values = [
    'guru_id' => mengajar_post_string('guru_id'),
    'mapel_id' => mengajar_post_string('mapel_id'),
    'nama_mapel_baru' => mengajar_post_string('nama_mapel_baru'),
    'kelas' => mengajar_post_string('kelas'),
];
$redirectLocation = $action === 'update'
    ? 'Edit/edit_mengajar.php?id=' . (int) $id
    : 'mengajar/form_mengajar.php';

if (!in_array($action, ['create', 'update'], true)) {
    $existingStatement = mengajar_prepare(
        $koneksi,
        'SELECT m.mapel_id, TRIM(s.kelas) AS kelas
         FROM mengajar m
         INNER JOIN siswa s ON s.id = m.siswa_id
         WHERE m.id = ?
         LIMIT 1',
        'Penugasan gagal disiapkan untuk dihapus'
    );
    mysqli_stmt_bind_param($existingStatement, 'i', $id);
    if (!mysqli_stmt_execute($existingStatement)) {
        error_log('Assignment delete lookup failed: ' . mysqli_stmt_error($existingStatement));
        mysqli_stmt_close($existingStatement);
        mengajar_redirect_error('Penugasan tidak dapat dihapus saat ini.');
    }
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($existingStatement));
    mysqli_stmt_close($existingStatement);
    if (!$existing) {
        mengajar_redirect_error('Penugasan tidak ditemukan.');
    }

    $deleteStatement = mengajar_prepare(
        $koneksi,
        'DELETE m
         FROM mengajar m
         INNER JOIN siswa s ON s.id = m.siswa_id
         WHERE m.mapel_id = ? AND TRIM(s.kelas) = ?',
        'Penugasan gagal dihapus'
    );
    mysqli_stmt_bind_param($deleteStatement, 'is', $existing['mapel_id'], $existing['kelas']);
    if (!mysqli_stmt_execute($deleteStatement)) {
        error_log('Assignment delete failed: ' . mysqli_stmt_error($deleteStatement));
        mysqli_stmt_close($deleteStatement);
        mengajar_redirect_error('Penugasan gagal dihapus. Data tersebut mungkin masih digunakan.');
    }
    $deletedRows = mysqli_stmt_affected_rows($deleteStatement);
    mysqli_stmt_close($deleteStatement);
    if ($deletedRows < 1) {
        mengajar_redirect_error('Penugasan tidak ditemukan atau sudah dihapus.');
    }
    $_SESSION['mengajar_success'] = 'Penugasan guru, kelas, dan mata pelajaran berhasil dihapus.';
    header('Location: ' . app_url('mengajar/kelola_mengajar.php'));
    exit();
}

$guruId = filter_var($values['guru_id'], FILTER_VALIDATE_INT);
$isNewSubject = $values['mapel_id'] === '__new__';
$mapelId = $isNewSubject ? 0 : filter_var($values['mapel_id'], FILTER_VALIDATE_INT);
$subjectNameLength = preg_match_all('/./us', $values['nama_mapel_baru']);
if (!$guruId || $guruId < 1 || $values['kelas'] === '') {
    mengajar_redirect_error('Guru, mata pelajaran, dan kelas wajib diisi.', $values, $redirectLocation);
}
if ($isNewSubject && (
    $values['nama_mapel_baru'] === ''
    || $subjectNameLength === false
    || $subjectNameLength > 100
)) {
    mengajar_redirect_error('Nama mata pelajaran baru wajib diisi dan maksimal 100 karakter.', $values, $redirectLocation);
}
if (!$isNewSubject && (!$mapelId || $mapelId < 1)) {
    mengajar_redirect_error('Pilih mata pelajaran yang tersedia atau pilih opsi untuk mengetik mata pelajaran baru.', $values, $redirectLocation);
}
if (strlen($values['kelas']) > 10) {
    mengajar_redirect_error('Nama kelas melebihi batas 10 karakter.', $values, $redirectLocation);
}

$teacherCheck = mengajar_prepare(
    $koneksi,
    "SELECT g.id
     FROM guru g
     INNER JOIN users u ON u.id = g.user_id AND u.role = 'guru'
     WHERE g.id = ?
     LIMIT 1",
    'Guru tidak dapat divalidasi'
);
mysqli_stmt_bind_param($teacherCheck, 'i', $guruId);
if (!mysqli_stmt_execute($teacherCheck)) {
    error_log('Assignment teacher validation failed: ' . mysqli_stmt_error($teacherCheck));
    mysqli_stmt_close($teacherCheck);
    mengajar_redirect_error('Guru tidak dapat divalidasi. Silakan coba lagi.', $values, $redirectLocation);
}
$validTeacher = mysqli_fetch_assoc(mysqli_stmt_get_result($teacherCheck));
mysqli_stmt_close($teacherCheck);
if (!$validTeacher) {
    mengajar_redirect_error('Guru yang dipilih tidak tersedia.', $values, $redirectLocation);
}

if (!$isNewSubject) {
    $subjectCheck = mengajar_prepare(
        $koneksi,
        'SELECT id
         FROM mapel
         WHERE id = ? AND guru_id = ?
         LIMIT 1',
        'Mata pelajaran tidak dapat divalidasi'
    );
    mysqli_stmt_bind_param($subjectCheck, 'ii', $mapelId, $guruId);
    if (!mysqli_stmt_execute($subjectCheck)) {
        error_log('Assignment subject validation failed: ' . mysqli_stmt_error($subjectCheck));
        mysqli_stmt_close($subjectCheck);
        mengajar_redirect_error('Mata pelajaran tidak dapat divalidasi. Silakan coba lagi.', $values, $redirectLocation);
    }
    $validSubject = mysqli_fetch_assoc(mysqli_stmt_get_result($subjectCheck));
    mysqli_stmt_close($subjectCheck);
    if (!$validSubject) {
        mengajar_redirect_error('Mata pelajaran tersebut tidak terhubung ke guru yang dipilih.', $values, $redirectLocation);
    }
}

$classCheck = mengajar_prepare(
    $koneksi,
    "SELECT id FROM siswa WHERE TRIM(kelas) = ? LIMIT 1",
    'Kelas tidak dapat divalidasi'
);
mysqli_stmt_bind_param($classCheck, 's', $values['kelas']);
if (!mysqli_stmt_execute($classCheck)) {
    error_log('Assignment class validation failed: ' . mysqli_stmt_error($classCheck));
    mysqli_stmt_close($classCheck);
    mengajar_redirect_error('Kelas tidak dapat divalidasi. Silakan coba lagi.', $values, $redirectLocation);
}
$validClass = mysqli_fetch_assoc(mysqli_stmt_get_result($classCheck));
mysqli_stmt_close($classCheck);
if (!$validClass) {
    mengajar_redirect_error('Kelas yang dipilih tidak tersedia atau belum memiliki siswa.', $values, $redirectLocation);
}

if (!mysqli_begin_transaction($koneksi)) {
    error_log('Could not start assignment transaction: ' . mysqli_error($koneksi));
    mengajar_redirect_error('Penugasan tidak dapat disimpan saat ini. Silakan coba lagi.', $values, $redirectLocation);
}

if ($isNewSubject) {
    $findSubjectStatement = mengajar_prepare(
        $koneksi,
        'SELECT id
         FROM mapel
         WHERE guru_id = ? AND LOWER(TRIM(nama_mapel)) = LOWER(?)
         ORDER BY id
         LIMIT 1
         FOR UPDATE',
        'Mata pelajaran tidak dapat diperiksa'
    );
    mysqli_stmt_bind_param($findSubjectStatement, 'is', $guruId, $values['nama_mapel_baru']);
    if (!mysqli_stmt_execute($findSubjectStatement)) {
        error_log('New assignment subject lookup failed: ' . mysqli_stmt_error($findSubjectStatement));
        mysqli_stmt_close($findSubjectStatement);
        mysqli_rollback($koneksi);
        mengajar_redirect_error('Mata pelajaran tidak dapat diperiksa saat ini.', $values, $redirectLocation);
    }
    $existingSubject = mysqli_fetch_assoc(mysqli_stmt_get_result($findSubjectStatement));
    mysqli_stmt_close($findSubjectStatement);

    if ($existingSubject) {
        $mapelId = (int) $existingSubject['id'];
    } else {
        $insertSubjectStatement = mengajar_prepare(
            $koneksi,
            'INSERT INTO mapel (kode_mapel, nama_mapel, guru_id) VALUES (?, ?, ?)',
            'Mata pelajaran baru tidak dapat disimpan'
        );
        $emptySubjectCode = '';
        mysqli_stmt_bind_param(
            $insertSubjectStatement,
            'ssi',
            $emptySubjectCode,
            $values['nama_mapel_baru'],
            $guruId
        );
        if (!mysqli_stmt_execute($insertSubjectStatement)) {
            error_log('New assignment subject insert failed: ' . mysqli_stmt_error($insertSubjectStatement));
            mysqli_stmt_close($insertSubjectStatement);
            mysqli_rollback($koneksi);
            mengajar_redirect_error('Mata pelajaran baru gagal disimpan.', $values, $redirectLocation);
        }
        $mapelId = (int) mysqli_insert_id($koneksi);
        mysqli_stmt_close($insertSubjectStatement);
        if ($mapelId < 1) {
            mysqli_rollback($koneksi);
            mengajar_redirect_error('Mata pelajaran baru gagal disimpan.', $values, $redirectLocation);
        }
    }
}

$oldGroup = null;
if ($action === 'update') {
    $oldGroupStatement = mengajar_prepare(
        $koneksi,
        "SELECT MIN(m.id) AS id, m.mapel_id, TRIM(s.kelas) AS kelas
         FROM mengajar m
         INNER JOIN siswa s ON s.id = m.siswa_id
         WHERE m.id = ?
         GROUP BY m.mapel_id, TRIM(s.kelas)
         LIMIT 1",
        'Penugasan tidak dapat diperbarui'
    );
    mysqli_stmt_bind_param($oldGroupStatement, 'i', $id);
    if (!mysqli_stmt_execute($oldGroupStatement)) {
        error_log('Assignment update lookup failed: ' . mysqli_stmt_error($oldGroupStatement));
        mysqli_stmt_close($oldGroupStatement);
        mysqli_rollback($koneksi);
        mengajar_redirect_error('Penugasan tidak dapat diperbarui saat ini.', $values, $redirectLocation);
    }
    $oldGroup = mysqli_fetch_assoc(mysqli_stmt_get_result($oldGroupStatement));
    mysqli_stmt_close($oldGroupStatement);
    if (!$oldGroup || (int) $oldGroup['id'] !== $id) {
        mysqli_rollback($koneksi);
        mengajar_redirect_error('Penugasan tidak ditemukan.', [], 'mengajar/kelola_mengajar.php');
    }
}

$targetGroupStatement = mengajar_prepare(
    $koneksi,
    'SELECT m.id
     FROM mengajar m
     INNER JOIN siswa s ON s.id = m.siswa_id
     WHERE m.mapel_id = ? AND TRIM(s.kelas) = ?
     LIMIT 1
     FOR UPDATE',
    'Penugasan tidak dapat divalidasi'
);
mysqli_stmt_bind_param($targetGroupStatement, 'is', $mapelId, $values['kelas']);
if (!mysqli_stmt_execute($targetGroupStatement)) {
    error_log('Assignment duplicate check failed: ' . mysqli_stmt_error($targetGroupStatement));
    mysqli_stmt_close($targetGroupStatement);
    mysqli_rollback($koneksi);
    mengajar_redirect_error('Penugasan tidak dapat divalidasi. Silakan coba lagi.', $values, $redirectLocation);
}
$targetGroupExists = mysqli_fetch_assoc(mysqli_stmt_get_result($targetGroupStatement)) !== null;
mysqli_stmt_close($targetGroupStatement);

$sameGroup = $action === 'update'
    && (int) $oldGroup['mapel_id'] === $mapelId
    && $oldGroup['kelas'] === $values['kelas'];
if ($targetGroupExists && !$sameGroup) {
    mysqli_rollback($koneksi);
    mengajar_redirect_error('Penugasan untuk mata pelajaran dan kelas tersebut sudah tersedia.', $values, $redirectLocation);
}

if (!$sameGroup) {
    if ($action === 'update') {
        $removeOldGroup = mengajar_prepare(
            $koneksi,
            'DELETE m
             FROM mengajar m
             INNER JOIN siswa s ON s.id = m.siswa_id
             WHERE m.mapel_id = ? AND TRIM(s.kelas) = ?',
            'Penugasan lama gagal diperbarui'
        );
        mysqli_stmt_bind_param($removeOldGroup, 'is', $oldGroup['mapel_id'], $oldGroup['kelas']);
        if (!mysqli_stmt_execute($removeOldGroup)) {
            error_log('Old assignment group deletion failed: ' . mysqli_stmt_error($removeOldGroup));
            mysqli_stmt_close($removeOldGroup);
            mysqli_rollback($koneksi);
            mengajar_redirect_error('Penugasan lama gagal diperbarui.', $values, $redirectLocation);
        }
        mysqli_stmt_close($removeOldGroup);
    }

    $saveStatement = mengajar_prepare(
        $koneksi,
        'INSERT INTO mengajar (mapel_id, siswa_id)
         SELECT ?, s.id
         FROM siswa s
         WHERE TRIM(s.kelas) = ?',
        'Penugasan gagal disimpan'
    );
    mysqli_stmt_bind_param($saveStatement, 'is', $mapelId, $values['kelas']);
    if (!mysqli_stmt_execute($saveStatement) || mysqli_stmt_affected_rows($saveStatement) < 1) {
        error_log('Assignment group insert failed: ' . mysqli_stmt_error($saveStatement));
        mysqli_stmt_close($saveStatement);
        mysqli_rollback($koneksi);
        mengajar_redirect_error('Penugasan gagal disimpan untuk siswa pada kelas tersebut.', $values, $redirectLocation);
    }
    mysqli_stmt_close($saveStatement);
}

if (!mysqli_commit($koneksi)) {
    error_log('Could not commit assignment transaction: ' . mysqli_error($koneksi));
    mysqli_rollback($koneksi);
    mengajar_redirect_error('Penugasan belum dapat disimpan. Silakan coba lagi.', $values, $redirectLocation);
}

unset($_SESSION['mengajar_values']);
$_SESSION['mengajar_success'] = $action === 'update' ? 'Penugasan guru mengajar berhasil diperbarui.' : 'Penugasan guru mengajar berhasil ditambahkan.';
header('Location: ' . app_url('mengajar/kelola_mengajar.php'));
exit();
