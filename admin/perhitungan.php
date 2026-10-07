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

$page_title  = 'Detail Perhitungan TOPSIS';
$use_mathjax = true;

if (empty($kriteria) || empty($alternatif)) {
    include 'header.php';
    echo "<div class='alert alert-danger'>Data Kriteria atau Alternatif masih kosong!</div>";
    include 'footer.php';
    exit;
}

/* ========== TAHAP 1 ========== */
$matriks_x = [];
$q_hasil = mysqli_query($koneksi, "SELECT id_alternatif, id_kriteria, AVG(nilai_jawaban) as rata2 
                                   FROM hasil_kuesioner GROUP BY id_alternatif, id_kriteria");
while ($row = mysqli_fetch_assoc($q_hasil)) {
    $matriks_x[$row['id_alternatif']][$row['id_kriteria']] = round($row['rata2'], 3);
}

/* Info responden untuk narasi */
$jml_responden = mysqli_fetch_assoc(mysqli_query($koneksi, 
    "SELECT COUNT(DISTINCT id_user) as total FROM hasil_kuesioner"))['total'];

/* Contoh data untuk narasi: alternatif pertama & kriteria pertama */
$contoh_alt_id  = array_key_first($alternatif);
$contoh_krit_id = array_key_first($kriteria);
$contoh_alt     = $alternatif[$contoh_alt_id]['nama_alternatif'];
$contoh_krit    = $kriteria[$contoh_krit_id]['nama_kriteria'];
$contoh_nilai_x = $matriks_x[$contoh_alt_id][$contoh_krit_id] ?? 0;

/* ========== TAHAP 2 ========== */
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

/* Contoh perhitungan pembagi untuk kriteria pertama (narasi) */
$contoh_pembagi_hitung = [];
foreach ($alternatif as $id_a => $a) {
    $contoh_pembagi_hitung[] = $matriks_x[$id_a][$contoh_krit_id] ?? 0;
}

/* ========== TAHAP 3 ========== */
$matriks_y = [];
foreach ($alternatif as $id_a => $a) {
    foreach ($kriteria as $id_k => $k) {
        $matriks_y[$id_a][$id_k] = round($matriks_r[$id_a][$id_k] * $k['bobot'], 4);
    }
}

/* ========== TAHAP 4 ========== */
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

/* ========== TAHAP 5 ========== */
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

/* ========== TAHAP 6 ========== */
$hasil_akhir = [];
foreach ($alternatif as $id_a => $a) {
    $total_d = $d_negatif[$id_a] + $d_positif[$id_a];
    $v = ($total_d != 0) ? round($d_negatif[$id_a] / $total_d, 4) : 0;
    $hasil_akhir[] = [
        'id'      => $id_a,
        'nama'    => $a['nama_alternatif'],
        'nilai_v' => $v
    ];
}
usort($hasil_akhir, function($a, $b) {
    return $b['nilai_v'] <=> $a['nilai_v'];
});

include 'header.php';
?>

<h3 class="mb-4">Detail Perhitungan TOPSIS</h3>

<p class="text-muted mb-4">
    Metode <strong>TOPSIS</strong> menentukan alternatif terbaik berdasarkan <strong>kedekatan</strong> 
    dengan solusi ideal positif dan <strong>kejauhan</strong> dari solusi ideal negatif. 
    Perhitungan dilakukan dalam <strong>6 tahap berurutan</strong>. Di bawah tiap tahap ada 
    <strong>contoh perhitungan manual</strong> agar mudah dipahami.
</p>

<!-- ================= HASIL AKHIR ================= -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-success text-white fw-bold">Hasil Akhir Perangkingan</div>
    <div class="card-body">
        <table class="table table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th style="width:100px">Peringkat</th>
                    <th>Nama Alternatif</th>
                    <th style="width:180px">Nilai Preferensi (V)</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ($hasil_akhir as $hasil): ?>
                <tr <?= $rank === 1 ? 'class="table-success fw-bold"' : '' ?>>
                    <td><?= $rank++ ?></td>
                    <td><?= htmlspecialchars($hasil['nama']) ?></td>
                    <td><?= $hasil['nilai_v'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= TAHAP 1 ================= -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white fw-bold">1. Matriks Keputusan (X)</div>
    <div class="card-body">

        <p class="mb-2"><strong>Tujuan tahap ini:</strong> menyusun tabel berisi nilai rata-rata jawaban kuesioner untuk setiap kombinasi (alternatif × kriteria).</p>

        <p class="mb-2"><strong>Rumus rata-rata:</strong></p>
        <p class="mb-3">$$ x_{ij} = \frac{\sum_{r=1}^{R} \text{nilai}_r}{R} $$</p>

        <div class="alert alert-light border mb-3">
            <strong>💡 Contoh perhitungan manual — sel [$contoh_alt × $contoh_krit]:</strong>
            <ol class="mb-0 mt-2">
                <li>Misalkan $R$ = <?= $jml_responden ?> responden yang mengisi kuesioner.</li>
                <li>Setiap responden memberi nilai 1–5 untuk kombinasi ini.</li>
                <li>Nilai-nilai tersebut <strong>dijumlahkan</strong>, lalu <strong>dibagi jumlah responden</strong>.</li>
                <li>Hasilnya: $x_{ij} = <?= $contoh_nilai_x ?>$ (nilai rata-rata).</li>
            </ol>
        </div>

        <p class="mb-2"><strong>Hasil matriks X:</strong></p>
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Alternatif</th>
                        <?php foreach ($kriteria as $k): ?>
                            <th class="text-center"><?= htmlspecialchars($k['nama_kriteria']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alternatif as $id_a => $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nama_alternatif']) ?></td>
                        <?php foreach ($kriteria as $id_k => $k): ?>
                            <td class="text-center"><?= $matriks_x[$id_a][$id_k] ?? 0 ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= TAHAP 2 ================= -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white fw-bold">2. Matriks Ternormalisasi (R)</div>
    <div class="card-body">

        <p class="mb-2"><strong>Tujuan tahap ini:</strong> menyetarakan skala setiap kriteria dengan membagi nilai dengan <strong>akar dari jumlah kuadrat kolomnya</strong>. Hasilnya semua nilai jadi antara 0–1.</p>

        <p class="mb-2"><strong>Rumus:</strong></p>
        <p class="mb-3">$$ r_{ij} = \frac{x_{ij}}{\sqrt{\sum_{i=1}^{m} x_{ij}^{2}}} $$</p>

        <div class="alert alert-light border mb-3">
            <strong>💡 Contoh perhitungan pembagi untuk kriteria "<?= htmlspecialchars($contoh_krit) ?>":</strong>
            <ol class="mb-0 mt-2">
                <li>Nilai-nilai kolom ini:
                    <?php 
                    $parts = [];
                    foreach ($alternatif as $id_a => $a) {
                        $v = $matriks_x[$id_a][$contoh_krit_id] ?? 0;
                        $parts[] = $v;
                    }
                    echo implode(', ', $parts);
                    ?>
                </li>
                <li>Kuadratkan tiap nilai:
                    <?php 
                    $sq_parts = [];
                    foreach ($parts as $p) $sq_parts[] = $p . '² = ' . round(pow($p,2), 3);
                    echo implode(', ', $sq_parts);
                    ?>
                </li>
                <li>Jumlahkan hasil kuadrat = <strong><?= round(array_sum(array_map(fn($p) => pow($p,2), $parts)), 4) ?></strong></li>
                <li>Akar kuadrat = <strong><?= round($pembagi[$contoh_krit_id], 4) ?></strong> ← inilah pembagi kolom ini</li>
            </ol>
        </div>

        <p class="mb-2"><strong>Nilai pembagi tiap kriteria:</strong></p>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:140px">Pembagi</th>
                        <?php foreach ($kriteria as $k): ?>
                            <th class="text-center"><?= htmlspecialchars($k['nama_kriteria']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>√(Σ x²)</td>
                        <?php foreach ($kriteria as $id_k => $k): ?>
                            <td class="text-center"><?= round($pembagi[$id_k], 4) ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-light border mb-3">
            <strong>💡 Contoh perhitungan nilai R untuk sel [$contoh_alt × $contoh_krit]:</strong>
            <p class="mb-0 mt-2">
                $$ r_{ij} = \frac{<?= $contoh_nilai_x ?>}{<?= round($pembagi[$contoh_krit_id], 4) ?>} = <?= $matriks_r[$contoh_alt_id][$contoh_krit_id] ?> $$
            </p>
        </div>

        <p class="mb-2"><strong>Hasil matriks R:</strong></p>
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Alternatif</th>
                        <?php foreach ($kriteria as $k): ?>
                            <th class="text-center"><?= htmlspecialchars($k['nama_kriteria']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alternatif as $id_a => $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nama_alternatif']) ?></td>
                        <?php foreach ($kriteria as $id_k => $k): ?>
                            <td class="text-center"><?= $matriks_r[$id_a][$id_k] ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= TAHAP 3 ================= -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white fw-bold">3. Matriks Ternormalisasi Terbobot (Y)</div>
    <div class="card-body">

        <p class="mb-2"><strong>Tujuan tahap ini:</strong> mengalikan matriks R dengan <strong>bobot</strong> tiap kriteria, agar kriteria yang lebih penting berpengaruh lebih besar.</p>

        <p class="mb-2"><strong>Rumus:</strong></p>
        <p class="mb-3">$$ y_{ij} = w_{j} \cdot r_{ij} $$</p>

        <p class="mb-2 fw-semibold">Bobot tiap kriteria:</p>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:100px">Bobot (w)</th>
                        <?php foreach ($kriteria as $k): ?>
                            <th class="text-center"><?= htmlspecialchars($k['nama_kriteria']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>w</td>
                        <?php foreach ($kriteria as $k): ?>
                            <td class="text-center"><?= $k['bobot'] ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-light border mb-3">
            <strong>💡 Contoh perhitungan Y untuk sel [$contoh_alt × $contoh_krit]:</strong>
            <p class="mb-0 mt-2">
                $$ y_{ij} = r_{ij} \times w_j = <?= $matriks_r[$contoh_alt_id][$contoh_krit_id] ?> \times <?= $kriteria[$contoh_krit_id]['bobot'] ?> = <?= $matriks_y[$contoh_alt_id][$contoh_krit_id] ?> $$
            </p>
        </div>

        <p class="mb-2 fw-semibold">Hasil matriks Y:</p>
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Alternatif</th>
                        <?php foreach ($kriteria as $k): ?>
                            <th class="text-center">
                                <?= htmlspecialchars($k['nama_kriteria']) ?><br>
                                <small>(w=<?= $k['bobot'] ?>)</small>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alternatif as $id_a => $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nama_alternatif']) ?></td>
                        <?php foreach ($kriteria as $id_k => $k): ?>
                            <td class="text-center"><?= $matriks_y[$id_a][$id_k] ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= TAHAP 4 ================= -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white fw-bold">4. Solusi Ideal Positif (A+) dan Negatif (A−)</div>
    <div class="card-body">

        <p class="mb-2"><strong>Tujuan tahap ini:</strong> menentukan <strong>nilai terbaik</strong> (A+) dan <strong>nilai terburuk</strong> (A−) dari tiap kolom kriteria pada matriks Y.</p>

        <p class="mb-2"><strong>Aturan pengambilan nilai:</strong></p>
        <ul>
            <li>Kriteria <strong>benefit</strong> (semakin besar semakin baik, misal: kenyamanan, fitur):
                <p class="mb-2 mt-1">$$ A^{+} = \max_i(y_{ij}), \quad A^{-} = \min_i(y_{ij}) $$</p>
            </li>
            <li>Kriteria <strong>cost</strong> (semakin kecil semakin baik, misal: harga):
                <p class="mb-2 mt-1">$$ A^{+} = \min_i(y_{ij}), \quad A^{-} = \max_i(y_{ij}) $$</p>
            </li>
        </ul>

        <div class="alert alert-light border mb-3">
            <strong>💡 Contoh untuk kolom "<?= htmlspecialchars($contoh_krit) ?>" (<?= $kriteria[$contoh_krit_id]['jenis_kriteria'] ?>):</strong>
            <?php
            $col_vals = [];
            foreach ($alternatif as $id_a => $a) {
                $col_vals[$id_a] = $matriks_y[$id_a][$contoh_krit_id];
            }
            ?>
            <ol class="mb-0 mt-2">
                <li>Nilai-nilai kolom: <?= implode(', ', $col_vals) ?></li>
                <li>Karena jenisnya <strong><?= $kriteria[$contoh_krit_id]['jenis_kriteria'] ?></strong>,
                    maka:
                    <?php if ($kriteria[$contoh_krit_id]['jenis_kriteria'] == 'benefit'): ?>
                        A+ = <strong>max</strong> = <?= $ideal_positif[$contoh_krit_id] ?>, 
                        A− = <strong>min</strong> = <?= $ideal_negatif[$contoh_krit_id] ?>
                    <?php else: ?>
                        A+ = <strong>min</strong> = <?= $ideal_positif[$contoh_krit_id] ?>, 
                        A− = <strong>max</strong> = <?= $ideal_negatif[$contoh_krit_id] ?>
                    <?php endif; ?>
                </li>
            </ol>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:160px">Solusi Ideal</th>
                        <?php foreach ($kriteria as $k): ?>
                            <th class="text-center">
                                <?= htmlspecialchars($k['nama_kriteria']) ?><br>
                                <small>(<?= $k['jenis_kriteria'] ?>)</small>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-semibold">A+ (Positif)</td>
                        <?php foreach ($kriteria as $id_k => $k): ?>
                            <td class="text-center"><?= $ideal_positif[$id_k] ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td class="fw-semibold">A− (Negatif)</td>
                        <?php foreach ($kriteria as $id_k => $k): ?>
                            <td class="text-center"><?= $ideal_negatif[$id_k] ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= TAHAP 5 ================= -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white fw-bold">5. Jarak Terhadap Solusi Ideal (D+ dan D−)</div>
    <div class="card-body">

        <p class="mb-2"><strong>Tujuan tahap ini:</strong> mengukur <strong>seberapa jauh</strong> tiap alternatif dari solusi ideal positif (D+) dan negatif (D−). Perhitungannya mirip "jarak Euclidean" di geometri.</p>

        <p class="mb-2"><strong>Rumus:</strong></p>
        <p class="mb-2">$$ D_{i}^{+} = \sqrt{\sum_{j=1}^{n} (y_{ij} - A_{j}^{+})^{2}} $$</p>
        <p class="mb-3">$$ D_{i}^{-} = \sqrt{\sum_{j=1}^{n} (y_{ij} - A_{j}^{-})^{2}} $$</p>

        <div class="alert alert-light border mb-3">
            <strong>💡 Contoh perhitungan D+ untuk "<?= $contoh_alt ?>":</strong>
            <ol class="mb-2 mt-2">
                <li>Ambil nilai Y baris <strong><?= $contoh_alt ?></strong>:
                    <?php
                    $row_y = [];
                    foreach ($kriteria as $id_k => $k) {
                        $row_y[] = $matriks_y[$contoh_alt_id][$id_k];
                    }
                    echo implode(', ', $row_y);
                    ?>
                </li>
                <li>Ambil nilai A+:
                    <?php
                    $row_ap = [];
                    foreach ($kriteria as $id_k => $k) $row_ap[] = $ideal_positif[$id_k];
                    echo implode(', ', $row_ap);
                    ?>
                </li>
                <li>Hitung selisih tiap kolom, lalu kuadratkan:
                    <?php
                    $selisih_parts = [];
                    foreach ($kriteria as $id_k => $k) {
                        $sel = $matriks_y[$contoh_alt_id][$id_k] - $ideal_positif[$id_k];
                        $selisih_parts[] = '(' . $matriks_y[$contoh_alt_id][$id_k] . ' − ' . $ideal_positif[$id_k] . ')² = ' . round(pow($sel, 2), 4);
                    }
                    echo implode(', ', $selisih_parts);
                    ?>
                </li>
                <li>Jumlahkan = <strong><?= round(array_sum(array_map(fn($v) => pow($v,2), array_map(fn($id_k) => $matriks_y[$contoh_alt_id][$id_k] - $ideal_positif[$id_k], array_keys($kriteria)))), 4) ?></strong></li>
                <li>Akar kuadrat → <strong>D+ = <?= $d_positif[$contoh_alt_id] ?></strong></li>
            </ol>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Alternatif</th>
                        <th class="text-center">D+ (jarak ke A+)</th>
                        <th class="text-center">D− (jarak ke A−)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alternatif as $id_a => $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nama_alternatif']) ?></td>
                        <td class="text-center"><?= $d_positif[$id_a] ?></td>
                        <td class="text-center"><?= $d_negatif[$id_a] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= TAHAP 6 ================= -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white fw-bold">6. Nilai Preferensi (V) dan Perangkingan</div>
    <div class="card-body">

        <p class="mb-2"><strong>Tujuan tahap ini:</strong> menghitung nilai akhir tiap alternatif (0 sampai 1). Nilai <strong>tertinggi</strong> = alternatif terbaik.</p>

        <p class="mb-2"><strong>Rumus:</strong></p>
        <p class="mb-3">$$ V_{i} = \frac{D_{i}^{-}}{D_{i}^{-} + D_{i}^{+}} $$</p>

        <div class="alert alert-secondary mb-3">
            <strong>📖 Cara membaca rumus:</strong>
            <ul class="mb-0 mt-2">
                <li>Kalau D− besar dan D+ kecil → artinya alternatif <em>dekat</em> ke A+ dan <em>jauh</em> dari A− → V mendekati 1 (bagus).</li>
                <li>Kalau D− kecil dan D+ besar → artinya alternatif <em>jauh</em> dari A+ dan <em>dekat</em> dari A− → V mendekati 0 (jelek).</li>
            </ul>
        </div>

        <div class="alert alert-light border mb-3">
            <strong>💡 Contoh perhitungan V untuk "<?= $contoh_alt ?>":</strong>
            <p class="mb-0 mt-2">
                $$ V = \frac{D^{-}}{D^{-} + D^{+}} = \frac{<?= $d_negatif[$contoh_alt_id] ?>}{<?= $d_negatif[$contoh_alt_id] ?> + <?= $d_positif[$contoh_alt_id] ?>} = \frac{<?= $d_negatif[$contoh_alt_id] ?>}{<?= round($d_negatif[$contoh_alt_id] + $d_positif[$contoh_alt_id], 4) ?>} = <?= round($d_negatif[$contoh_alt_id] / ($d_negatif[$contoh_alt_id] + $d_positif[$contoh_alt_id]), 4) ?> $$
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Alternatif</th>
                        <th class="text-center">D−</th>
                        <th class="text-center">D+</th>
                        <th class="text-center">D− + D+</th>
                        <th class="text-center">V</th>
                        <th class="text-center" style="width:90px">Peringkat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank_map = [];
                    $rank = 1;
                    foreach ($hasil_akhir as $h) {
                        $rank_map[$h['id']] = $rank++;
                    }
                    foreach ($alternatif as $id_a => $a):
                        $total_d = $d_negatif[$id_a] + $d_positif[$id_a];
                        $v       = $total_d > 0 ? round($d_negatif[$id_a] / $total_d, 4) : 0;
                    ?>
                    <tr <?= $rank_map[$id_a] === 1 ? 'class="table-success fw-bold"' : '' ?>>
                        <td><?= htmlspecialchars($a['nama_alternatif']) ?></td>
                        <td class="text-center"><?= $d_negatif[$id_a] ?></td>
                        <td class="text-center"><?= $d_positif[$id_a] ?></td>
                        <td class="text-center"><?= round($total_d, 4) ?></td>
                        <td class="text-center"><?= $v ?></td>
                        <td class="text-center"><?= $rank_map[$id_a] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-success mt-3 mb-0">
            <strong>Kesimpulan:</strong> Alternatif terbaik adalah
            <strong><?= htmlspecialchars($hasil_akhir[0]['nama']) ?></strong>
            dengan nilai preferensi V = <strong><?= $hasil_akhir[0]['nilai_v'] ?></strong>.
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>