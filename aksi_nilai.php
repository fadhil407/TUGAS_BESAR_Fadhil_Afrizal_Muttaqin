<?php
require_once __DIR__ . '/../auth.php';
require_role('guru');
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../Nilai/nilai_support.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    nilai_redirect();
}

if (!nilai_validate_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    die('Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.');
}

$action = $_POST['action'] ?? '';
if (!is_string($action) || !in_array($action, ['create', 'update', 'delete'], true)) {
    http_response_code(400);
    die('Tindakan nilai tidak valid.');
}

$teacherUserId = (int) ($_SESSION['user']['id'] ?? 0);
$gradeId = isset($_POST['id']) && is_scalar($_POST['id'])
    ? filter_var($_POST['id'], FILTER_VALIDATE_INT)
    : false;

if ($action === 'delete') {
    if (!$gradeId || $gradeId < 1) {
        $_SESSION['nilai_error'] = 'Data nilai yang dipilih tidak valid.';
        nilai_redirect();
    }

    $deleteSql = "
        DELETE FROM nilai_rapor
        WHERE id = ?
          AND EXISTS (
              SELECT 1
              FROM mengajar mg
              INNER JOIN mapel mp ON mp.id = mg.mapel_id
              INNER JOIN guru g ON g.id = mp.guru_id
              INNER JOIN users u ON u.id = g.user_id AND u.role = 'guru'
              WHERE mg.siswa_id = nilai_rapor.siswa_id
                AND mg.mapel_id = nilai_rapor.mapel_id
                AND g.user_id = ?
          )
    ";
    $statement = nilai_prepare($koneksi, $deleteSql, 'Could not prepare grade deletion');
    mysqli_stmt_bind_param($statement, 'ii', $gradeId, $teacherUserId);
    nilai_execute($statement, 'Could not delete grade record');
    $deleted = mysqli_stmt_affected_rows($statement) === 1;
    mysqli_stmt_close($statement);

    if (!$deleted) {
        $_SESSION['nilai_error'] = 'Nilai tidak ditemukan atau Anda tidak berwenang menghapusnya.';
        nilai_redirect();
    }

    $_SESSION['nilai_success'] = 'Nilai siswa berhasil dihapus.';
    nilai_redirect();
}

$mode = $action === 'update' ? 'edit' : 'create';
$formGradeId = $action === 'update' && $gradeId && $gradeId > 0 ? $gradeId : 0;
if ($action === 'update' && $formGradeId === 0) {
    $_SESSION['nilai_error'] = 'Data nilai yang dipilih tidak valid.';
    nilai_redirect();
}

if ($action === 'update' && !nilai_find_grade($koneksi, $formGradeId, $teacherUserId)) {
    $_SESSION['nilai_error'] = 'Nilai tidak ditemukan atau Anda tidak berwenang mengubahnya.';
    nilai_redirect();
}

$pairInput = $_POST['assignment'] ?? '';
$pair = is_string($pairInput) ? explode(':', $pairInput, 2) : [];
$studentId = count($pair) === 2 ? filter_var($pair[0], FILTER_VALIDATE_INT) : false;
$subjectId = count($pair) === 2 ? filter_var($pair[1], FILTER_VALIDATE_INT) : false;
$scoreKeys = ['nilai_kkm', 'nilai_pengetahuan', 'nilai_keterampilan'];
$scores = [];
$validScores = true;
foreach ($scoreKeys as $key) {
    $value = $_POST[$key] ?? null;
    $score = is_scalar($value) ? filter_var($value, FILTER_VALIDATE_INT) : false;
    if ($score === false || $score < 0 || $score > 100) {
        $validScores = false;
    }
    $scores[$key] = $score;
}

