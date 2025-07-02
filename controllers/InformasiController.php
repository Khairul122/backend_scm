<?php
class InformasiController {
    private $informasiModel;

    public function __construct() {
        $this->informasiModel = new InformasiModel();
    }

    public function getAllInformasi() {
        $authorId = $_GET['author_id'] ?? null;
        $category = $_GET['category'] ?? null;
        $summary = $_GET['summary'] ?? false;
        
        if ($authorId) {
            $informasi = $this->informasiModel->getInformasiByAuthor($authorId);
        } elseif ($category) {
            $informasi = $this->informasiModel->getInformasiByCategory($category);
        } elseif ($summary) {
            $informasi = $this->informasiModel->getInformasiSummary();
        } else {
            $informasi = $this->informasiModel->getAllInformasi();
        }
        
        response(200, ['data' => $informasi]);
    }

    public function getInformasiById($id) {
        $informasi = $this->informasiModel->getInformasiById($id);
        
        if (!$informasi) {
            response(404, ['error' => 'Article not found']);
            return;
        }
        
        response(200, ['data' => $informasi]);
    }

    public function createInformasi() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateInformasiInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        $informasiId = $this->informasiModel->createInformasi($input);
        
        if ($informasiId) {
            $informasi = $this->informasiModel->getInformasiById($informasiId);
            response(201, ['message' => 'Article published successfully', 'data' => $informasi]);
        } else {
            response(500, ['error' => 'Failed to publish article']);
        }
    }

    public function updateInformasi($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->informasiModel->getInformasiById($id)) {
            response(404, ['error' => 'Article not found']);
            return;
        }

        if ($this->informasiModel->updateInformasi($id, $input)) {
            $informasi = $this->informasiModel->getInformasiById($id);
            response(200, ['message' => 'Article updated successfully', 'data' => $informasi]);
        } else {
            response(500, ['error' => 'Failed to update article']);
        }
    }

    public function deleteInformasi($id) {
        if (!$this->informasiModel->getInformasiById($id)) {
            response(404, ['error' => 'Article not found']);
            return;
        }

        if ($this->informasiModel->deleteInformasi($id)) {
            response(200, ['message' => 'Article deleted successfully']);
        } else {
            response(500, ['error' => 'Failed to delete article']);
        }
    }

    public function searchInformasi() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $informasi = $this->informasiModel->searchInformasi($query);
        response(200, ['data' => $informasi]);
    }

    public function getInformasiStats() {
        $stats = $this->informasiModel->getInformasiStats();
        response(200, ['data' => $stats]);
    }

    public function getPopularInformasi() {
        $limit = $_GET['limit'] ?? 10;
        $informasi = $this->informasiModel->getPopularInformasi($limit);
        
        response(200, [
            'message' => 'Popular articles based on content quality and engagement',
            'data' => $informasi
        ]);
    }

    public function getRecentInformasi() {
        $days = $_GET['days'] ?? 30;
        $limit = $_GET['limit'] ?? 10;
        $informasi = $this->informasiModel->getRecentInformasi($days, $limit);
        
        response(200, [
            'message' => "Recent articles from last {$days} days",
            'data' => $informasi
        ]);
    }

    public function getOutdatedInformasi() {
        $months = $_GET['months'] ?? 12;
        $informasi = $this->informasiModel->getOutdatedInformasi($months);
        
        response(200, [
            'message' => "Outdated articles older than {$months} months",
            'data' => $informasi
        ]);
    }

    public function getAuthorStats() {
        $stats = $this->informasiModel->getAuthorStats();
        
        response(200, [
            'message' => 'Author performance statistics',
            'data' => $stats
        ]);
    }

    public function bulkDeleteOutdated() {
        $input = json_decode(file_get_contents('php://input'), true);
        $months = $input['months'] ?? 24;
        
        $deletedCount = $this->informasiModel->bulkDeleteOutdated($months);
        
        if ($deletedCount !== false) {
            response(200, [
                'message' => 'Outdated articles deleted successfully',
                'deleted_count' => $deletedCount
            ]);
        } else {
            response(500, ['error' => 'Failed to delete outdated articles']);
        }
    }

    public function getInformasiPerformance() {
        $stats = $this->informasiModel->getInformasiStats();
        $authorStats = $this->informasiModel->getAuthorStats();
        $recent = $this->informasiModel->getRecentInformasi(30, 5);
        $popular = $this->informasiModel->getPopularInformasi(5);
        
        response(200, [
            'message' => 'Article performance overview',
            'data' => [
                'overview' => $stats,
                'author_performance' => $authorStats,
                'recent_articles' => $recent,
                'popular_articles' => $popular
            ]
        ]);
    }

    public function getInformasiByTopic() {
        $topics = [
            'pertanian' => $this->informasiModel->getInformasiByCategory('pertanian'),
            'kopi' => $this->informasiModel->getInformasiByCategory('kopi'),
            'tutorial' => $this->informasiModel->getInformasiByCategory('tutorial'),
            'tips' => $this->informasiModel->getInformasiByCategory('tips'),
            'berita' => $this->informasiModel->getInformasiByCategory('berita')
        ];
        
        response(200, [
            'message' => 'Articles categorized by topic',
            'data' => $topics
        ]);
    }

    private function validateInformasiInput($input) {
        $required = ['judul', 'konten', 'created_by'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['created_by'])) {
            return false;
        }

        if (strlen($input['judul']) < 10 || strlen($input['judul']) > 200) {
            return false;
        }

        if (strlen($input['konten']) < 50) {
            return false;
        }

        return true;
    }
}