<?php

require_once __DIR__ . '/../controllers/AlamatPengirimanController.php';
require_once __DIR__ . '/../models/AlamatPengirimanModel.php';

$router->get('/api/alamat', function() {
    $controller = new AlamatPengirimanController();
    $controller->getAllAlamat();
});

$router->get('/api/alamat/search', function() {
    $controller = new AlamatPengirimanController();
    $controller->searchAlamat();
});

$router->get('/api/alamat/stats', function() {
    $controller = new AlamatPengirimanController();
    $controller->getAlamatStats();
});

$router->get('/api/alamat/suspicious', function() {
    $controller = new AlamatPengirimanController();
    $controller->getSuspiciousAlamat();
});

$router->get('/api/alamat/{id}', function($id) {
    $controller = new AlamatPengirimanController();
    $controller->getAlamatById($id);
});

$router->post('/api/alamat', function() {
    $controller = new AlamatPengirimanController();
    $controller->createAlamat();
});

$router->post('/api/alamat/validate', function() {
    $controller = new AlamatPengirimanController();
    $controller->validateUserAlamat();
});

$router->put('/api/alamat/{id}', function($id) {
    $controller = new AlamatPengirimanController();
    $controller->updateAlamat($id);
});

$router->patch('/api/alamat/{id}/default', function($id) {
    $controller = new AlamatPengirimanController();
    $controller->setDefaultAlamat($id);
});

$router->delete('/api/alamat/{id}', function($id) {
    $controller = new AlamatPengirimanController();
    $controller->deleteAlamat($id);
});