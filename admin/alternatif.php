<?php
session_start();
if ($_SESSION['status'] != "login" || $_SESSION['role'] != "admin") {
    header("location:../login.php");
    exit();
}

include '../koneksi.php';

if (isset($_POST['simpan'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_alternatif']);
    mysqli_query($koneksi, "INSERT INTO alternatif (nama_alternatif) VALUES ('$nama')");
    echo "<script>alert('Data berhasil disimpan!'); window.location='alternatif.php';</script>";
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM alternatif WHERE id_alternatif='$id'");
    echo "<script>alert('Data berhasil dihapus!'); window.location='alternatif.php';</script>";
}

$page_title = 'Data Alternatif';
include 'header.php';
?>

<h3 class="mb-4">Kelola Data Alternatif (Mata Kuliah)</h3>

<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Alternatif (Mata Kuliah)</label>
                <input type="text" name="nama_alternatif" class="form-control" required>
            </div>
            <button type="submit" name="simpan" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan Alternatif
            </button>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <table class="table table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th style="width:60px">No</th>
                    <th>Nama Alternatif</th>
                    <th style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $query = mysqli_query($koneksi, "SELECT * FROM alternatif ORDER BY id_alternatif");
                while ($data = mysqli_fetch_array($query)) {
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($data['nama_alternatif']) ?></td>
                    <td>
                        <a href="?hapus=<?= $data['id_alternatif'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Yakin hapus data ini?')">
                            <i class="bi bi-trash"></i> Hapus
                        </a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>