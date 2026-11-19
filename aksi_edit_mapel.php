<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$kodeMapel = trim($_POST['kode_mapel'] ?? '');
$namaMapel = trim($_POST['nama_mapel'] ?? '');
$guruId = filter_input(INPUT_POST, 'guru_id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: ' . app_url('tabel_user/daftar_mapel.php'));
    exit;
}

$redirectUrl = app_url('Edit/edit_mapel.php?id=' . $id);
$mapelStatement = mysqli_prepare($koneksi, 'SELECT id FROM mapel WHERE id = ?');
mysqli_stmt_bind_param($mapelStatement, 'i', $id);
mysqli_stmt_execute($mapelStatement);
$mapelResult = mysqli_stmt_get_result($mapelStatement);
$mapelExists = mysqli_num_rows($mapelResult) > 0;
mysqli_stmt_close($mapelStatement);

if (!$mapelExists) {
    header('Location: ' . app_url('tabel_user/daftar_mapel.php'));
    exit;
}

if ($kodeMapel === '' || strlen($kodeMapel) > 10 || $namaMapel === '' || strlen($namaMapel) > 100 || !$guruId || $guruId < 1) {
    $_SESSION['edit_mapel_error'] = 'Isi semua kolom dengan benar. Kode maksimal 10 karakter dan nama maksimal 100 karakter.';
    $_SESSION['edit_mapel_old'] = [
        'kode_mapel' => $kodeMapel,
        'nama_mapel' => $namaMapel,
        'guru_id' => $guruId ?: 0,
    ];
    header('Location: ' . $redirectUrl);
    exit;
}

$guruStatement = mysqli_prepare($koneksi, 'SELECT id FROM guru WHERE id = ?');
mysqli_stmt_bind_param($guruStatement, 'i', $guruId);
mysqli_stmt_execute($guruStatement);
$guruResult = mysqli_stmt_get_result($guruStatement);
$guruExists = mysqli_num_rows($guruResult) > 0;
mysqli_stmt_close($guruStatement);

if (!$guruExists) {
    $_SESSION['edit_mapel_error'] = 'Guru yang dipilih tidak ditemukan.';
    $_SESSION['edit_mapel_old'] = [
        'kode_mapel' => $kodeMapel,
        'nama_mapel' => $namaMapel,
        'guru_id' => $guruId,
    ];
    header('Location: ' . $redirectUrl);
    exit;
}

$updateStatement = mysqli_prepare($koneksi, 'UPDATE mapel SET kode_mapel = ?, nama_mapel = ?, guru_id = ? WHERE id = ?');
mysqli_stmt_bind_param($updateStatement, 'ssii', $kodeMapel, $namaMapel, $guruId, $id);

if (!mysqli_stmt_execute($updateStatement)) {
    $_SESSION['edit_mapel_error'] = 'Gagal memperbarui data mapel: ' . mysqli_stmt_error($updateStatement);
    $_SESSION['edit_mapel_old'] = [
        'kode_mapel' => $kodeMapel,
        'nama_mapel' => $namaMapel,
        'guru_id' => $guruId,
    ];
    mysqli_stmt_close($updateStatement);
    header('Location: ' . $redirectUrl);
    exit;
}

mysqli_stmt_close($updateStatement);
header('Location: ' . app_url('tabel_user/daftar_mapel.php'));
exit;
