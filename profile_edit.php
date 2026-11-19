<?php
require_once __DIR__ . '/../auth.php';
require_login();
require_once __DIR__ . '/../koneksi.php';

$userId = (int) ($_SESSION['user']['id'] ?? 0);
$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');
$roleLabels = ['admin' => 'Administrator', 'guru' => 'Guru', 'siswa' => 'Siswa'];

$userStatement = mysqli_prepare($koneksi, 'SELECT username, role FROM users WHERE id = ? LIMIT 1');
if (!$userStatement) {
    die('Gagal memuat akun: ' . htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
}
mysqli_stmt_bind_param($userStatement, 'i', $userId);
if (!mysqli_stmt_execute($userStatement)) {
    die('Gagal memuat akun: ' . htmlspecialchars(mysqli_stmt_error($userStatement), ENT_QUOTES, 'UTF-8'));
}
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($userStatement));
mysqli_stmt_close($userStatement);

if (!$user || $user['role'] !== $role) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . app_url('login.php'));
    exit();
}

$profile = null;
$profileStatement = null;
if ($role === 'guru') {
    $profileStatement = mysqli_prepare($koneksi, 'SELECT nama, nip, jenis_kelamin FROM guru WHERE user_id = ? LIMIT 1');
} elseif ($role === 'siswa') {
    $profileStatement = mysqli_prepare($koneksi, 'SELECT nama, nis, kelas, jenis_kelamin FROM siswa WHERE user_id = ? LIMIT 1');
}

if ($profileStatement) {
    mysqli_stmt_bind_param($profileStatement, 'i', $userId);
    if (!mysqli_stmt_execute($profileStatement)) {
        die('Gagal memuat profil: ' . htmlspecialchars(mysqli_stmt_error($profileStatement), ENT_QUOTES, 'UTF-8'));
    }
    $profile = mysqli_fetch_assoc(mysqli_stmt_get_result($profileStatement));
    mysqli_stmt_close($profileStatement);
} elseif ($role !== 'admin') {
    die('Gagal menyiapkan data profil: ' . htmlspecialchars(mysqli_error($koneksi), ENT_QUOTES, 'UTF-8'));
}