$commentInput = $_POST['keterangan'] ?? null;
$comment = is_string($commentInput) ? trim($commentInput) : '';
$commentLength = preg_match_all('/./us', $comment);
if (
    !$studentId || $studentId < 1
    || !$subjectId || $subjectId < 1
    || !$validScores
    || $comment === ''
    || $commentLength === false
    || $commentLength > 50
) {
    $_SESSION['nilai_error'] = 'Pilih siswa dan mata pelajaran yang valid, isi semua nilai antara 0–100, serta isi keterangan maksimal 50 karakter.';
    $_SESSION['nilai_form_values'] = $_POST;
    nilai_redirect($mode, $formGradeId);
}

$kkm = $scores['nilai_kkm'];
$knowledge = $scores['nilai_pengetahuan'];
$skill = $scores['nilai_keterampilan'];

if ($action === 'create') {
    $sql = "
        INSERT INTO nilai_rapor
            (siswa_id, mapel_id, nilai_kkm, nilai_pengetahuan, nilai_keterampilan, keterangan)
        SELECT ?, ?, ?, ?, ?, ?
        FROM DUAL
        WHERE EXISTS (
            SELECT 1
            FROM mengajar mg
            INNER JOIN mapel mp ON mp.id = mg.mapel_id
            INNER JOIN guru g ON g.id = mp.guru_id
            INNER JOIN users u ON u.id = g.user_id AND u.role = 'guru'
            WHERE mg.siswa_id = ? AND mg.mapel_id = ? AND g.user_id = ?
        )
    ";
    $statement = nilai_prepare($koneksi, $sql, 'Could not prepare grade creation');
    mysqli_stmt_bind_param(
        $statement,
        'iiiiisiii',
        $studentId,
        $subjectId,
        $kkm,
        $knowledge,
        $skill,
        $comment,
        $studentId,
        $subjectId,
        $teacherUserId
    );
    nilai_execute($statement, 'Could not create grade record');
    $saved = mysqli_stmt_affected_rows($statement) === 1;
    mysqli_stmt_close($statement);
} else {
    $sql = "
        UPDATE nilai_rapor
        SET siswa_id = ?, mapel_id = ?, nilai_kkm = ?, nilai_pengetahuan = ?,
            nilai_keterampilan = ?, keterangan = ?
        WHERE id = ?
          AND EXISTS (
              SELECT 1
              FROM mengajar mg
              INNER JOIN mapel mp ON mp.id = mg.mapel_id
              INNER JOIN guru g ON g.id = mp.guru_id
              INNER JOIN users u ON u.id = g.user_id AND u.role = 'guru'
              WHERE mg.siswa_id = ? AND mg.mapel_id = ? AND g.user_id = ?
          )
    ";
    $statement = nilai_prepare($koneksi, $sql, 'Could not prepare grade update');
    mysqli_stmt_bind_param(
        $statement,
        'iiiiisiiii',
        $studentId,
        $subjectId,
        $kkm,
        $knowledge,
        $skill,
        $comment,
        $formGradeId,
        $studentId,
        $subjectId,
        $teacherUserId
    );
    nilai_execute($statement, 'Could not update grade record');
    mysqli_stmt_close($statement);
    $updatedGrade = nilai_find_grade($koneksi, $formGradeId, $teacherUserId);
    $saved = $updatedGrade !== null
        && (int) $updatedGrade['siswa_id'] === $studentId
        && (int) $updatedGrade['mapel_id'] === $subjectId
        && (int) $updatedGrade['nilai_kkm'] === $kkm
        && (int) $updatedGrade['nilai_pengetahuan'] === $knowledge
        && (int) $updatedGrade['nilai_keterampilan'] === $skill
        && $updatedGrade['keterangan'] === $comment;
}

if (!$saved) {
    $_SESSION['nilai_error'] = 'Siswa atau mata pelajaran tidak termasuk penugasan Anda.';
    $_SESSION['nilai_form_values'] = $_POST;
    nilai_redirect($mode, $formGradeId);
}

unset($_SESSION['nilai_form_values']);
$_SESSION['nilai_success'] = $action === 'create'
    ? 'Nilai siswa berhasil ditambahkan.'
    : 'Nilai siswa berhasil diperbarui.';
nilai_redirect();
