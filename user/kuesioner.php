<?php
session_start();
require_once '../koneksi.php';

if (
    !isset($_SESSION['status'], $_SESSION['role'], $_SESSION['id_user']) ||
    $_SESSION['status'] !== 'login' ||
    $_SESSION['role']   !== 'user'
) {
    header('Location: ../login.php');
    exit;
}

$id_user = (int) $_SESSION['id_user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_kuesioner'])) {
    $jawaban = $_POST['jawaban'] ?? [];

    if (empty($jawaban) || !is_array($jawaban)) {
        $error = 'Anda belum mengisi kuesioner. Silakan isi semua pertanyaan.';
    } else {
        mysqli_begin_transaction($koneksi);

        try {
            $sql = "INSERT INTO hasil_kuesioner 
                        (id_user, id_alternatif, id_kriteria, nilai_jawaban)
                    VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($koneksi, $sql);

            if (!$stmt) {
                throw new Exception('Gagal menyiapkan query: ' . mysqli_error($koneksi));
            }

            foreach ($jawaban as $id_alternatif => $kriteria_array) {
                if (!is_array($kriteria_array)) continue;

                foreach ($kriteria_array as $id_kriteria => $nilai) {
                    $id_alternatif = (int) $id_alternatif;
                    $id_kriteria   = (int) $id_kriteria;
                    $nilai         = (int) $nilai;

                    mysqli_stmt_bind_param(
                        $stmt, 'iiii',
                        $id_user, $id_alternatif, $id_kriteria, $nilai
                    );
                    mysqli_stmt_execute($stmt);
                }
            }

            mysqli_stmt_close($stmt);
            mysqli_commit($koneksi);

            header('Location: hasil.php');
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $error = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage();
        }
    }
}

$alternatif_list = mysqli_query($koneksi, "SELECT id_alternatif, nama_alternatif FROM alternatif ORDER BY id_alternatif");
$kriteria_list   = mysqli_query($koneksi, "SELECT id_kriteria, nama_kriteria FROM kriteria ORDER BY id_kriteria");
$skala_list      = mysqli_query($koneksi, "SELECT nilai, keterangan FROM skala_nilai ORDER BY nilai DESC");

$kriteria_arr = mysqli_fetch_all($kriteria_list, MYSQLI_ASSOC);
$skala_arr    = mysqli_fetch_all($skala_list, MYSQLI_ASSOC);

$total_pertanyaan_per_alternatif = count($kriteria_arr);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner Pemilihan - User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.92);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .kriteria-block:last-child hr { display: none; }
    </style>
</head>
<body class="bg-light">

    <div id="loading-overlay">
        <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status"></div>
        <h4 class="mt-3 text-primary">Memproses Penilaian Anda...</h4>
        <p class="text-muted">Sedang menghitung menggunakan metode TOPSIS</p>
    </div>

    <div class="container my-5">
        <h2 class="text-center mb-4">Form Kuesioner Penilaian</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="form-kuesioner" novalidate>
            <input type="hidden" name="simpan_kuesioner" value="1">

            <?php while ($alternatif = mysqli_fetch_assoc($alternatif_list)): ?>
                <?php $id_alt = (int) $alternatif['id_alternatif']; ?>

                <div class="card mb-4 border-primary shadow-sm">
                    <div class="card-header bg-primary text-white fw-bold">
                        Menilai: <?= htmlspecialchars($alternatif['nama_alternatif']) ?>
                    </div>
                    <div class="card-body">
                        <?php foreach ($kriteria_arr as $index => $kriteria): ?>
                            <?php
                                $id_krit   = (int) $kriteria['id_kriteria'];
                                $name_attr = "jawaban[{$id_alt}][{$id_krit}]";
                            ?>
                            <div class="kriteria-block mb-3">
                                <p class="mb-2 fw-semibold">
                                    <?= $index + 1 ?>. <?= htmlspecialchars($kriteria['nama_kriteria']) ?>
                                </p>

                                <div class="d-flex flex-wrap gap-3">
                                    <?php foreach ($skala_arr as $skala): ?>
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="radio"
                                                   id="alt<?= $id_alt ?>-krit<?= $id_krit ?>-val<?= (int) $skala['nilai'] ?>"
                                                   name="<?= $name_attr ?>"
                                                   value="<?= (int) $skala['nilai'] ?>"
                                                   required>
                                            <label class="form-check-label text-muted"
                                                   for="alt<?= $id_alt ?>-krit<?= $id_krit ?>-val<?= (int) $skala['nilai'] ?>">
                                                <?= htmlspecialchars($skala['keterangan']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if ($index < $total_pertanyaan_per_alternatif - 1): ?>
                                <hr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endwhile; ?>

            <div class="text-center mb-5">
                <button type="submit" class="btn btn-success btn-lg px-5">
                    Kirim Kuesioner
                </button>
            </div>
        </form>
    </div>

    <script>
        const form      = document.getElementById('form-kuesioner');
        const overlay   = document.getElementById('loading-overlay');
        const submitBtn = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');

                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus({ preventScroll: true });
                }
                return;
            }

            overlay.style.display = 'flex';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>