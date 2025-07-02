<?php

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../controllers/' . $class . '.php',
        __DIR__ . '/../models/' . $class . '.php'
    ];
    
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once 'AuthApi.php';

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