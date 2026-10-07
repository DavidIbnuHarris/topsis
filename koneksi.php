<?php

// Deklarasi variabel database
$host       = "localhost";
$username   = "root"; 
$password   = ""; 
$database   = "db_topsis_1"; 

// Membuat koneksi
$koneksi = mysqli_connect($host, $username, $password, $database);

// Mengecek koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>