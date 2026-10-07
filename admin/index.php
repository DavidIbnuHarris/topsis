<?php
session_start();
if ($_SESSION['status'] != "login" || $_SESSION['role'] != "admin") {
    header("location:../login.php");
    exit();
}

include '../koneksi.php';

$jml_alternatif = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM alternatif"));
$jml_kriteria   = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM kriteria"));
$jml_responden  = mysqli_num_rows(mysqli_query($koneksi, "SELECT DISTINCT id_user FROM hasil_kuesioner"));

$page_title = 'Dashboard Overview';
include 'header.php';
?>

<h3 class="mb-4">Selamat Datang, Admin!</h3>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-start border-primary border-4 shadow-sm h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Alternatif</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $jml_alternatif ?> Data</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-box fs-1 text-black-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card border-start border-success border-4 shadow-sm h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Kriteria</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $jml_kriteria ?> Data</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-list-check fs-1 text-black-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card border-start border-info border-4 shadow-sm h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Total Responden</div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $jml_responden ?> Orang</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people fs-1 text-black-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>