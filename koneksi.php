<?php

$username = "root";
$password = ""; 
$hostname = "localhost";
$database = "db_smk";

$koneksi = mysqli_connect($hostname, $username, $password, $database);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

if (!mysqli_set_charset($koneksi, "utf8mb4")) {
    error_log("Gagal mengatur charset koneksi MySQL: " . mysqli_error($koneksi));
    die("Koneksi database tidak dapat disiapkan.");
}

?>