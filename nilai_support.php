<?php
function nilai_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function nilai_csrf_token(): string
{
    if (empty($_SESSION['nilai_csrf_token'])) {
        $_SESSION['nilai_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['nilai_csrf_token'];
}

function nilai_validate_csrf($token): bool
{
    return is_string($token)
        && isset($_SESSION['nilai_csrf_token'])
        && hash_equals($_SESSION['nilai_csrf_token'], $token);
}

function nilai_prepare(mysqli $koneksi, string $sql, string $message): mysqli_stmt
{
    $statement = mysqli_prepare($koneksi, $sql);
    if (!$statement) {
        error_log($message . ': ' . mysqli_error($koneksi));
        http_response_code(500);
        die('Terjadi kesalahan saat menyiapkan data nilai.');
    }

    return $statement;
}

function nilai_execute(mysqli_stmt $statement, string $message): void
{
    if (!mysqli_stmt_execute($statement)) {
        error_log($message . ': ' . mysqli_stmt_error($statement));
        mysqli_stmt_close($statement);
        http_response_code(500);
        die('Terjadi kesalahan saat memproses data nilai.');
    }
}

function nilai_assignment_options(mysqli $koneksi, int $teacherUserId): array
{
    $sql = "
        SELECT DISTINCT s.id AS siswa_id, s.nis, s.nama AS nama_siswa, TRIM(s.kelas) AS kelas,
               mp.id AS mapel_id, mp.nama_mapel, mp.kode_mapel
        FROM mengajar mg
        INNER JOIN siswa s ON s.id = mg.siswa_id
        INNER JOIN mapel mp ON mp.id = mg.mapel_id
        INNER JOIN guru g ON g.id = mp.guru_id
        INNER JOIN users u ON u.id = g.user_id AND u.role = 'guru'
        WHERE g.user_id = ?
        ORDER BY kelas ASC, nama_siswa ASC, nama_mapel ASC
    ";
    $statement = nilai_prepare($koneksi, $sql, 'Could not prepare assigned student list');
    mysqli_stmt_bind_param($statement, 'i', $teacherUserId);
    nilai_execute($statement, 'Could not load assigned student list');
    $result = mysqli_stmt_get_result($statement);
    $options = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($statement);

    return $options;
}

function nilai_find_grade(mysqli $koneksi, int $gradeId, int $teacherUserId): ?array
{
    $sql = "
        SELECT nr.id, nr.siswa_id, nr.mapel_id, nr.nilai_kkm, nr.nilai_pengetahuan,
               nr.nilai_keterampilan, nr.keterangan, s.nis, s.nama AS nama_siswa,
               TRIM(s.kelas) AS kelas, mp.nama_mapel, mp.kode_mapel
        FROM nilai_rapor nr
        INNER JOIN siswa s ON s.id = nr.siswa_id
        INNER JOIN mapel mp ON mp.id = nr.mapel_id
        WHERE nr.id = ?
          AND EXISTS (
              SELECT 1
              FROM mengajar mg
              INNER JOIN mapel assigned_mapel ON assigned_mapel.id = mg.mapel_id
              INNER JOIN guru g ON g.id = assigned_mapel.guru_id
              INNER JOIN users u ON u.id = g.user_id AND u.role = 'guru'
              WHERE mg.siswa_id = nr.siswa_id
                AND mg.mapel_id = nr.mapel_id
                AND g.user_id = ?
          )
        LIMIT 1
    ";
    $statement = nilai_prepare($koneksi, $sql, 'Could not prepare grade lookup');
    mysqli_stmt_bind_param($statement, 'ii', $gradeId, $teacherUserId);
    nilai_execute($statement, 'Could not load grade record');
    $grade = mysqli_fetch_assoc(mysqli_stmt_get_result($statement)) ?: null;
    mysqli_stmt_close($statement);

    return $grade;
}

function nilai_redirect(string $mode = 'list', int $gradeId = 0): void
{
    $query = $mode === 'list' ? '' : '?mode=' . rawurlencode($mode);
    if ($gradeId > 0) {
        $query .= ($query === '' ? '?' : '&') . 'id=' . $gradeId;
    }

    header('Location: ' . app_url('Nilai/kelola_nilai.php' . $query));
    exit();
}
