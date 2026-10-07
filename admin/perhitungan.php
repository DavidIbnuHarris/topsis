<?php
session_start();
if ($_SESSION['status'] != "login" || $_SESSION['role'] != "admin") {
    header("location:../login.php");
    exit();
}

include '../koneksi.php';

$kriteria = [];
$q_kriteria = mysqli_query($koneksi, "SELECT * FROM kriteria ORDER BY id_kriteria ASC");
while ($row = mysqli_fetch_assoc($q_kriteria)) {
    $kriteria[$row['id_kriteria']] = $row;
}

$alternatif = [];
$q_alternatif = mysqli_query($koneksi, "SELECT * FROM alternatif ORDER BY id_alternatif ASC");
while ($row = mysqli_fetch_assoc($q_alternatif)) {
    $alternatif[$row['id_alternatif']] = $row;
}

if (empty($kriteria) || empty($alternatif)) {
    $page_title = 'Detail Perhitungan TOPSIS';
    include 'header.php';
    echo "<div class='alert alert-danger'>Data Kriteria atau Alternatif masih kosong!</div>";
    include 'footer.php';
    exit;
}

$matriks_x = [];
$q_hasil = mysqli_query($koneksi, "SELECT id_alternatif, id_kriteria, AVG(nilai_jawaban) as rata2 
                                   FROM hasil_kuesioner GROUP BY id_alternatif, id_kriteria");
while ($row = mysqli_fetch_assoc($q_hasil)) {
    $matriks_x[$row['id_alternatif']][$row['id_kriteria']] = round($row['rata2'], 3);
}

$pembagi = [];
foreach ($kriteria as $id_k => $k) {
    $total_kuadrat = 0;
    foreach ($alternatif as $id_a => $a) {
        $nilai = $matriks_x[$id_a][$id_k] ?? 0;
        $total_kuadrat += pow($nilai, 2);
    }
    $pembagi[$id_k] = sqrt($total_kuadrat);
}

$matriks_r = [];
foreach ($alternatif as $id_a => $a) {
    foreach ($kriteria as $id_k => $k) {
        $nilai = $matriks_x[$id_a][$id_k] ?? 0;
        $matriks_r[$id_a][$id_k] = ($pembagi[$id_k] != 0) ? round($nilai / $pembagi[$id_k], 4) : 0;
    }
}

$matriks_y = [];
foreach ($alternatif as $id_a => $a) {
    foreach ($kriteria as $id_k => $k) {
        $matriks_y[$id_a][$id_k] = round($matriks_r[$id_a][$id_k] * $k['bobot'], 4);
    }
}

$ideal_positif = [];
$ideal_negatif = [];
foreach ($kriteria as $id_k => $k) {
    $kolom_nilai = [];
    foreach ($alternatif as $id_a => $a) {
        $kolom_nilai[] = $matriks_y[$id_a][$id_k];
    }
    if ($k['jenis_kriteria'] == 'benefit') {
        $ideal_positif[$id_k] = max($kolom_nilai);
        $ideal_negatif[$id_k] = min($kolom_nilai);
    } else {
        $ideal_positif[$id_k] = min($kolom_nilai);
        $ideal_negatif[$id_k] = max($kolom_nilai);
    }
}

$d_positif = [];
$d_negatif = [];
foreach ($alternatif as $id_a => $a) {
    $sum_plus = 0;
    $sum_min  = 0;
    foreach ($kriteria as $id_k => $k) {
        $sum_plus += pow($matriks_y[$id_a][$id_k] - $ideal_positif[$id_k], 2);
        $sum_min  += pow($matriks_y[$id_a][$id_k] - $ideal_negatif[$id_k], 2);
    }
    $d_positif[$id_a] = round(sqrt($sum_plus), 4);
    $d_negatif[$id_a] = round(sqrt($sum_min), 4);
}

$hasil_akhir = [];
foreach ($alternatif as $id_a => $a) {
    $total_d = $d_negatif[$id_a] + $d_positif[$id_a];
    $v = ($total_d != 0) ? round($d_negatif[$id_a] / $total_d, 4) : 0;
    $hasil_akhir[] = [
        'nama'    => $a['nama_alternatif'],
        'nilai_v' => $v
    ];
}
usort($hasil_akhir, function($a, $b) {
    return $b['nilai_v'] <=> $a['nilai_v'];
});

$page_title = 'Detail Perhitungan TOPSIS';
include 'header.php';
?>

<div class="card mb-4 shadow-sm border-bottom-success">
    <div class="card-header bg-success text-white fw-bold">Hasil Akhir Perangkingan</div>
    <div class="card-body">
        <table class="table table-bordered table-striped mb-0">
            <thead class="table-dark">
                <tr>
                    <th style="width:100px">Peringkat</th>
                    <th>Nama Alternatif</th>
                    <th style="width:180px">Nilai Preferensi (V)</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ($hasil_akhir as $hasil): ?>
                <tr>
                    <td class="fw-bold"><?= $rank++ ?></td>
                    <td><?= htmlspecialchars($hasil['nama']) ?></td>
                    <td><?= $hasil['nilai_v'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<h5 class="mb-3">Detail Tahapan Matematika TOPSIS</h5>

<div class="accordion mb-5" id="accordionTopsis">

    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tahap1">
                1. Matriks Keputusan (X) - Rata-rata Kuesioner
            </button>
        </h2>
        <div id="tahap1" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis">
            <div class="accordion-body overflow-auto">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Alternatif</th>
                            <?php foreach ($kriteria as $k) echo "<th>" . htmlspecialchars($k['nama_kriteria']) . "</th>"; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alternatif as $id_a => $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['nama_alternatif']) ?></td>
                            <?php foreach ($kriteria as $id_k => $k): ?>
                            <td><?= $matriks_x[$id_a][$id_k] ?? 0 ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tahap2">
                2. Matriks Normalisasi (R)
            </button>
        </h2>
        <div id="tahap2" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis">
            <div class="accordion-body overflow-auto">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Alternatif</th>
                            <?php foreach ($kriteria as $k) echo "<th>" . htmlspecialchars($k['nama_kriteria']) . "</th>"; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alternatif as $id_a => $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['nama_alternatif']) ?></td>
                            <?php foreach ($kriteria as $id_k => $k): ?>
                            <td><?= $matriks_r[$id_a][$id_k] ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tahap3">
                3. Matriks Normalisasi Terbobot (Y)
            </button>
        </h2>
        <div id="tahap3" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis">
            <div class="accordion-body overflow-auto">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Alternatif</th>
                            <?php foreach ($kriteria as $k) echo "<th>" . htmlspecialchars($k['nama_kriteria']) . " (W={$k['bobot']})</th>"; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alternatif as $id_a => $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['nama_alternatif']) ?></td>
                            <?php foreach ($kriteria as $id_k => $k): ?>
                            <td><?= $matriks_y[$id_a][$id_k] ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tahap4">
                4. Solusi Ideal Positif (A+) & Negatif (A-)
            </button>
        </h2>
        <div id="tahap4" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis">
            <div class="accordion-body overflow-auto">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Solusi Ideal</th>
                            <?php foreach ($kriteria as $k) echo "<th>" . htmlspecialchars($k['nama_kriteria']) . " ({$k['jenis_kriteria']})</th>"; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold">Positif (A+)</td>
                            <?php foreach ($kriteria as $id_k => $k) echo "<td>{$ideal_positif[$id_k]}</td>"; ?>
                        </tr>
                        <tr>
                            <td class="fw-bold">Negatif (A-)</td>
                            <?php foreach ($kriteria as $id_k => $k) echo "<td>{$ideal_negatif[$id_k]}</td>"; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tahap5">
                5. Jarak Solusi Ideal (D+ dan D-)
            </button>
        </h2>
        <div id="tahap5" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis">
            <div class="accordion-body overflow-auto">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Alternatif</th>
                            <th>Jarak Positif (D+)</th>
                            <th>Jarak Negatif (D-)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alternatif as $id_a => $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['nama_alternatif']) ?></td>
                            <td><?= $d_positif[$id_a] ?></td>
                            <td><?= $d_negatif[$id_a] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>