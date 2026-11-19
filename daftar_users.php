<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$totalUsers = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM users"));
$totalUsers = (int) $totalUsers['total'];
$totalPages = (int) ceil($totalUsers / $limit);

$roleCounts = ['admin' => 0, 'guru' => 0, 'siswa' => 0];
$roleQuery = mysqli_query($koneksi, 'SELECT role, COUNT(*) AS total FROM users GROUP BY role');
if (!$roleQuery) {
    die("Query Error: " . mysqli_error($koneksi));
}
while ($roleCount = mysqli_fetch_assoc($roleQuery)) {
    if (isset($roleCounts[$roleCount['role']])) {
        $roleCounts[$roleCount['role']] = (int) $roleCount['total'];
    }
}

$query = "
    SELECT u.*, COALESCE(g.nama, s.nama, '-') AS nama_lengkap
    FROM users u
    LEFT JOIN guru g ON g.user_id = u.id
    LEFT JOIN siswa s ON s.user_id = u.id
    ORDER BY u.id ASC
    LIMIT $limit OFFSET $offset
";
$users = mysqli_query($koneksi, $query);

if (!$users) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>

<main class="container users-page py-4 py-lg-5">
    <header class="users-heading">
        <div>
            <span class="users-eyebrow">PENGELOLAAN SEKOLAH</span>
            <h1>Daftar pengguna</h1>
            <p>Kelola akun dan informasi pengguna sekolah dari satu tempat.</p>
        </div>
        <a href="<?= htmlspecialchars(app_url('Tambah_user/tambah_users.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary users-add-button">
            <span aria-hidden="true">+</span>
            Tambah pengguna
        </a>
    </header>

    <section class="users-stats" aria-label="Ringkasan pengguna">
        <article class="users-stat users-stat-total">
            <span class="users-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <span class="users-stat-label">Semua akun</span>
            <strong><?= $totalUsers; ?></strong>
        </article>
        <article class="users-stat users-stat-admin">
            <span class="users-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>
            </span>
            <span class="users-stat-label">Admin</span>
            <strong><?= $roleCounts['admin']; ?></strong>
        </article>
        <article class="users-stat users-stat-guru">
            <span class="users-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M2 3h20v14H2zM8 21h8M12 17v4M7 8h10M7 12h6"/></svg>
            </span>
            <span class="users-stat-label">Guru</span>
            <strong><?= $roleCounts['guru']; ?></strong>
        </article>
        <article class="users-stat users-stat-siswa">
            <span class="users-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="m2 10 10-5 10 5-10 5-10-5Z"/><path d="M6 12v5c3.5 3 8.5 3 12 0v-5M22 10v6"/></svg>
            </span>
            <span class="users-stat-label">Siswa</span>
            <strong><?= $roleCounts['siswa']; ?></strong>
        </article>
    </section>

    <section class="users-table-card">
        <div class="users-table-heading">
            <div>
                <h2>Data pengguna</h2>
                <p>Informasi akun, peran, dan tindakan pengelolaan.</p>
            </div>
            <span class="users-total-chip"><?= $totalUsers; ?> akun terdaftar</span>
        </div>
        <div class="table-responsive users-table-wrap">
            <table class="table table-hover align-middle users-table">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" style="width: 7%;">No.</th>
                            <th scope="col">Pengguna</th>
                            <th scope="col">Peran</th>
                            <th scope="col">Status akun</th>
                            <th scope="col" style="width: 18%;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = $offset + 1;
                    while ($user = mysqli_fetch_assoc($users)) {
                        $nama = $user['nama_lengkap'] ?? '-';
                        $role = $user['role'];
                        $displayName = $nama !== '-' ? $nama : $user['username'];
                        $roleClass = in_array($role, ['admin', 'guru', 'siswa'], true) ? $role : 'other';
                    ?>
                        <tr>
                            <td><span class="users-row-number"><?= $no++; ?></span></td>
                            <td>
                                <div class="users-identity">
                                    <span class="users-avatar users-avatar-<?= htmlspecialchars($roleClass, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
                                        <?= htmlspecialchars(strtoupper(substr($displayName, 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <span class="users-identity-copy">
                                        <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span>@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="users-role users-role-<?= htmlspecialchars($roleClass, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span aria-hidden="true"></span>
                                    <?= htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="users-account-status"><span aria-hidden="true"></span>Aktif</span>
                            </td>
                            <td>
                                <div class="users-actions">
                                    <a href="<?= htmlspecialchars(app_url('Edit/edit_user.php?id=' . (int) $user['id'] . '&role=' . urlencode($role)), ENT_QUOTES, 'UTF-8'); ?>" class="users-action users-action-edit" aria-label="Edit akun <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m16 4 4 4M4 20l4-.8L19 8a2.12 2.12 0 0 0-3-3L5 16l-1 4Z"/></svg>
                                        Edit
                                    </a>
                                    <a href="<?= htmlspecialchars(app_url('Delete/hapus.php?id=' . (int) $user['id']), ENT_QUOTES, 'UTF-8'); ?>" class="users-action users-action-delete" aria-label="Hapus akun <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">
                                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/></svg>
                                        Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if ($totalUsers === 0): ?>
                        <tr>
                            <td colspan="5">
                                <div class="users-empty-state">
                                    <span aria-hidden="true">＋</span>
                                    <strong>Belum ada pengguna</strong>
                                    <p>Tambahkan akun pertama untuk mulai mengelola data sekolah.</p>
                                    <a href="<?= htmlspecialchars(app_url('Tambah_user/tambah_users.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Tambah pengguna</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <div class="users-pagination">
                        <div class="users-pagination-copy">
                            Menampilkan <strong><?= min($totalUsers, $offset + 1); ?>–<?= min($totalUsers, $offset + $limit); ?></strong> dari <strong><?= $totalUsers; ?></strong> pengguna
                        </div>

                        <nav aria-label="Navigasi halaman pengguna">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= max(1, $page - 1); ?>" aria-label="Halaman sebelumnya">‹ <span>Sebelumnya</span></a>
                                </li>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= min($totalPages, $page + 1); ?>" aria-label="Halaman berikutnya"><span>Berikutnya</span> ›</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../footer.php'; ?>