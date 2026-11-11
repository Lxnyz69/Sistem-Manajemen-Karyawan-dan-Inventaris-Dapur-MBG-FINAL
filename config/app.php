<?php
return [
    'name'     => env('APP_NAME', 'Manajemen Perusahaan'),
    'debug'    => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'timezone' => 'Asia/Jakarta',
    'per_page' => 10,
    'login_max_attempts' => 5,
    'login_window_minutes' => 15,
];
