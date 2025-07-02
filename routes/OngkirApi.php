<?php

require_once __DIR__ . '/../controllers/OngkirController.php';
require_once __DIR__ . '/../models/OngkirModel.php';

$router->get('/api/ongkir/provinsi', function() {
    $controller = new OngkirController();
    $controller->getProvinsi();
});

$router->get('/api/ongkir/kota', function() {
    $controller = new OngkirController();
    $controller->getKota();
});

$router->get('/api/ongkir/kota/search', function() {
    $controller = new OngkirController();
    $controller->searchKota();
});

$router->get('/api/ongkir/provinsi/{id}', function($id) {
    $controller = new OngkirController();
    $controller->getProvinsiById($id);
});

$router->get('/api/ongkir/kota/{id}', function($id) {
    $controller = new OngkirController();
    $controller->getKotaById($id);
});

$router->post('/api/ongkir/cost', function() {
    $controller = new OngkirController();
    $controller->getCost();
});

$router->post('/api/ongkir/import', function() {
    $controller = new OngkirController();
    $controller->importAll();
});