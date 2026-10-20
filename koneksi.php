// Membuat koneksi ke database MySQL menggunakan PHP
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_uts_agenda_kegiatan";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}
?>