<?php
function mengajar_csrf_token(): string
{
    if (empty($_SESSION['mengajar_csrf_token'])) {
        $_SESSION['mengajar_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['mengajar_csrf_token'];
}

function mengajar_validate_csrf($token): bool
{
    return is_string($token)
        && isset($_SESSION['mengajar_csrf_token'])
        && hash_equals($_SESSION['mengajar_csrf_token'], $token);
}

function mengajar_fetch_options(mysqli $koneksi): array
{
    $queries = [
        'guru' => [
            "SELECT g.id, g.nama, g.nip
             FROM guru g
             INNER JOIN users u ON u.id = g.user_id AND u.role = 'guru'
             ORDER BY g.nama ASC",
            'Gagal memuat daftar guru',
        ],
        'mapel' => [
            "SELECT mp.id, mp.kode_mapel, mp.nama_mapel, mp.guru_id, g.nama AS nama_guru
             FROM mapel mp
             INNER JOIN guru g ON g.id = mp.guru_id
             INNER JOIN users u ON u.id = g.user_id AND u.role = 'guru'
             ORDER BY mp.nama_mapel ASC",
            'Gagal memuat mata pelajaran',
        ],
        'kelas' => [
            "SELECT DISTINCT TRIM(kelas) AS nama
             FROM siswa
             WHERE TRIM(kelas) <> ''
             ORDER BY nama ASC",
            'Gagal memuat daftar kelas',
        ],
    ];
    $options = [];

    foreach ($queries as $key => [$sql, $errorMessage]) {
        $result = mysqli_query($koneksi, $sql);
        if (!$result) {
            die($errorMessage . ': ' . htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
        }
        $options[$key] = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    return $options;
}

function mengajar_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function mengajar_redirect_error(string $message, array $values = [], string $location = 'mengajar/form_mengajar.php'): void
{
    $_SESSION['mengajar_error'] = $message;
    $_SESSION['mengajar_values'] = $values;
    header('Location: ' . app_url($location));
    exit();
}

function mengajar_prepare(mysqli $koneksi, string $sql, string $errorMessage): mysqli_stmt
{
    $statement = mysqli_prepare($koneksi, $sql);
    if (!$statement) {
        error_log($errorMessage . ': ' . mysqli_error($koneksi));
        http_response_code(500);
        die(htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'));
    }

    return $statement;
}
