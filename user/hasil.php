<?php
session_start();
// Cek apakah yang akses punya sesi dan rolenya user
if ($_SESSION['status'] != "login" || $_SESSION['role'] != "user") {
    header("location:../login.php");
    exit();
}

include '../koneksi.php';

// --- LOGIKA TOPSIS (Sama seperti Admin, tapi berjalan di background) ---
$kriteria = [];
$q_kriteria = mysqli_query($koneksi, "SELECT * FROM kriteria ORDER BY id_kriteria ASC");
while ($row = mysqli_fetch_assoc($q_kriteria)) { $kriteria[$row['id_kriteria']] = $row; }

$alternatif = [];
$q_alternatif = mysqli_query($koneksi, "SELECT * FROM alternatif ORDER BY id_alternatif ASC");
while ($row = mysqli_fetch_assoc($q_alternatif)) { $alternatif[$row['id_alternatif']] = $row; }

$matriks_x = [];
$q_hasil = mysqli_query($koneksi, "SELECT id_alternatif, id_kriteria, AVG(nilai_jawaban) as rata2 FROM hasil_kuesioner GROUP BY id_alternatif, id_kriteria");
while ($row = mysqli_fetch_assoc($q_hasil)) { $matriks_x[$row['id_alternatif']][$row['id_kriteria']] = round($row['rata2'], 3); }

$pembagi = [];
foreach ($kriteria as $id_k => $k) {
    $tot = 0;
    foreach ($alternatif as $id_a => $a) { $nilai = $matriks_x[$id_a][$id_k] ?? 0; $tot += pow($nilai, 2); }
    $pembagi[$id_k] = sqrt($tot);
}

$matriks_y = [];
foreach ($alternatif as $id_a => $a) {
    foreach ($kriteria as $id_k => $k) {
        $nilai_r = ($pembagi[$id_k] != 0) ? (($matriks_x[$id_a][$id_k] ?? 0) / $pembagi[$id_k]) : 0;
        $matriks_y[$id_a][$id_k] = round($nilai_r * $k['bobot'], 4);
    }
}

$ideal_positif = []; $ideal_negatif = [];
foreach ($kriteria as $id_k => $k) {
    $kolom = [];
    foreach ($alternatif as $id_a => $a) { $kolom[] = $matriks_y[$id_a][$id_k]; }
    if ($k['jenis_kriteria'] == 'benefit') {
        $ideal_positif[$id_k] = max($kolom); $ideal_negatif[$id_k] = min($kolom);
    } else {
        $ideal_positif[$id_k] = min($kolom); $ideal_negatif[$id_k] = max($kolom);
    }
}

$hasil_akhir = [];
foreach ($alternatif as $id_a => $a) {
    $sum_p = 0; $sum_n = 0;
    foreach ($kriteria as $id_k => $k) {
        $sum_p += pow($matriks_y[$id_a][$id_k] - $ideal_positif[$id_k], 2);
        $sum_n += pow($matriks_y[$id_a][$id_k] - $ideal_negatif[$id_k], 2);
    }
    $d_p = sqrt($sum_p); $d_n = sqrt($sum_n);
    $v = ($d_n + $d_p != 0) ? round($d_n / ($d_n + $d_p), 4) : 0;
    $hasil_akhir[] = ['nama' => $a['nama_alternatif'], 'nilai_v' => $v];
}

// Urutkan ranking dari yang terbesar
usort($hasil_akhir, function($a, $b) { return $b['nilai_v'] <=> $a['nilai_v']; });
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Hasil Keputusan - User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5 mb-5">
        <div class="text-center mb-4">
            <h2 class="text-success fw-bold">Terima Kasih!</h2>
            <p class="text-muted">Data kuesioner berhasil diproses menggunakan algoritma TOPSIS.</p>
        </div>

        <div class="card shadow-sm border-top-primary">
            <div class="card-header bg-primary text-white fw-bold">
                Hasil Perangkingan Rekomendasi
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Peringkat</th>
                            <th>Alternatif (Mata Kuliah)</th>
                            <th>Nilai Preferensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1; 
                        foreach($hasil_akhir as $hasil): 
                            // Highlight baris pertama (Peringkat 1)
                            $bg_class = ($rank == 1) ? 'table-success fw-bold' : '';
                        ?>
                        <tr class="<?= $bg_class ?>">
                            <td><?= $rank++ ?></td>
                            <td><?= $hasil['nama'] ?></td>
                            <td><?= $hasil['nilai_v'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="alert alert-info mt-3">
                    <strong>Kesimpulan:</strong> Berdasarkan perhitungan agregat dari seluruh kuesioner, sistem merekomendasikan <strong><?= $hasil_akhir[0]['nama'] ?></strong> sebagai pilihan terbaik.
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="kuesioner.php" class="btn btn-outline-primary">Isi Kuesioner Baru</a>
        </div>
    </div>
</body>
</html>