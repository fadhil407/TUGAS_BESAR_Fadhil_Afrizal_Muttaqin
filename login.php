<?php
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['login'])) {
    redirect_to_role_dashboard();
}

$activeAuthForm = $_SESSION['active_auth_form'] ?? 'login';
unset($_SESSION['active_auth_form']);
$registrationError = $_SESSION['registration_error'] ?? '';
unset($_SESSION['registration_error']);
$registrationSuccess = $_SESSION['registration_success'] ?? '';
unset($_SESSION['registration_success']);
$registrationValues = $_SESSION['registration_values'] ?? [];
unset($_SESSION['registration_values']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk atau Daftar - Data Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body class="login-page">
    <main class="container">
        <section class="auth-card <?= $activeAuthForm === 'register' ? 'show-registration' : ''; ?>">
            <div class="auth-intro">
                <span class="auth-eyebrow">PORTAL DATA SEKOLAH</span>
                <h1>Belajar, tumbuh, <span>berprestasi.</span></h1>
                <p>Ruang digital sekolah untuk mendukung perjalanan belajar yang lebih teratur dan menyenangkan.</p>
                <div class="auth-decoration" aria-hidden="true"><span></span><span></span><span></span></div>
            </div>

            <div class="auth-panel">
                <div class="auth-tabs" role="tablist" aria-label="Akses akun">
                    <button class="auth-tab <?= $activeAuthForm === 'register' ? '' : 'active'; ?>" type="button" data-auth-tab="login" aria-selected="<?= $activeAuthForm === 'register' ? 'false' : 'true'; ?>">Masuk</button>
                    <button class="auth-tab <?= $activeAuthForm === 'register' ? 'active' : ''; ?>" type="button" data-auth-tab="register" aria-selected="<?= $activeAuthForm === 'register' ? 'true' : 'false'; ?>">Daftar siswa</button>
                </div>

                <div class="auth-form <?= $activeAuthForm === 'register' ? '' : 'active'; ?>" data-auth-panel="login">
                    <h2>Selamat datang</h2>
                    <p class="auth-subtitle">Masuk untuk melanjutkan ke portal sekolah.</p>
                    <?php if (isset($_SESSION['login_error'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($_SESSION['login_error'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <?php unset($_SESSION['login_error']); ?>
                    <?php endif; ?>
                    <?php if ($registrationSuccess !== ''): ?>
                        <div class="alert alert-success" role="status"><?= htmlspecialchars($registrationSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <form action="<?= htmlspecialchars(app_url('action/aksi_login.php'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="login_identity" autocomplete="off" autocapitalize="none" spellcheck="false" readonly required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="login_passphrase" autocomplete="new-password" readonly required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Masuk ke akun</button>
                    </form>
                    <p class="auth-footnote">Belum punya akun? <button class="auth-inline-link" type="button" data-auth-tab="register">Daftar sebagai siswa</button></p>
                </div>

                <div class="auth-form <?= $activeAuthForm === 'register' ? 'active' : ''; ?>" data-auth-panel="register">
                    <h2>Buat akun siswa</h2>
                    <p class="auth-subtitle">Isi data berikut untuk mendaftar ke portal sekolah.</p>
                    <?php if ($registrationError !== ''): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($registrationError, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <form action="<?= htmlspecialchars(app_url('action/aksi_registrasi.php'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="register-nis" class="form-label">NIS</label>
                                <input type="text" class="form-control" id="register-nis" name="nis" maxlength="10" value="<?= htmlspecialchars($registrationValues['nis'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-sm-6">
                                <label for="register-class" class="form-label">Kelas</label>
                                <input type="text" class="form-control" id="register-class" name="kelas" maxlength="10" placeholder="Contoh: XI RPL 1" value="<?= htmlspecialchars($registrationValues['kelas'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="register-name" class="form-label">Nama lengkap</label>
                                <input type="text" class="form-control" id="register-name" name="nama" maxlength="100" value="<?= htmlspecialchars($registrationValues['nama'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-12">
                                <span class="form-label d-block">Jenis kelamin</span>
                                <div class="d-flex gap-3">
                                    <label class="form-check-label"><input class="form-check-input me-1" type="radio" name="jk" value="L" <?= ($registrationValues['jk'] ?? '') === 'L' ? 'checked' : ''; ?> required> Laki-laki</label>
                                    <label class="form-check-label"><input class="form-check-input me-1" type="radio" name="jk" value="P" <?= ($registrationValues['jk'] ?? '') === 'P' ? 'checked' : ''; ?>> Perempuan</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="register-username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="register-username" name="username" maxlength="50" autocomplete="username" value="<?= htmlspecialchars($registrationValues['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="register-password" class="form-label">Password <span class="form-hint">(minimal 8 karakter)</span></label>
                                <input type="password" class="form-control" id="register-password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-4">Daftar akun sebagai siswa</button>
                    </form>
                    <p class="auth-footnote">Sudah punya akun? <button class="auth-inline-link" type="button" data-auth-tab="login">Masuk di sini</button></p>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const loginForm = document.querySelector('[data-auth-panel="login"] form');
        const loginInputs = loginForm.querySelectorAll('input');

        loginInputs.forEach(function (input) {
            input.addEventListener('focus', function () {
                input.value = '';
                input.readOnly = false;
            }, { once: true });
        });

        window.addEventListener('pageshow', function () {
            loginInputs.forEach(function (input) {
                input.value = '';
                input.readOnly = true;
            });
        });

        document.querySelectorAll('[data-auth-tab]').forEach(function (button) {
            button.addEventListener('click', function () {
                const target = button.dataset.authTab;
                const card = document.querySelector('.auth-card');
                card.classList.toggle('show-registration', target === 'register');
                document.querySelectorAll('.auth-tab').forEach(function (tab) {
                    const active = tab.dataset.authTab === target;
                    tab.classList.toggle('active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                document.querySelectorAll('[data-auth-panel]').forEach(function (panel) {
                    panel.classList.toggle('active', panel.dataset.authPanel === target);
                });
            });
        });
    </script>
</body>
</html>