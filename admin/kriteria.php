<?php
session_start();
if ($_SESSION['status'] != "login" || $_SESSION['role'] != "admin") {
    header("location:../login.php");
    exit();
}

include '../koneksi.php';

$error_kriteria = '';
$error_skala    = '';

/* =========================
   PROSES KRITERIA
   ========================= */
if (isset($_POST['simpan_kriteria'])) {
    $nama  = mysqli_real_escape_string($koneksi, $_POST['nama_kriteria']);
    $jenis = mysqli_real_escape_string($koneksi, $_POST['jenis_kriteria']);
    $bobot = (float) $_POST['bobot'];

    if (mysqli_query($koneksi, "INSERT INTO kriteria (nama_kriteria, jenis_kriteria, bobot) 
                                VALUES ('$nama', '$jenis', '$bobot')")) {
        echo "<script>alert('Kriteria berhasil disimpan!'); window.location='kriteria.php';</script>";
        exit;
    }
    $error_kriteria = 'Gagal menyimpan kriteria.';
}

if (isset($_GET['hapus_kriteria'])) {
    $id = (int) $_GET['hapus_kriteria'];
    mysqli_query($koneksi, "DELETE FROM kriteria WHERE id_kriteria='$id'");
    echo "<script>alert('Kriteria berhasil dihapus!'); window.location='kriteria.php';</script>";
    exit;
}

/* =========================
   PROSES SKALA NILAI
   ========================= */
if (isset($_POST['simpan_skala'])) {
    $id_skala   = (int) ($_POST['id_skala'] ?? 0);
    $nilai      = (int) $_POST['nilai'];
    $keterangan = mysqli_real_escape_string($koneksi, trim($_POST['keterangan']));

    if ($nilai < 1 || $keterangan === '') {
        $error_skala = 'Nilai dan keterangan wajib diisi.';
    } else {
        if ($id_skala > 0) {
            $sql = "UPDATE skala_nilai SET nilai='$nilai', keterangan='$keterangan' 
                    WHERE id_skala='$id_skala'";
            $msg = 'Skala berhasil diperbarui!';
        } else {
            $cek = mysqli_query($koneksi, "SELECT id_skala FROM skala_nilai WHERE nilai='$nilai'");
            if (mysqli_num_rows($cek) > 0) {
                $error_skala = "Nilai $nilai sudah ada. Gunakan nilai lain.";
            } else {
                $sql = "INSERT INTO skala_nilai (nilai, keterangan) VALUES ('$nilai', '$keterangan')";
                $msg = 'Skala berhasil ditambahkan!';
            }
        }

        if (empty($error_skala) && mysqli_query($koneksi, $sql)) {
            echo "<script>alert('$msg'); window.location='kriteria.php';</script>";
            exit;
        }
    }
}

if (isset($_GET['hapus_skala'])) {
    $id = (int) $_GET['hapus_skala'];
    mysqli_query($koneksi, "DELETE FROM skala_nilai WHERE id_skala='$id'");
    echo "<script>alert('Skala berhasil dihapus!'); window.location='kriteria.php';</script>";
    exit;
}

$edit_skala = null;
if (isset($_GET['edit_skala'])) {
    $id = (int) $_GET['edit_skala'];
    $q  = mysqli_query($koneksi, "SELECT * FROM skala_nilai WHERE id_skala='$id'");
    $edit_skala = mysqli_fetch_assoc($q);
}

$page_title = 'Data Kriteria';
include 'header.php';
?>

<!-- =========================
     SECTION 1: KRITERIA
     ========================= -->
<h3 class="mb-4">Kelola Data Kriteria (Pertanyaan Kuesioner)</h3>

<?php if (!empty($error_kriteria)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_kriteria) ?></div>
<?php endif; ?>

<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-semibold">Pertanyaan Kriteria</label>
                <input type="text" name="nama_kriteria" class="form-control"
                       placeholder="Contoh: Apakah mata kuliah ini menyenangkan?" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Jenis Kriteria</label>
                <select name="jenis_kriteria" class="form-select" required>
                    <option value="benefit">Benefit (Semakin besar semakin baik)</option>
                    <option value="cost">Cost (Semakin kecil semakin baik)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Bobot</label>
                <input type="number" step="0.1" name="bobot" class="form-control"
                       placeholder="Contoh: 3, 4, atau 5" required>
            </div>
            <button type="submit" name="simpan_kriteria" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan Kriteria
            </button>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-5">
    <div class="card-body">
        <table class="table table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th style="width:60px">No</th>
                    <th>Pertanyaan Kriteria</th>
                    <th style="width:120px">Jenis</th>
                    <th style="width:100px">Bobot</th>
                    <th style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $query = mysqli_query($koneksi, "SELECT * FROM kriteria ORDER BY id_kriteria");
                if (mysqli_num_rows($query) === 0):
                ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Belum ada kriteria. Silakan tambahkan.
                        </td>
                    </tr>
                <?php else: while ($data = mysqli_fetch_array($query)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($data['nama_kriteria']) ?></td>
                        <td><?= ucfirst($data['jenis_kriteria']) ?></td>
                        <td><?= $data['bobot'] ?></td>
                        <td>
                            <a href="?hapus_kriteria=<?= $data['id_kriteria'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Yakin hapus kriteria ini?')">
                                <i class="bi bi-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- =========================
     SECTION 2: SKALA NILAI
     ========================= -->
<hr class="my-5">

<h3 class="mb-2">Kelola Skala Nilai Kuesioner</h3>
<p class="text-muted mb-4">
    Skala ini yang muncul sebagai pilihan jawaban di form kuesioner user.
    Semakin tinggi nilai, semakin positif maknanya.
</p>

<?php if (!empty($error_skala)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_skala) ?></div>
<?php endif; ?>

<div class="card mb-4 shadow-sm <?= $edit_skala ? 'border-warning border-3' : '' ?>" id="form-skala">
    <div class="card-header bg-white fw-bold">
        <?= $edit_skala ? 'Edit Skala' : 'Tambah Skala Baru' ?>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="id_skala" value="<?= $edit_skala['id_skala'] ?? 0 ?>">

            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Nilai</label>
                    <input type="number" name="nilai" class="form-control"
                           min="1" max="99"
                           value="<?= $edit_skala['nilai'] ?? '' ?>"
                           placeholder="5" required>
                </div>
                <div class="col-md-7">
                    <label class="form-label fw-semibold">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control"
                           value="<?= htmlspecialchars($edit_skala['keterangan'] ?? '') ?>"
                           placeholder="Contoh: Sangat Baik / Sangat Setuju" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" name="simpan_skala" class="btn btn-primary w-100">
                        <i class="bi bi-save me-1"></i> <?= $edit_skala ? 'Update Skala' : 'Simpan Skala' ?>
                    </button>
                </div>
            </div>

            <?php if ($edit_skala): ?>
                <a href="kriteria.php" class="btn btn-secondary btn-sm mt-3">
                    <i class="bi bi-x-circle me-1"></i> Batal Edit
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">Daftar Skala Nilai</div>
    <div class="card-body">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th style="width:60px">No</th>
                    <th style="width:100px">Nilai</th>
                    <th>Keterangan</th>
                    <th style="width:180px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $query = mysqli_query($koneksi, "SELECT * FROM skala_nilai ORDER BY nilai DESC");
                if (mysqli_num_rows($query) === 0):
                ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            Belum ada skala nilai. Silakan tambahkan.
                        </td>
                    </tr>
                <?php else: while ($data = mysqli_fetch_array($query)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <span class="badge bg-primary fs-6"><?= (int) $data['nilai'] ?></span>
                        </td>
                        <td><?= htmlspecialchars($data['keterangan']) ?></td>
                        <td>
                            <a href="?edit_skala=<?= $data['id_skala'] ?>#form-skala"
                               class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                            <a href="?hapus_skala=<?= $data['id_skala'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Yakin hapus skala ini?')">
                                <i class="bi bi-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>