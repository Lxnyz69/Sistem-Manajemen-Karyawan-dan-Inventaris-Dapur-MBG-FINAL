<?php
declare(strict_types=1);

require ROOT . '/app/helpers.php';
env_load(ROOT . '/.env');
$GLOBALS['config'] = require ROOT . '/config/app.php';
date_default_timezone_set($GLOBALS['config']['timezone']);

spl_autoload_register(function (string $c) {
    foreach (['Core', 'Controllers'] as $d) {
        $f = ROOT . "/app/$d/$c.php";
        if (is_file($f)) { require $f; return; }
    }
});
if (is_file(ROOT . '/vendor/autoload.php')) require ROOT . '/vendor/autoload.php';

ini_set('display_errors', $GLOBALS['config']['debug'] ? '1' : '0');
error_reporting(E_ALL);

session_name('cmsid');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

set_exception_handler(function (Throwable $e) {
    @file_put_contents(ROOT . '/storage/logs/app.log',
        '[' . date('c') . '] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n", FILE_APPEND);
    http_response_code(500);
    $msg = $GLOBALS['config']['debug'] ? $e->getMessage() : 'Terjadi kesalahan pada server. Coba lagi nanti.';
    echo view('errors/error', ['code' => 500, 'msg' => $msg], 'layouts/plain');
});

$router = new Router();
require ROOT . '/config/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());