<?php

require_once __DIR__ . '/../controllers/PembeliAlamatController.php';
require_once __DIR__ . '/../models/PembeliAlamatModel.php';

$router->get('/api/pembeli-alamat', function() {
    $controller = new PembeliAlamatController();
    $controller->getAllAddresses();
});

$router->get('/api/pembeli-alamat/{id}', function($id) {
    $controller = new PembeliAlamatController();
    $controller->getAddressById($id);
});

$router->get('/api/pembeli-alamat/search', function() {
    $controller = new PembeliAlamatController();
    $controller->searchAddresses();
});

$router->get('/api/pembeli-alamat/default', function() {
    $controller = new PembeliAlamatController();
    $controller->getDefaultAddress();
});

$router->get('/api/pembeli-alamat/city/{city_id}', function($city_id) {
    $controller = new PembeliAlamatController();
    $controller->getAddressesByCity($city_id);
});

$router->get('/api/pembeli-alamat/province/{province_id}', function($province_id) {
    $controller = new PembeliAlamatController();
    $controller->getAddressesByProvince($province_id);
});

$router->get('/api/pembeli-alamat/statistics', function() {
    $controller = new PembeliAlamatController();
    $controller->getAddressStatistics();
});

$router->get('/api/pembeli-alamat/frequently-used', function() {
    $controller = new PembeliAlamatController();
    $controller->getFrequentlyUsedAddresses();
});

$router->get('/api/pembeli-alamat/usage-history/{id}', function($id) {
    $controller = new PembeliAlamatController();
    $controller->getAddressUsageHistory($id);
});

$router->get('/api/pembeli-alamat/overview', function() {
    $controller = new PembeliAlamatController();
    $controller->getAddressOverview();
});

$router->get('/api/pembeli-alamat/provinces', function() {
    $controller = new PembeliAlamatController();
    $controller->getProvinces();
});

$router->get('/api/pembeli-alamat/cities/{province_id}', function($province_id) {
    $controller = new PembeliAlamatController();
    $controller->getCitiesByProvince($province_id);
});

$router->post('/api/pembeli-alamat', function() {
    $controller = new PembeliAlamatController();
    $controller->createAddress();
});

$router->post('/api/pembeli-alamat/{id}/duplicate', function($id) {
    $controller = new PembeliAlamatController();
    $controller->duplicateAddress($id);
});

$router->put('/api/pembeli-alamat/{id}', function($id) {
    $controller = new PembeliAlamatController();
    $controller->updateAddress($id);
});

$router->patch('/api/pembeli-alamat/{id}/set-default', function($id) {
    $controller = new PembeliAlamatController();
    $controller->setDefaultAddress($id);
});

$router->delete('/api/pembeli-alamat/{id}', function($id) {
    $controller = new PembeliAlamatController();
    $controller->deleteAddress($id);
});