<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../koneksi.php';

function registration_redirect(string $message, string $form = 'register', array $values = []): void
{
    $_SESSION['registration_error'] = $message;
    $_SESSION['registration_values'] = $values;
    $_SESSION['active_auth_form'] = $form;
    header('Location: ' . app_url('login.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('login.php'));
    exit();
}

$fields = ['nis', 'nama', 'kelas', 'jk', 'username', 'password'];
$input = [];
foreach ($fields as $field) {
    $value = $_POST[$field] ?? '';
    $input[$field] = is_string($value) ? trim($value) : '';
}

$nis = $input['nis'];
$nama = $input['nama'];
$kelas = $input['kelas'];
$jk = $input['jk'];
$username = $input['username'];
$password = $_POST['password'] ?? '';
$values = [
    'nis' => $nis,
    'nama' => $nama,
    'kelas' => $kelas,
    'jk' => $jk,
    'username' => $username,
];

if ($nis === '' || $nama === '' || $kelas === '' || $username === '' || !is_string($password) || $password === '') {
    registration_redirect('Semua kolom wajib diisi.', 'register', $values);
}
if (strlen($nis) > 10 || strlen($nama) > 100 || strlen($kelas) > 10 || strlen($username) > 50) {
    registration_redirect('Panjang NIS, nama, kelas, atau username melebihi batas.', 'register', $values);
}
if (!in_array($jk, ['L', 'P'], true)) {
    registration_redirect('Pilih jenis kelamin yang valid.', 'register', $values);
}
if (strlen($password) < 8 || strlen($password) > 72) {
    registration_redirect('Password harus terdiri dari 8 sampai 72 karakter.', 'register', $values);
}

$check = mysqli_prepare(
    $koneksi,
    'SELECT username FROM users WHERE username = ? UNION ALL SELECT nis FROM siswa WHERE nis = ? LIMIT 1'
);
if (!$check) {
    error_log('Could not prepare registration duplicate check: ' . mysqli_error($koneksi));
    registration_redirect('Pendaftaran belum dapat diproses. Silakan coba lagi.');
}
mysqli_stmt_bind_param($check, 'ss', $username, $nis);
if (!mysqli_stmt_execute($check)) {
    error_log('Registration duplicate check failed: ' . mysqli_stmt_error($check));
    mysqli_stmt_close($check);
    registration_redirect('Pendaftaran belum dapat diproses. Silakan coba lagi.');
}
$duplicate = mysqli_stmt_get_result($check);
$hasDuplicate = $duplicate && mysqli_num_rows($duplicate) > 0;
mysqli_stmt_close($check);
if ($hasDuplicate) {
    registration_redirect('Username atau NIS sudah terdaftar.', 'register', $values);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
if ($passwordHash === false) {
    error_log('Password hashing failed during student registration.');
    registration_redirect('Pendaftaran belum dapat diproses. Silakan coba lagi.');
}

if (!mysqli_begin_transaction($koneksi)) {
    error_log('Could not start student registration transaction: ' . mysqli_error($koneksi));
    registration_redirect('Pendaftaran belum dapat diproses. Silakan coba lagi.');
}

$userStatement = mysqli_prepare($koneksi, "INSERT INTO users (username, password, role, created_at) VALUES (?, ?, 'siswa', NOW())");
$studentStatement = mysqli_prepare($koneksi, 'INSERT INTO siswa (nis, nama, kelas, jenis_kelamin, user_id) VALUES (?, ?, ?, ?, ?)');
if (!$userStatement || !$studentStatement) {
    error_log('Could not prepare student registration: ' . mysqli_error($koneksi));
    mysqli_rollback($koneksi);
    registration_redirect('Pendaftaran belum dapat diproses. Silakan coba lagi.');
}

mysqli_stmt_bind_param($userStatement, 'ss', $username, $passwordHash);
$userSaved = mysqli_stmt_execute($userStatement);
if (!$userSaved) {
    error_log('Could not create student account: ' . mysqli_stmt_error($userStatement));
    mysqli_stmt_close($userStatement);
    mysqli_stmt_close($studentStatement);
    mysqli_rollback($koneksi);
    registration_redirect('Username atau NIS sudah terdaftar, atau pendaftaran gagal. Silakan periksa data Anda.', 'register', $values);
}

$userId = mysqli_insert_id($koneksi);
mysqli_stmt_bind_param($studentStatement, 'ssssi', $nis, $nama, $kelas, $jk, $userId);
if (!mysqli_stmt_execute($studentStatement)) {
    error_log('Could not save student profile: ' . mysqli_stmt_error($studentStatement));
    mysqli_stmt_close($userStatement);
    mysqli_stmt_close($studentStatement);
    mysqli_rollback($koneksi);
    registration_redirect('NIS sudah terdaftar atau data siswa gagal disimpan. Silakan periksa data Anda.', 'register', $values);
}

mysqli_stmt_close($userStatement);
mysqli_stmt_close($studentStatement);
if (!mysqli_commit($koneksi)) {
    error_log('Could not commit student registration: ' . mysqli_error($koneksi));
    mysqli_rollback($koneksi);
    registration_redirect('Pendaftaran belum dapat diproses. Silakan coba lagi.');
}

$_SESSION['registration_success'] = 'Pendaftaran berhasil. Silakan masuk menggunakan akun siswa Anda.';
$_SESSION['active_auth_form'] = 'login';
header('Location: ' . app_url('login.php'));
exit();
