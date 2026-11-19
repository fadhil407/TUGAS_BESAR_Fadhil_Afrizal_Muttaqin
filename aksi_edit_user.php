<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
/** @var mysqli $koneksi */
require_once __DIR__ . '/../koneksi.php';

$id = $_POST['id_user'];
$username = $_POST['username'];
$pass = $_POST['password'];
$pass = password_hash($pass, PASSWORD_DEFAULT);
$role = $_POST['role'];

//untuk mengecek apakah password di rubah atau tidak
if ($pass != "") {
    //query untuk update tabel users dimana password ingin kita ubah
    $sql = "UPDATE users SET username='$username', password='$pass' WHERE id = '$id'";
    $hasil = mysqli_query($koneksi, $sql);
} else {
    //query untuk update tabel users dimana password tidak ingin kita ubah
    $sql = "UPDATE users SET username='$username' WHERE id = '$id'";
    $hasil = mysqli_query($koneksi, $sql);

}

//untuk menangkap isi dari form input nama dan jenis kelamin karen tabel siswa dan guru memiliki field tersebut
$nama = $_POST['nama'];
$jk = $_POST['jenis_kelamin'];
// $id_user = $_POST['id_user'];

// untuk mengecek kita ingin mengudah tabel siswa atau guru 
if ($_GET['aksi'] == 'editsiswa') {

    //field yang hanya dimiliki siswa
    $nis = $_POST['nis'];
    $kelas = $_POST['kelas'];

} elseif ($_GET['aksi'] == 'editguru') {

    // field yang dimiliki guru 
    $nip = $_POST['nip'];
    // $id_guru = $_POST['id_guru'];

    $sql = "UPDATE guru SET nip='$nip', nama='$nama', jenis_kelamin='$jk' WHERE user_id = '$id'";

    $result = mysqli_query($koneksi, $sql);

}

//untuk mengecek apakah data pada tabel users berhasil diubah atau tidak
if ($hasil || $result) {
    echo "<script>alert('Data Berhasil diubah'); window.location.href=" . json_encode(app_url('tabel_user/daftar_user.php')) . ";</script>";
} else {
    echo "<script>alert('Data Gagal diubah'); window.location.href=" . json_encode(app_url('tabel_user/daftar_user.php')) . ";</script>";
}