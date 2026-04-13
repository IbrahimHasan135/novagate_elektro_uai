<?php
$currentPage = $page ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaGate - Smart Door Lock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar {
            position: fixed; top: 0; bottom: 0; left: 0; z-index: 100;
            padding: 48px 0 0; box-shadow: 0 0 15px rgba(0,0,0,0.1);
            background: #1a237e;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8); padding: 12px 20px;
            border-radius: 0; font-weight: 500;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1); color: white;
        }
        .sidebar .nav-link i { margin-right: 10px; }
        .main-content { margin-left: 250px; padding: 20px; margin-top: 60px; }
        .navbar-brand-custom {
            position: fixed; top: 0; left: 0; right: 0; z-index: 101;
            background: #151b60; padding: 12px 20px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand-custom .brand { color: white; font-weight: 600; font-size: 18px; }
        .navbar-brand-custom .brand i { margin-right: 8px; }
        .stat-card { border: none; border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .stat-icon { width: 55px; height: 55px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table-card { border-radius: 12px; }
        .table thead th { background: #f8f9fa; border-bottom: 2px solid #e9ecef; font-weight: 600; color: #495057; font-size: 13px; text-transform: uppercase; }
        .table-hover tbody tr:hover { background: #f8f9fa; }
        .badge-status { padding: 6px 12px; border-radius: 20px; font-weight: 500; font-size: 12px; }
        .badge-success-custom { background: #d4edda; color: #155724; }
        .badge-danger-custom { background: #f8d7da; color: #721c24; }
        .badge-warning-custom { background: #fff3cd; color: #856404; }
        .badge-info-custom { background: #cce5ff; color: #004085; }
        @media (max-width: 991px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <nav class="navbar-brand-custom">
        <div class="brand">
            <i class="bi bi-shield-lock"></i> NovaGate
            <span class="opacity-75" style="font-size: 14px; font-weight: 400;">Smart Door Lock</span>
        </div>
        <div class="d-flex align-items-center text-white">
            <i class="bi bi-clock me-2"></i>
            <span id="current-time"></span>
        </div>
    </nav>

    <nav class="sidebar d-none d-lg-block">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'rfids' ? 'active' : '' ?>" href="rfids.php">
                    <i class="bi bi-credit-card"></i> RFID Terdaftar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'add-rfid' ? 'active' : '' ?>" href="add-rfid.php">
                    <i class="bi bi-plus-circle"></i> Daftarkan RFID
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'devices' ? 'active' : '' ?>" href="devices.php">
                    <i class="bi bi-device-hdd"></i> Device
                </a>
            </li>
        </ul>
    </nav>

    <div class="main-content">