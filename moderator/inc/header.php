<?php
if (!defined('in_nia_app')) exit;
$admin_title = isset($admin_title) ? $admin_title : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo _e($admin_title); ?> – <?php echo _e(get_option('sitename', 'Nia App')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f5f6f8; }
        .admin-sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1030; }
        .admin-main { flex: 1; min-height: 100vh; margin-left: 260px; transition: margin-left 0.3s ease; }
        .card { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); transition: transform 0.2s; border-radius: 10px; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025); }
        .card-header { background-color: #fff; border-bottom: 1px solid rgba(0,0,0,.05); font-weight: 600; padding: 1rem 1.25rem; border-radius: 10px 10px 0 0 !important; }
        .table { background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .table thead th { background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; color: #495057; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .table tbody td { vertical-align: middle; padding: 0.75rem 1rem; color: #3f4254; }
        .btn-primary { background-color: #3699ff; border-color: #3699ff; }
        .btn-primary:hover { background-color: #187de4; border-color: #187de4; }
        .pagination .page-link { color: #3699ff; border-color: #e4e6ef; }
        .pagination .page-item.active .page-link { background-color: #3699ff; border-color: #3699ff; }
        @media (max-width: 991.98px) {
            .admin-sidebar { left: -260px !important; transition: left 0.3s ease; z-index: 1040 !important; }
            .admin-sidebar.show { left: 0 !important; }
            .admin-sidebar-overlay.show { display: block; }
            .admin-main { margin-left: 0; padding-top: 5rem !important; }
        }
    </style>
</head>
<body>
<div class="d-flex position-relative">
    <div class="admin-sidebar-overlay" id="sidebarOverlay"></div>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="admin-main p-4 w-100 overflow-hidden">
        <div class="d-flex align-items-center mb-4">
            <button class="btn btn-light d-lg-none me-3 shadow-sm border" id="sidebarToggle">
                <span class="material-icons align-middle">menu</span>
            </button>
            <h1 class="h4 mb-0"><?php echo _e($admin_title); ?></h1>
        </div>
