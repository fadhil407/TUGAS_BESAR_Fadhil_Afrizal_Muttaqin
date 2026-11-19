<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../koneksi.php';

$nis = $_POST['nis'];
$nama = $_POST['nama'];
$kelas = $_POST['kelas'];
$jenis_kelamin = $_POST['jenis_kelamin'];
$username = $_POST['username'];
$pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role = $_POST['role'];

//sql atau query untuk memasukan data kedalam tabel users
$sql_users = "INSERT INTO users (id, username, password, role, created_at) VALUES ('','$username','$pass','$role',now())";

//untuk menjalankan atau mengeksekusi perintah sql, insert data kedalam tabel users
$query = mysqli_query($koneksi, $sql_users);

//untuk mengecek apakah query yang dijalankan berhasil atau gagal untuk tabel users
if ($query) {

    //mysqli_insert_id berfungsi untuk mengambil id terakhir atau id yang baru dimasukan dari tabel users, dan id tersebut akan digunakan untuk mengisi field user_id pada tabel siswa
    $id_terakhir = mysqli_insert_id($koneksi);

    //sql atau query untuk memasukan data kedalam tabel siswa
    $sql_siswa = "INSERT INTO siswa (id, nis, nama, kelas, jenis_kelamin, user_id) 
    VALUES('','$nis','$nama','$kelas','$jenis_kelamin','$id_terakhir') ";

    //untuk menjalankan atau mengeksekusi perintah sql, insert data kedalam tabel siswa
    $query = mysqli_query($koneksi, $sql_siswa);
    //pengecekan berhasil atau gagal untuk tabel siswa

    if ($query) {
        //pesan berhasil
        echo "<script>alert('Data berhasil disimpan!'); window.location.href=" . json_encode(app_url('tabel_user/daftar_user.php')) . ";</script>";

        //else pengecekan untuk insert data ke tabel siswa
    } else {
        // jika gagal maka akan memunculkan pesan gagal
        echo "<script>alert('Gagal menyimpan data kedalam tabel siswa: " . mysqli_error($koneksi) . "'); window.location.href=" . json_encode(app_url('Tambah_user/tambah_siswa.php')) . ";</script>";
    }

    //else pengecekan untuk insert data tabel users
} else {

    // jika gagal maka akan memunculkan pesan gagal
    echo "<script>alert('Gagal menyimpan data kedalam tabel users: " . mysqli_error($koneksi) . "'); window.location.href=" . json_encode(app_url('Tambah_user/tambah_siswa.php')) . ";</script>";
}