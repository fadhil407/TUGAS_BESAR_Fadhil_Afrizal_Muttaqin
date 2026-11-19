<?php
require_once __DIR__ . '/../auth.php';
require_login();
require_once __DIR__ . '/../koneksi.php';

function profile_redirect_with_error(string $message, array $oldInput = []): void
{
    $_SESSION['profile_error'] = $message;
    $_SESSION['profile_old_input'] = $oldInput;
    header('Location: ' . app_url('Profile/profile_edit.php'));
    exit();
}

function profile_string_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('Profile/profile.php'));
    exit();
}

$userId = (int) ($_SESSION['user']['id'] ?? 0);
$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');
$postedRole = $_POST['role'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';
$savedToken = $_SESSION['profile_csrf_token'] ?? '';

if (!is_string($csrfToken) || $savedToken === '' || !hash_equals($savedToken, $csrfToken)) {
    profile_redirect_with_error('Permintaan tidak valid atau sudah kedaluwarsa. Silakan coba kembali.');
}
if (!is_string($postedRole) || $postedRole !== $role || !in_array($role, ['admin', 'guru', 'siswa'], true)) {
    profile_redirect_with_error('Peran akun tidak valid.');
}

$posted = [];
foreach (['username', 'nama', 'nip', 'nis', 'kelas', 'jenis_kelamin', 'password_baru', 'konfirmasi_password'] as $field) {
    $value = $_POST[$field] ?? '';
    $posted[$field] = is_string($value) ? trim($value) : '';
}
$oldInput = array_intersect_key($posted, array_flip(['username', 'nama', 'nip', 'nis', 'kelas', 'jenis_kelamin']));
$username = $posted['username'];
$newPassword = $_POST['password_baru'] ?? '';
$confirmPassword = $_POST['konfirmasi_password'] ?? '';

if ($username === '' || profile_string_length($username) > 50) {
    profile_redirect_with_error('Username wajib diisi dan maksimal 50 karakter.', $oldInput);
}
if (!is_string($newPassword) || !is_string($confirmPassword)) {
    profile_redirect_with_error('Password baru tidak valid.', $oldInput);
}
if (($newPassword !== '' || $confirmPassword !== '') && (strlen($newPassword) < 8 || strlen($newPassword) > 72)) {
    profile_redirect_with_error('Password baru harus terdiri dari 8 sampai 72 karakter.', $oldInput);
}
if ($newPassword !== $confirmPassword) {
    profile_redirect_with_error('Konfirmasi password baru tidak sama.', $oldInput);
}

$accountStatement = mysqli_prepare($koneksi, 'SELECT username FROM users WHERE id = ? LIMIT 1');
if (!$accountStatement) {
    error_log('Could not prepare profile account lookup: ' . mysqli_error($koneksi));
    profile_redirect_with_error('Profil tidak dapat diperbarui saat ini. Silakan coba lagi.', $oldInput);
}
mysqli_stmt_bind_param($accountStatement, 'i', $userId);
if (!mysqli_stmt_execute($accountStatement)) {
    error_log('Profile account lookup failed: ' . mysqli_stmt_error($accountStatement));
    mysqli_stmt_close($accountStatement);
    profile_redirect_with_error('Profil tidak dapat diperbarui saat ini. Silakan coba lagi.', $oldInput);
}
$accountResult = mysqli_stmt_get_result($accountStatement);
$account = $accountResult ? mysqli_fetch_assoc($accountResult) : null;
mysqli_stmt_close($accountStatement);
if (!$account) {
    profile_redirect_with_error('Akun tidak ditemukan. Silakan masuk kembali.');
}

$duplicateStatement = mysqli_prepare($koneksi, 'SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
if (!$duplicateStatement) {
    error_log('Could not prepare username duplicate check: ' . mysqli_error($koneksi));
    profile_redirect_with_error('Profil tidak dapat diperbarui saat ini. Silakan coba lagi.', $oldInput);
}
mysqli_stmt_bind_param($duplicateStatement, 'si', $username, $userId);
if (!mysqli_stmt_execute($duplicateStatement)) {
    error_log('Username duplicate check failed: ' . mysqli_stmt_error($duplicateStatement));
    mysqli_stmt_close($duplicateStatement);
    profile_redirect_with_error('Profil tidak dapat diperbarui saat ini. Silakan coba lagi.', $oldInput);
}
$duplicateResult = mysqli_stmt_get_result($duplicateStatement);
$usernameTaken = $duplicateResult && mysqli_num_rows($duplicateResult) > 0;
mysqli_stmt_close($duplicateStatement);
if ($usernameTaken) {
    profile_redirect_with_error('Username sudah digunakan oleh akun lain.', $oldInput);
}

$profileFields = [];
$profileTable = '';
$uniqueColumn = '';
$uniqueValue = '';
if ($role === 'guru') {
    $profileTable = 'guru';
    $uniqueColumn = 'nip';
    $uniqueValue = $posted['nip'];
    $profileFields = [
        'nama' => $posted['nama'],
        'nip' => $posted['nip'],
        'jenis_kelamin' => $posted['jenis_kelamin'],
    ];
    if ($profileFields['nama'] === '' || profile_string_length($profileFields['nama']) > 100 || $profileFields['nip'] === '' || profile_string_length($profileFields['nip']) > 20 || !in_array($profileFields['jenis_kelamin'], ['L', 'P'], true)) {
        profile_redirect_with_error('Lengkapi nama, NIP, dan jenis kelamin dengan benar.', $oldInput);
    }
} elseif ($role === 'siswa') {
    $profileTable = 'siswa';
    $uniqueColumn = 'nis';
    $uniqueValue = $posted['nis'];
    $profileFields = [
        'nama' => $posted['nama'],
        'nis' => $posted['nis'],
        'kelas' => $posted['kelas'],
        'jenis_kelamin' => $posted['jenis_kelamin'],
    ];
    if ($profileFields['nama'] === '' || profile_string_length($profileFields['nama']) > 100 || $profileFields['nis'] === '' || profile_string_length($profileFields['nis']) > 10 || $profileFields['kelas'] === '' || profile_string_length($profileFields['kelas']) > 10 || !in_array($profileFields['jenis_kelamin'], ['L', 'P'], true)) {
        profile_redirect_with_error('Lengkapi nama, NIS, kelas, dan jenis kelamin dengan benar.', $oldInput);
    }
}

if ($role !== 'admin') {
    $uniqueStatement = mysqli_prepare($koneksi, "SELECT id FROM `$profileTable` WHERE `$uniqueColumn` = ? AND user_id <> ? LIMIT 1");
    if (!$uniqueStatement) {
        error_log('Could not prepare profile identifier duplicate check: ' . mysqli_error($koneksi));
        profile_redirect_with_error('Profil tidak dapat diperbarui saat ini. Silakan coba lagi.', $oldInput);
    }
    mysqli_stmt_bind_param($uniqueStatement, 'si', $uniqueValue, $userId);
    if (!mysqli_stmt_execute($uniqueStatement)) {
        error_log('Profile identifier duplicate check failed: ' . mysqli_stmt_error($uniqueStatement));
        mysqli_stmt_close($uniqueStatement);
        profile_redirect_with_error('Profil tidak dapat diperbarui saat ini. Silakan coba lagi.', $oldInput);
    }
    $uniqueResult = mysqli_stmt_get_result($uniqueStatement);
    $identifierTaken = $uniqueResult && mysqli_num_rows($uniqueResult) > 0;
    mysqli_stmt_close($uniqueStatement);
    if ($identifierTaken) {
        profile_redirect_with_error(($role === 'guru' ? 'NIP' : 'NIS') . ' sudah digunakan oleh akun lain.', $oldInput);
    }

    $profileExistsStatement = mysqli_prepare($koneksi, "SELECT id FROM `$profileTable` WHERE user_id = ? LIMIT 1");
    if (!$profileExistsStatement) {
        error_log('Could not prepare profile existence check: ' . mysqli_error($koneksi));
        profile_redirect_with_error('Profil tidak dapat diperbarui saat ini. Silakan coba lagi.', $oldInput);
    }
    mysqli_stmt_bind_param($profileExistsStatement, 'i', $userId);
    if (!mysqli_stmt_execute($profileExistsStatement)) {
        error_log('Profile existence check failed: ' . mysqli_stmt_error($profileExistsStatement));
        mysqli_stmt_close($profileExistsStatement);
        profile_redirect_with_error('Profil tidak dapat diperbarui saat ini. Silakan coba lagi.', $oldInput);
    }
    $existsResult = mysqli_stmt_get_result($profileExistsStatement);
    $profileExists = $existsResult && mysqli_num_rows($existsResult) > 0;
    mysqli_stmt_close($profileExistsStatement);
    if (!$profileExists) {
        profile_redirect_with_error('Data profil belum terhubung ke akun. Hubungi administrator.');
    }
}

$passwordHash = null;
if ($newPassword !== '') {
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        error_log('Password hashing failed during profile update.');
        profile_redirect_with_error('Profil tidak dapat diperbarui saat ini. Silakan coba lagi.', $oldInput);
    }
}

if (!mysqli_begin_transaction($koneksi)) {
    error_log('Could not start profile update transaction: ' . mysqli_error($koneksi));
    profile_redirect_with_error('Profil tidak dapat diperbarui saat ini. Silakan coba lagi.', $oldInput);
}

if ($passwordHash !== null) {
    $userUpdate = mysqli_prepare($koneksi, 'UPDATE users SET username = ?, password = ? WHERE id = ?');
    if ($userUpdate) {
        mysqli_stmt_bind_param($userUpdate, 'ssi', $username, $passwordHash, $userId);
    }
} else {
    $userUpdate = mysqli_prepare($koneksi, 'UPDATE users SET username = ? WHERE id = ?');
    if ($userUpdate) {
        mysqli_stmt_bind_param($userUpdate, 'si', $username, $userId);
    }
}

if (!$userUpdate || !mysqli_stmt_execute($userUpdate)) {
    error_log('Profile account update failed: ' . ($userUpdate ? mysqli_stmt_error($userUpdate) : mysqli_error($koneksi)));
    if ($userUpdate) {
        mysqli_stmt_close($userUpdate);
    }
    mysqli_rollback($koneksi);
    profile_redirect_with_error('Username gagal diperbarui. Pastikan username belum digunakan.', $oldInput);
}
mysqli_stmt_close($userUpdate);

if ($role === 'guru') {
    $profileUpdate = mysqli_prepare($koneksi, 'UPDATE guru SET nama = ?, nip = ?, jenis_kelamin = ? WHERE user_id = ?');
    if ($profileUpdate) {
        mysqli_stmt_bind_param($profileUpdate, 'sssi', $profileFields['nama'], $profileFields['nip'], $profileFields['jenis_kelamin'], $userId);
    }
} elseif ($role === 'siswa') {
    $profileUpdate = mysqli_prepare($koneksi, 'UPDATE siswa SET nama = ?, nis = ?, kelas = ?, jenis_kelamin = ? WHERE user_id = ?');
    if ($profileUpdate) {
        mysqli_stmt_bind_param($profileUpdate, 'ssssi', $profileFields['nama'], $profileFields['nis'], $profileFields['kelas'], $profileFields['jenis_kelamin'], $userId);
    }
} else {
    $profileUpdate = null;
}

if ($role !== 'admin' && (!$profileUpdate || !mysqli_stmt_execute($profileUpdate))) {
    error_log('Profile details update failed: ' . ($profileUpdate ? mysqli_stmt_error($profileUpdate) : mysqli_error($koneksi)));
    if ($profileUpdate) {
        mysqli_stmt_close($profileUpdate);
    }
    mysqli_rollback($koneksi);
    profile_redirect_with_error('Data pribadi gagal diperbarui. Silakan coba lagi.', $oldInput);
}
if ($profileUpdate) {
    mysqli_stmt_close($profileUpdate);
}

if (!mysqli_commit($koneksi)) {
    error_log('Could not commit profile update transaction: ' . mysqli_error($koneksi));
    mysqli_rollback($koneksi);
    profile_redirect_with_error('Perubahan profil belum dapat disimpan. Silakan coba lagi.', $oldInput);
}

$_SESSION['user']['username'] = $username;
$_SESSION['username'] = $username;
unset($_SESSION['profile_csrf_token'], $_SESSION['profile_old_input']);
$_SESSION['profile_success'] = 'Profil berhasil diperbarui.';
header('Location: ' . app_url('Profile/profile.php'));
exit();
