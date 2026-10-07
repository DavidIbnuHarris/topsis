<?php if (!empty($use_mathjax)): ?>
<script>
window.MathJax = {
  tex: {
    inlineMath: [['$', '$'], ['\\(', '\\)']],
    displayMath: [['$$', '$$'], ['\\[', '\\]']]
  },
  svg: { fontCache: 'global' }
};
</script>
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>
<?php endif; ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SPK TOPSIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
    #sidebar-wrapper { min-height: 100vh; width: 250px; background-color: #4e73df; flex-shrink: 0; }
    .sidebar-heading { padding: 1rem 1.25rem; font-size: 1.2rem; font-weight: bold; color: white; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .list-group-item { background-color: transparent; color: white; border: none; padding: 15px 20px; border-radius: 0; }
    .list-group-item:hover { background-color: rgba(255,255,255,0.1); color: #fff; }
    .list-group-item.active-menu { background-color: rgba(255,255,255,0.2); color: #fff; border-left: 4px solid #fff; }
    .list-group-item.text-warning:hover { background-color: rgba(255, 193, 7, 0.15); color: #ffc107; }
    #page-content-wrapper { width: 100%; background-color: #f8f9fc; min-height: 100vh; }
    .topbar { background-color: white; box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15); }

    .step-card { border-left: 4px solid #4e73df; margin-bottom: 2rem; }
    .step-card .card-header { background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; }
    .step-number {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 50%;
        background: #4e73df; color: white; font-weight: bold; margin-right: 10px;
    }
    .formula-box {
        background: #f1f5ff; border-radius: 8px; padding: 15px 20px;
        margin-bottom: 15px; border-left: 4px solid #4e73df;
    }
    .formula-box .formula-title { font-weight: bold; color: #4e73df; margin-bottom: 8px; }
    .formula-box .formula-desc { font-size: 0.9rem; color: #555; margin-top: 8px; }
    .kriteria-th { font-size: 0.85rem; white-space: nowrap; }
</style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light topbar px-4 py-3 mb-4">
                <span class="navbar-brand mb-0 h1"><?= htmlspecialchars($page_title) ?></span>
                <?php if ($show_user ?? true): ?>
                <div class="ms-auto d-flex align-items-center">
                    <span class="me-2 text-muted">Administrator</span>
                    <i class="bi bi-person-circle fs-3 text-secondary"></i>
                </div>
                <?php endif; ?>
            </nav>

            <div class="container-fluid px-4">