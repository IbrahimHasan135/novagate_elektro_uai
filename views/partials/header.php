<?php
$currentPage = $page ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaGate - Smart Door Lock</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <span>🔐</span> NovaGate <span>Smart Door Lock</span>
        </div>
        <div class="navbar-menu">
            <a href="/" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="/rfids" class="<?= $currentPage === 'rfids' ? 'active' : '' ?>">RFID Terdaftar</a>
            <a href="/add-rfid" class="<?= $currentPage === 'add-rfid' ? 'active' : '' ?>">Daftarkan RFID</a>
            <a href="/devices" class="<?= $currentPage === 'devices' ? 'active' : '' ?>">Device</a>
        </div>
    </nav>
    <main class="main-content">