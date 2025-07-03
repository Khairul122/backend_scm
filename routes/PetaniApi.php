<?php

require_once __DIR__ . '/../controllers/PetaniController.php';
require_once __DIR__ . '/../models/PetaniModel.php';

$router->get('/api/petani', function() {
    $controller = new PetaniController();
    $controller->getAllPetani();
});

$router->get('/api/petani/search', function() {
    $controller = new PetaniController();
    $controller->searchPetani();
});

$router->get('/api/petani/stats', function() {
    $controller = new PetaniController();
    $controller->getPetaniStats();
});

$router->get('/api/petani/capacity', function() {
    $controller = new PetaniController();
    $controller->getPetaniCapacity();
});

$router->get('/api/petani/territory-stats', function() {
    $controller = new PetaniController();
    $controller->getTerritoryStats();
});

$router->get('/api/petani/production', function() {
    $controller = new PetaniController();
    $controller->getPetaniProduction();
});

$router->get('/api/petani/top-producers', function() {
    $controller = new PetaniController();
    $controller->getTopProducers();
});

$router->get('/api/petani/network', function() {
    $controller = new PetaniController();
    $controller->getPetaniNetwork();
});

$router->get('/api/petani/{id}', function($id) {
    $controller = new PetaniController();
    $controller->getPetaniById($id);
});

$router->post('/api/petani', function() {
    $controller = new PetaniController();
    $controller->createPetani();
});

$router->post('/api/petani/onboard', function() {
    $controller = new PetaniController();
    $controller->onboardNewFarmer();
});

$router->post('/api/petani/bulk-territory', function() {
    $controller = new PetaniController();
    $controller->bulkUpdateTerritory();
});

$router->put('/api/petani/{id}', function($id) {
    $controller = new PetaniController();
    $controller->updatePetani($id);
});

$router->patch('/api/petani/{id}/capacity', function($id) {
    $controller = new PetaniController();
    $controller->updateFarmerCapacity($id);
});

$router->patch('/api/petani/{id}/contact', function($id) {
    $controller = new PetaniController();
    $controller->updateFarmerContact($id);
});

$router->delete('/api/petani/{id}', function($id) {
    $controller = new PetaniController();
    $controller->deletePetani($id);
});