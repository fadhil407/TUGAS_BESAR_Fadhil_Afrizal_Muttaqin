<?php
$currentRole = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');
$dashboardUrl = role_dashboard_url($currentRole);
$currentUsername = $_SESSION['username'] ?? ($_SESSION['user']['username'] ?? 'Akun');
$roleLabels = [
    'admin' => 'Administrator',
    'guru' => 'Guru',
    'siswa' => 'Siswa',
];
?>
<nav class="navbar navbar-expand-lg navbar-school">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand" href="<?= htmlspecialchars($dashboardUrl ?? 'login.php', ENT_QUOTES, 'UTF-8'); ?>">
            <span class="brand-mark" aria-hidden="true">S</span>
            <span class="brand-copy">
                <strong>Ruang Sekolah</strong>
                <small>Portal pendidikan</small>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#schoolNavbar" aria-controls="schoolNavbar" aria-expanded="false" aria-label="Buka menu navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="schoolNavbar">
            <div class="school-nav-links">
                <span class="nav-section-label">Menu utama</span>
                <a class="school-nav-link" href="<?= htmlspecialchars($dashboardUrl ?? 'login.php', ENT_QUOTES, 'UTF-8'); ?>">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z"/></svg>
                    Ringkasan
                </a>

                <?php if ($currentRole === 'guru'): ?>
                    <a class="school-nav-link" href="<?= htmlspecialchars(app_url('mengajar/kelola_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 5h16M4 12h16M4 19h10M8 3v4m8 3v4m-8 3v4"/></svg>
                        Penugasan saya
                    </a>
                    <a class="school-nav-link" href="<?= htmlspecialchars(app_url('Nilai/kelola_nilai.php'), ENT_QUOTES, 'UTF-8'); ?>">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2ZM8 7h8M8 11h8"/></svg>
                        Melihat nilai siswa
                    </a>
                    <a class="school-nav-link" href="<?= htmlspecialchars(app_url('Nilai/rekap_nilai.php'), ENT_QUOTES, 'UTF-8'); ?>">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 19V5m0 14h16M8 15l3-4 3 2 5-7"/></svg>
                        Rekap nilai
                    </a>
                <?php elseif ($currentRole === 'siswa'): ?>
                    <a class="school-nav-link" href="<?= htmlspecialchars(app_url('Nilai/nilai_siswa.php'), ENT_QUOTES, 'UTF-8'); ?>">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8M8 12h8m-8 4h5"/></svg>
                        Melihat Nilai Saya
                    </a>
                    <a class="school-nav-link" href="<?= htmlspecialchars(app_url('Nilai/rekap_nilai.php'), ENT_QUOTES, 'UTF-8'); ?>">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 19V5m0 14h16M8 15l3-4 3 2 5-7"/></svg>
                        Rekap nilai saya
                    </a>
                <?php endif; ?>

                <?php if ($currentRole === 'admin'): ?>
                    <div class="custom-dropdown school-nav-dropdown">
                        <button class="school-nav-link" type="button" data-dropdown-toggle="true" aria-expanded="false" aria-controls="manageMenu">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6m3-3h-6"/></svg>
                            Kelola data
                            <span class="nav-chevron" aria-hidden="true"></span>
                        </button>
                        <ul class="dropdown-menu custom-dropdown-menu" id="manageMenu">
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('tabel_user/daftar_users.php'), ENT_QUOTES, 'UTF-8'); ?>">Semua pengguna</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('tabel_user/daftar_user.php'), ENT_QUOTES, 'UTF-8'); ?>">Daftar akun</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('tabel_user/daftar_guru.php'), ENT_QUOTES, 'UTF-8'); ?>">Daftar guru</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('tabel_user/daftar_siswa.php'), ENT_QUOTES, 'UTF-8'); ?>">Daftar siswa</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('tabel_user/daftar_mapel.php'), ENT_QUOTES, 'UTF-8'); ?>">Mata pelajaran</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('mengajar/kelola_mengajar.php'), ENT_QUOTES, 'UTF-8'); ?>">Penugasan mengajar</a></li>
                        </ul>
                    </div>

                    <div class="custom-dropdown school-nav-dropdown">
                        <button class="school-nav-link" type="button" data-dropdown-toggle="true" aria-expanded="false" aria-controls="addMenu">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            Tambah baru
                            <span class="nav-chevron" aria-hidden="true"></span>
                        </button>
                        <ul class="dropdown-menu custom-dropdown-menu" id="addMenu">
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('Tambah_user/tambah_users.php'), ENT_QUOTES, 'UTF-8'); ?>">Buat akun pengguna</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('Tambah_user/tambah_guru.php'), ENT_QUOTES, 'UTF-8'); ?>">Tambah guru</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('Tambah_user/tambah_admin.php'), ENT_QUOTES, 'UTF-8'); ?>">Tambah admin</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('Tambah_user/tambah_siswa.php'), ENT_QUOTES, 'UTF-8'); ?>">Tambah siswa</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('Tambah_user/tambah_mapel.php'), ENT_QUOTES, 'UTF-8'); ?>">Tambah mata pelajaran</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <div class="custom-dropdown account-dropdown">
                <button class="account-trigger" type="button" data-dropdown-toggle="true" aria-expanded="false" aria-controls="accountMenu">
                    <span class="account-avatar" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr($currentUsername, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="account-copy">
                        <strong><?= htmlspecialchars($currentUsername, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?= htmlspecialchars($roleLabels[$currentRole] ?? 'Akun', ENT_QUOTES, 'UTF-8'); ?></small>
                    </span>
                    <span class="nav-chevron" aria-hidden="true"></span>
                </button>
                <ul class="dropdown-menu custom-dropdown-menu dropdown-menu-end" id="accountMenu">
                    <li class="account-menu-heading">
                        <strong><?= htmlspecialchars($currentUsername, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?= htmlspecialchars($roleLabels[$currentRole] ?? 'Akun', ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('Profile/profile.php'), ENT_QUOTES, 'UTF-8'); ?>">Profil saya</a></li>
                    <li><a class="dropdown-item" href="<?= htmlspecialchars(app_url('Profile/profile_edit.php'), ENT_QUOTES, 'UTF-8'); ?>">Edit profil</a></li>
                    <li><a class="dropdown-item" href="<?= htmlspecialchars($dashboardUrl ?? 'login.php', ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a></li>
                    <li><a class="dropdown-item account-logout" href="<?= htmlspecialchars(app_url('logout.php'), ENT_QUOTES, 'UTF-8'); ?>">Keluar dari akun</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggles = document.querySelectorAll('[data-dropdown-toggle="true"]');

        function closeMenus() {
            document.querySelectorAll('.custom-dropdown-menu.show').forEach(function (menu) {
                menu.classList.remove('show');
            });
            toggles.forEach(function (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            });
        }

        toggles.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                const menu = document.getElementById(button.getAttribute('aria-controls'));
                const shouldOpen = !menu.classList.contains('show');
                closeMenus();
                if (shouldOpen) {
                    menu.classList.add('show');
                    button.setAttribute('aria-expanded', 'true');
                }
            });
        });

        document.addEventListener('click', closeMenus);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenus();
            }
        });
    });
</script>
