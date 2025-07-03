<?php
class BatchProduksiController {
    private $batchModel;

    public function __construct() {
        $this->batchModel = new BatchProduksiModel();
    }

    public function getAllBatch() {
        $status = $_GET['status'] ?? null;
        $petaniId = $_GET['petani_id'] ?? null;
        $jenisKopi = $_GET['jenis_kopi'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        if ($status) {
            $batch = $this->batchModel->getBatchByStatus($status);
        } elseif ($petaniId) {
            $batch = $this->batchModel->getBatchByPetani($petaniId);
        } elseif ($jenisKopi) {
            $batch = $this->batchModel->getBatchByJenisKopi($jenisKopi);
        } elseif ($startDate && $endDate) {
            $batch = $this->batchModel->getBatchByDateRange($startDate, $endDate);
        } else {
            $batch = $this->batchModel->getAllBatch();
        }
        
        response(200, ['data' => $batch]);
    }

    public function getBatchById($id) {
        $batch = $this->batchModel->getBatchById($id);
        
        if (!$batch) {
            response(404, ['error' => 'Batch not found']);
            return;
        }
        
        response(200, ['data' => $batch]);
    }

    public function createBatch() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateBatchInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        if (!isset($input['kode_batch'])) {
            $input['kode_batch'] = $this->batchModel->generateKodeBatch();
        }

        if ($this->batchModel->checkKodeBatchExists($input['kode_batch'])) {
            response(400, ['error' => 'Batch code already exists']);
            return;
        }

        $batchId = $this->batchModel->createBatch($input);
        
        if ($batchId) {
            $batch = $this->batchModel->getBatchById($batchId);
            response(201, ['message' => 'Batch created from farmer pickup', 'data' => $batch]);
        } else {
            response(500, ['error' => 'Failed to create batch']);
        }
    }

    public function updateBatch($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->batchModel->getBatchById($id)) {
            response(404, ['error' => 'Batch not found']);
            return;
        }

