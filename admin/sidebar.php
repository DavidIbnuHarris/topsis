<?php
$current = basename($_SERVER['PHP_SELF']);
$menus = [
    'index.php'       => ['label' => 'Dashboard',       'icon' => 'bi-speedometer2'],
    'alternatif.php'  => ['label' => 'Data Alternatif', 'icon' => 'bi-box'],
    'kriteria.php'    => ['label' => 'Data Kriteria',   'icon' => 'bi-list-check'],
    'perhitungan.php' => ['label' => 'Perhitungan',     'icon' => 'bi-calculator'],
];
?>
<div id="sidebar-wrapper" class="d-flex flex-column">
    <div class="sidebar-heading text-center">SPK TOPSIS</div>

    <div class="list-group list-group-flush mt-3 flex-grow-1">
        <?php foreach ($menus as $file => $menu): ?>
            <a href="<?= $file ?>"
               class="list-group-item <?= $current === $file ? 'active-menu fw-bold' : '' ?>">
                <i class="bi <?= $menu['icon'] ?> me-2"></i> <?= $menu['label'] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="list-group list-group-flush border-top border-light border-opacity-25">
        <a href="../logout.php"
           class="list-group-item text-warning fw-bold"
           onclick="return confirm('Yakin ingin logout?')">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>