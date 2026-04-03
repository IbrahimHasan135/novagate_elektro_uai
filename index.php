<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/novagate_elektro_uai', '', $path);

$routes = [
    '/' => '/views/dashboard.php',
    '/dashboard' => '/views/dashboard.php',
    '/rfids' => '/views/rfids.php',
    '/add-rfid' => '/views/add-rfid.php',
    '/devices' => '/views/devices.php',
];

if (isset($routes[$path])) {
    require_once __DIR__ . $routes[$path];
} else {
    http_response_code(404);
    echo "<h1>404 - Halaman tidak ditemukan</h1>";
    echo "<p>Halaman yang Anda cari tidak ada.</p>";
}