        if ($this->batchModel->updateBatch($id, $input)) {
            $batch = $this->batchModel->getBatchById($id);
            response(200, ['message' => 'Batch updated successfully', 'data' => $batch]);
        } else {
            response(500, ['error' => 'Failed to update batch']);
        }
    }

    public function updateBatchStatus($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['status']) || !in_array($input['status'], ['panen', 'proses', 'selesai', 'terjual'])) {
            response(400, ['error' => 'Invalid status']);
            return;
        }

        if (!$this->batchModel->getBatchById($id)) {
            response(404, ['error' => 'Batch not found']);
            return;
        }

        if ($this->batchModel->updateBatchStatus($id, $input['status'])) {
            response(200, ['message' => 'Batch status updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update batch status']);
        }
    }

    public function searchBatch() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $batch = $this->batchModel->searchBatch($query);
        response(200, ['data' => $batch]);
    }

    public function getBatchStats() {
        $stats = $this->batchModel->getBatchStats();
        response(200, ['data' => $stats]);
    }

    public function getProcessingBatches() {
        $batches = $this->batchModel->getProcessingBatches();
        
        response(200, [
            'message' => 'Batches currently in processing',
            'data' => $batches
        ]);
    }

    public function getProductionTrends() {
        $days = $_GET['days'] ?? 30;
        $trends = $this->batchModel->getProductionTrends($days);
        
        response(200, [
            'message' => "Production trends for last {$days} days",
            'data' => $trends
        ]);
    }

    public function getPetaniProductivity() {
        $productivity = $this->batchModel->getPetaniProductivity();
        
        response(200, [
            'message' => 'Farmer productivity analysis',
            'data' => $productivity
        ]);
    }

    public function getQualityAnalysis() {
        $quality = $this->batchModel->getQualityAnalysis();
        
        response(200, [
            'message' => 'Batch quality analysis and scores',
            'data' => $quality
        ]);
    }

    public function getRecentPickups() {
        $limit = $_GET['limit'] ?? 10;
        $pickups = $this->batchModel->getRecentPickups($limit);
        
        response(200, [
            'message' => "Recent {$limit} farmer pickups",
            'data' => $pickups
        ]);
    }

    public function getReadyForSale() {
        $batches = $this->batchModel->getReadyForSale();
        
        response(200, [
            'message' => 'Batches ready for sale',
            'data' => $batches
        ]);
    }

    public function getInventoryStatus() {
        $inventory = $this->batchModel->getInventoryStatus();
        
        response(200, [
            'message' => 'Current inventory status by type and status',
            'data' => $inventory
        ]);
    }

    public function getPriceAnalysis() {
        $analysis = $this->batchModel->getPriceAnalysis();
        
        response(200, [
            'message' => 'Price analysis by coffee type',
            'data' => $analysis
        ]);
    }

    public function getSlowMovingBatches() {
        $days = $_GET['days'] ?? 30;
        $batches = $this->batchModel->getSlowMovingBatches($days);
        
        response(200, [
            'message' => "Slow moving batches older than {$days} days",
            'data' => $batches
        ]);
    }

    public function bulkUpdateStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ids']) || !isset($input['status']) || !is_array($input['ids'])) {
            response(400, ['error' => 'Batch IDs and status required']);
            return;
        }

        if (!in_array($input['status'], ['panen', 'proses', 'selesai', 'terjual'])) {
            response(400, ['error' => 'Invalid status']);
            return;
        }

        if ($this->batchModel->bulkUpdateStatus($input['ids'], $input['status'])) {
            response(200, [
                'message' => 'Bulk status update completed',
                'updated_count' => count($input['ids'])
            ]);
        } else {
            response(500, ['error' => 'Failed to update batch statuses']);
        }
    }

    public function inputFromPickup() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validatePickupInput($input)) {
            response(400, ['error' => 'Invalid pickup data']);
            return;
        }

        $input['kode_batch'] = $this->batchModel->generateKodeBatch();
        $input['status'] = 'panen';

        $batchId = $this->batchModel->createBatch($input);
        
        if ($batchId) {
            $batch = $this->batchModel->getBatchById($batchId);
            response(201, [
                'message' => 'New batch registered from farmer pickup',
                'data' => $batch
            ]);
        } else {
            response(500, ['error' => 'Failed to register pickup batch']);
        }
    }

    public function updateQuantity($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['jumlah_kg']) || !is_numeric($input['jumlah_kg']) || $input['jumlah_kg'] <= 0) {
            response(400, ['error' => 'Valid quantity required']);
            return;
        }

        if (!$this->batchModel->getBatchById($id)) {
            response(404, ['error' => 'Batch not found']);
            return;
        }

        if ($this->batchModel->updateBatch($id, ['jumlah_kg' => $input['jumlah_kg']])) {
            response(200, ['message' => 'Batch quantity updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update batch quantity']);
        }
    }

    public function updateQualityScore($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['harga_per_kg']) || !is_numeric($input['harga_per_kg']) || $input['harga_per_kg'] <= 0) {
            response(400, ['error' => 'Valid price per kg required']);
            return;
        }

        if (!$this->batchModel->getBatchById($id)) {
            response(404, ['error' => 'Batch not found']);
            return;
        }

        if ($this->batchModel->updateBatch($id, ['harga_per_kg' => $input['harga_per_kg']])) {
            response(200, ['message' => 'Batch quality score updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update batch quality score']);
        }
    }

    public function getProductionOverview() {
        $stats = $this->batchModel->getBatchStats();
        $processing = $this->batchModel->getProcessingBatches();
        $trends = $this->batchModel->getProductionTrends(30);
        $productivity = $this->batchModel->getPetaniProductivity();
        $quality = $this->batchModel->getQualityAnalysis();
        $inventory = $this->batchModel->getInventoryStatus();
        
        response(200, [
            'message' => 'Comprehensive production overview',
            'data' => [
                'overview' => $stats,
                'processing_batches' => $processing,
                'trends' => $trends,
                'farmer_productivity' => $productivity,
                'quality_analysis' => $quality,
                'inventory_status' => $inventory
            ]
        ]);
    }

    public function generateBatchCode() {
        $code = $this->batchModel->generateKodeBatch();
        
        response(200, [
            'message' => 'Generated batch code',
            'kode_batch' => $code
        ]);
    }

    private function validateBatchInput($input) {
        $required = ['petani_id', 'jenis_kopi', 'jumlah_kg', 'tanggal_panen', 'harga_per_kg'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['petani_id'])) {
            return false;
        }

        if (!in_array($input['jenis_kopi'], ['arabika', 'robusta'])) {
            return false;
        }

        if (!is_numeric($input['jumlah_kg']) || $input['jumlah_kg'] <= 0) {
            return false;
        }

        if (!is_numeric($input['harga_per_kg']) || $input['harga_per_kg'] <= 0) {
            return false;
        }

        return true;
    }

    private function validatePickupInput($input) {
        $required = ['petani_id', 'jenis_kopi', 'jumlah_kg', 'tanggal_panen', 'harga_per_kg'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['petani_id'])) {
            return false;
        }

        if (!in_array($input['jenis_kopi'], ['arabika', 'robusta'])) {
            return false;
        }

        if (!is_numeric($input['jumlah_kg']) || $input['jumlah_kg'] <= 0) {
            return false;
        }

        if (!is_numeric($input['harga_per_kg']) || $input['harga_per_kg'] <= 0) {
            return false;
        }

        if (strtotime($input['tanggal_panen']) > time()) {
            return false;
        }

        return true;
    }
}