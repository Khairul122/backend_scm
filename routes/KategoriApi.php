<?php

require_once __DIR__ . '/../controllers/KategoriController.php';
require_once __DIR__ . '/../models/KategoriModel.php';

$router->get('/api/kategori', function() {
    $controller = new KategoriController();
    $controller->index();
});

$router->post('/api/kategori', function() {
    $controller = new KategoriController();
    $controller->store();
});

$router->get('/api/kategori/{id}', function() {
    $controller = new KategoriController();
    $controller->show();
});

$router->put('/api/kategori/{id}', function() {
    $controller = new KategoriController();
    $controller->update();
});

$router->delete('/api/kategori/{id}', function() {
    $controller = new KategoriController();
    $controller->delete();
});

$router->get('/api/kategori/{id}/products', function() {
    $controller = new KategoriController();
    $controller->getProducts();
});