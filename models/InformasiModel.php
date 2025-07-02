<?php
class InformasiModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllInformasi() {
        $stmt = $this->db->prepare("
            SELECT i.*, u.nama_lengkap as author_name 
            FROM informasi i
            JOIN users u ON i.created_by = u.id
            ORDER BY i.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInformasiById($id) {
        $stmt = $this->db->prepare("
            SELECT i.*, u.nama_lengkap as author_name 
            FROM informasi i
            JOIN users u ON i.created_by = u.id
            WHERE i.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createInformasi($data) {
        $stmt = $this->db->prepare("
            INSERT INTO informasi (judul, konten, gambar, created_by) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "sssi",
            $data['judul'],
            $data['konten'],
            $data['gambar'],
            $data['created_by']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateInformasi($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['judul'])) {
            $fields[] = "judul = ?";
            $types .= "s";
            $values[] = $data['judul'];
        }

        if (isset($data['konten'])) {
            $fields[] = "konten = ?";
            $types .= "s";
            $values[] = $data['konten'];
        }

        if (isset($data['gambar'])) {
            $fields[] = "gambar = ?";
            $types .= "s";
            $values[] = $data['gambar'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE informasi SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteInformasi($id) {
        $stmt = $this->db->prepare("DELETE FROM informasi WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function searchInformasi($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT i.*, u.nama_lengkap as author_name 
            FROM informasi i
            JOIN users u ON i.created_by = u.id
            WHERE i.judul LIKE ? OR i.konten LIKE ?
            ORDER BY i.created_at DESC
            LIMIT 20
        ");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInformasiByAuthor($authorId) {
        $stmt = $this->db->prepare("
            SELECT i.*, u.nama_lengkap as author_name 
            FROM informasi i
            JOIN users u ON i.created_by = u.id
            WHERE i.created_by = ?
            ORDER BY i.created_at DESC
        ");
        $stmt->bind_param("i", $authorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInformasiStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_articles,
                COUNT(DISTINCT created_by) as total_authors,
                AVG(CHAR_LENGTH(konten)) as avg_content_length,
                COUNT(CASE WHEN gambar IS NOT NULL THEN 1 END) as articles_with_image,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_articles
            FROM informasi
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getPopularInformasi($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT i.*, u.nama_lengkap as author_name,
                CHAR_LENGTH(i.konten) as content_length,
                CASE 
                    WHEN i.gambar IS NOT NULL THEN 1 
                    ELSE 0 
                END as has_image
            FROM informasi i
            JOIN users u ON i.created_by = u.id
            ORDER BY 
                has_image DESC,
                content_length DESC,
                i.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRecentInformasi($days = 30, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT i.*, u.nama_lengkap as author_name 
            FROM informasi i
            JOIN users u ON i.created_by = u.id
            WHERE i.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY i.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $days, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getOutdatedInformasi($months = 12) {
        $stmt = $this->db->prepare("
            SELECT i.*, u.nama_lengkap as author_name,
                DATEDIFF(NOW(), i.created_at) as days_old
            FROM informasi i
            JOIN users u ON i.created_by = u.id
            WHERE i.created_at <= DATE_SUB(NOW(), INTERVAL ? MONTH)
            ORDER BY i.created_at ASC
        ");
        $stmt->bind_param("i", $months);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAuthorStats() {
        $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.nama_lengkap,
                COUNT(i.id) as total_articles,
                MAX(i.created_at) as last_article_date,
                AVG(CHAR_LENGTH(i.konten)) as avg_content_length
            FROM users u
            LEFT JOIN informasi i ON u.id = i.created_by
            WHERE u.role IN ('admin', 'pengepul', 'roasting')
            GROUP BY u.id, u.nama_lengkap
            ORDER BY total_articles DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInformasiByCategory($keyword) {
        $searchTerm = "%$keyword%";
        $stmt = $this->db->prepare("
            SELECT i.*, u.nama_lengkap as author_name 
            FROM informasi i
            JOIN users u ON i.created_by = u.id
            WHERE i.judul LIKE ? OR i.konten LIKE ?
            ORDER BY 
                CASE 
                    WHEN i.judul LIKE ? THEN 1 
                    ELSE 2 
                END,
                i.created_at DESC
        ");
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInformasiSummary() {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                judul,
                SUBSTRING(konten, 1, 200) as excerpt,
                gambar,
                created_at
            FROM informasi
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function bulkDeleteOutdated($months = 24) {
        $stmt = $this->db->prepare("
            DELETE FROM informasi 
            WHERE created_at <= DATE_SUB(NOW(), INTERVAL ? MONTH)
        ");
        $stmt->bind_param("i", $months);
        
        if ($stmt->execute()) {
            return $stmt->affected_rows;
        }
        return false;
    }
}