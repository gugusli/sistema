<?php
// Router para el servidor built-in de PHP
// Sirve archivos estáticos desde /public/ y delega el resto a index.php

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Si el archivo existe físicamente, servirlo directamente
$file = __DIR__ . $path;
if ($path !== '/' && file_exists($file) && is_file($file)) {
    return false; // PHP built-in lo sirve solo
}

// Todo lo demás va al router principal
require __DIR__ . '/index.php';