$errorMessage = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_error']);
$oldInput = $_SESSION['profile_old_input'] ?? [];
unset($_SESSION['profile_old_input']);
$csrfToken = $_SESSION['profile_csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['profile_csrf_token'] = $csrfToken;
$displayName = $oldInput['nama'] ?? ($profile['nama'] ?? $user['username']);
$initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($displayName, 0, 1));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profil - Ruang Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    <main class="container profile-page profile-edit-page">
        <div class="profile-breadcrumb"><a href="<?= htmlspecialchars(app_url('Profile/profile.php'), ENT_QUOTES, 'UTF-8'); ?>">Profil saya</a><span aria-hidden="true">/</span><span>Edit profil</span></div>
        <section class="profile-edit-heading">
            <span class="profile-hero-avatar"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?></span>
            <div><span class="profile-eyebrow">PENGATURAN AKUN</span><h1>Edit profil</h1><p>Perbarui informasi akun dan data pribadi Anda.</p></div>
        </section>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger profile-flash" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($role !== 'admin' && !$profile): ?>
            <div class="profile-missing-notice">Profil Anda belum terhubung. Hubungi administrator untuk memperbarui data pribadi.</div>
        <?php else: ?>
            <form action="<?= htmlspecialchars(app_url('action/aksi_profile.php'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="profile-edit-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="role" value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
                <section class="profile-edit-card">
                    <header class="profile-card-heading">
                        <span class="profile-card-icon" aria-hidden="true">✦</span>
                        <div><h2>Informasi pribadi</h2><p>Pastikan informasi Anda ditulis dengan benar.</p></div>
                    </header>
                    <div class="profile-form-grid">
                        <div class="profile-field profile-field-full">
                            <label for="username">Username</label>
                            <input class="form-control" id="username" name="username" type="text" maxlength="50" autocomplete="username" value="<?= htmlspecialchars($oldInput['username'] ?? $user['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            <small>Username digunakan untuk masuk ke akun.</small>
                        </div>
                        <?php if ($role === 'guru'): ?>
                            <div class="profile-field">
                                <label for="nama">Nama lengkap</label>
                                <input class="form-control" id="nama" name="nama" type="text" maxlength="100" value="<?= htmlspecialchars($oldInput['nama'] ?? $profile['nama'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="profile-field">
                                <label for="nip">Nomor induk guru (NIP)</label>
                                <input class="form-control" id="nip" name="nip" type="text" maxlength="20" value="<?= htmlspecialchars($oldInput['nip'] ?? $profile['nip'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <fieldset class="profile-field profile-field-full">
                                <legend>Jenis kelamin</legend>
                                <div class="profile-radio-group">
                                    <label><input type="radio" name="jenis_kelamin" value="L" <?= ($oldInput['jenis_kelamin'] ?? $profile['jenis_kelamin']) === 'L' ? 'checked' : ''; ?> required> Laki-laki</label>
                                    <label><input type="radio" name="jenis_kelamin" value="P" <?= ($oldInput['jenis_kelamin'] ?? $profile['jenis_kelamin']) === 'P' ? 'checked' : ''; ?>> Perempuan</label>
                                </div>
                            </fieldset>
                        <?php elseif ($role === 'siswa'): ?>
                            <div class="profile-field">
                                <label for="nama">Nama lengkap</label>
                                <input class="form-control" id="nama" name="nama" type="text" maxlength="100" value="<?= htmlspecialchars($oldInput['nama'] ?? $profile['nama'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="profile-field">
                                <label for="nis">Nomor induk siswa (NIS)</label>
                                <input class="form-control" id="nis" name="nis" type="text" maxlength="10" value="<?= htmlspecialchars($oldInput['nis'] ?? $profile['nis'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="profile-field">
                                <label for="kelas">Kelas</label>
                                <input class="form-control" id="kelas" name="kelas" type="text" maxlength="10" value="<?= htmlspecialchars($oldInput['kelas'] ?? $profile['kelas'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <fieldset class="profile-field profile-field-full">
                                <legend>Jenis kelamin</legend>
                                <div class="profile-radio-group">
                                    <label><input type="radio" name="jenis_kelamin" value="L" <?= ($oldInput['jenis_kelamin'] ?? $profile['jenis_kelamin']) === 'L' ? 'checked' : ''; ?> required> Laki-laki</label>
                                    <label><input type="radio" name="jenis_kelamin" value="P" <?= ($oldInput['jenis_kelamin'] ?? $profile['jenis_kelamin']) === 'P' ? 'checked' : ''; ?>> Perempuan</label>
                                </div>
                            </fieldset>
                        <?php else: ?>
                            <div class="profile-admin-note profile-field-full">Sebagai administrator, Anda dapat mengubah username dan password akun di halaman ini.</div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="profile-edit-card" id="ubah-password">
                    <header class="profile-card-heading">
                        <span class="profile-card-icon profile-security-card-icon" aria-hidden="true">⌑</span>
                        <div><h2>Keamanan akun</h2><p>Kosongkan password baru jika tidak ingin menggantinya.</p></div>
                    </header>
                    <div class="profile-form-grid">
                        <div class="profile-field">
                            <label for="password_baru">Password baru</label>
                            <input class="form-control" id="password_baru" name="password_baru" type="password" minlength="8" maxlength="72" autocomplete="new-password">
                            <small>Minimal 8 karakter.</small>
                        </div>
                        <div class="profile-field">
                            <label for="konfirmasi_password">Konfirmasi password baru</label>
                            <input class="form-control" id="konfirmasi_password" name="konfirmasi_password" type="password" minlength="8" maxlength="72" autocomplete="new-password">
                            <small>Isi kembali password baru untuk konfirmasi.</small>
                        </div>
                    </div>
                </section>

                <div class="profile-form-actions">
                    <a href="<?= htmlspecialchars(app_url('Profile/profile.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                </div>
            </form>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>
