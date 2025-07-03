<?php

require_once 'AuthApi.php';
require_once 'DashboardApi.php';
require_once 'KategoriApi.php';
require_once 'OngkirApi.php';
require_once 'AlamatPengirimanApi.php';
require_once 'UserApi.php';
require_once 'ReturApi.php';
require_once 'KurirApi.php';
require_once 'OngkirCacheApi.php';
require_once 'PesananApi.php';
require_once 'UlasanApi.php';
require_once 'PetaniApi.php';
require_once 'BatchProduksiApi.php';
require_once 'GudangApi.php';
require_once 'LimitProductApi.php';
require_once 'RoastProductApi.php';
require_once 'RoastProduksiApi.php';
require_once 'RoastGudangApi.php';
require_once 'RoastChatApi.php';
require_once 'PenjualProdukApi.php';
require_once 'PenjualGudangApi.php';
require_once 'PenjualPesananApi.php';
require_once 'PenjualChatApi.php';
require_once 'PembeliApi.php';
require_once 'PembeliAlamatApi.php';

$router->get('/api/health', function() {
    response(200, [
        'status' => 'OK',
        'message' => 'SCM Kopi API is running',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ]);
});

$router->get('/api', function() {
    response(200, [
        'message' => 'Welcome to SCM Komoditas Kopi API',
        'version' => '1.0.0',
        'endpoints' => [
            'auth' => '/api/auth/*',
            'dashboard' => '/api/dashboard/*',
            'health' => '/api/health'
        ]
    ]);
});

$router->get('/api/test', function() {
    response(200, [
        'message' => 'Test endpoint working',
        'database' => 'Connected to db_scm',
        'php_version' => phpversion()
    ]);
});