<?php
$page_title = $page_title ?? 'Admin Panel';
$show_user  = $show_user  ?? true;
?>
<!DOCTYPE html>
<html lang="id">
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
        #page-content-wrapper { width: 100%; background-color: #f8f9fc; min-height: 100vh; }
        .topbar { background-color: white; box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15); }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light topbar px-4 py-3 mb-4">
                <span class="navbar-brand mb-0 h1"><?= htmlspecialchars($page_title) ?></span>
                <?php if ($show_user): ?>
                <div class="ms-auto d-flex align-items-center">
                    <span class="me-2 text-muted">Administrator</span>
                    <i class="bi bi-person-circle fs-3 text-secondary"></i>
                </div>
                <?php endif; ?>
            </nav>

            <div class="container-fluid px-4">