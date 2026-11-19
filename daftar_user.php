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

$query = "SELECT * FROM users ORDER BY id ASC LIMIT $limit OFFSET $offset";
$users = mysqli_query($koneksi, $query);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/padil_coding/tugas_besar_CRUD/Css/style.css?v=20261021-1328">
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container mt-5 mb-5">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2>Daftar User</h2>
        <a href="<?= htmlspecialchars(app_url('Tambah_user/tambah_users.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">+ Tambah User</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Data Pengguna</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Username</th>
                            <th scope="col">Password</th>
                            <th scope="col">Role</th>
                            <th scope="col">Created At</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = $offset + 1;
                    while ($user = mysqli_fetch_assoc($users)) {
                    ?>
                        <tr>
                          <td><?= $no++ ?></td>
                          <td><?= htmlspecialchars($user['username']) ?></td>
                          <td><span class="text-muted">••••••••</span></td>
                          <td>
                              <span class="badge <?= $user['role'] == 'admin' ? 'bg-danger' : ($user['role'] == 'guru' ? 'bg-warning text-dark' : 'bg-info') ?>">
                                  <?= htmlspecialchars($user['role']) ?>
                              </span>
                          </td>
                          <td><?= htmlspecialchars($user['created_at']) ?></td>
                          <td>
                            <a href="<?= htmlspecialchars(app_url('Edit/edit_user.php?id=' . (int) $user['id'] . '&role=' . urlencode($user['role'])), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="<?= htmlspecialchars(app_url('Delete/hapus.php?id=' . (int) $user['id']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</a>
                          </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            Menampilkan <?= min($totalUsers, $offset + 1); ?>-<?= min($totalUsers, $offset + $limit); ?> dari <?= $totalUsers ?> data
                        </div>

                        <nav aria-label="Pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= max(1, $page - 1); ?>">Sebelumnya</a>
                                </li>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= min($totalPages, $page + 1); ?>">Berikutnya</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
</body>
</html>