<?php
class RoastProductModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllRoastedProducts($penjualId) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori, u.nama_lengkap as roaster_name
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            WHERE p.penjual_id = ? AND p.kategori_id = 2
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoastedProductById($id, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori, u.nama_lengkap as roaster_name
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            WHERE p.id = ? AND p.penjual_id = ? AND p.kategori_id = 2
        ");
        $stmt->bind_param("ii", $id, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getRoastedProductsByStatus($status, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE p.status = ? AND p.penjual_id = ? AND p.kategori_id = 2
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("si", $status, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createRoastedProduct($data) {
        $data['kategori_id'] = 2;
        
        $stmt = $this->db->prepare("
            INSERT INTO produk (nama_produk, deskripsi, harga, stok, kategori_id, penjual_id, foto, berat, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = $data['status'] ?? 'aktif';
        
        $stmt->bind_param(
            "ssdiiisds",
            $data['nama_produk'],
            $data['deskripsi'],
            $data['harga'],
            $data['stok'],
            $data['kategori_id'],
            $data['penjual_id'],
            $data['foto'],
            $data['berat'],
            $status
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateRoastedProduct($id, $data, $penjualId) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['nama_produk'])) {
            $fields[] = "nama_produk = ?";
            $types .= "s";
            $values[] = $data['nama_produk'];
        }

        if (isset($data['deskripsi'])) {
            $fields[] = "deskripsi = ?";
            $types .= "s";
            $values[] = $data['deskripsi'];
        }

        if (isset($data['harga'])) {
            $fields[] = "harga = ?";
            $types .= "d";
            $values[] = $data['harga'];
        }

        if (isset($data['stok'])) {
            $fields[] = "stok = ?";
            $types .= "i";
            $values[] = $data['stok'];
        }

        if (isset($data['foto'])) {
            $fields[] = "foto = ?";
            $types .= "s";
            $values[] = $data['foto'];
        }

        if (isset($data['berat'])) {
            $fields[] = "berat = ?";
            $types .= "d";
            $values[] = $data['berat'];
        }

        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $types .= "s";
            $values[] = $data['status'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "ii";
        $values[] = $id;
        $values[] = $penjualId;

        $sql = "UPDATE produk SET " . implode(", ", $fields) . " WHERE id = ? AND penjual_id = ? AND kategori_id = 2";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteRoastedProduct($id, $penjualId) {
        $stmt = $this->db->prepare("UPDATE produk SET status = 'nonaktif' WHERE id = ? AND penjual_id = ? AND kategori_id = 2");
        $stmt->bind_param("ii", $id, $penjualId);
        return $stmt->execute();
    }

    public function updateRoastProfile($id, $roastData, $penjualId) {
        $roastProfile = $this->buildRoastProfileDescription($roastData);
        
        $stmt = $this->db->prepare("UPDATE produk SET deskripsi = ? WHERE id = ? AND penjual_id = ? AND kategori_id = 2");
        $stmt->bind_param("sii", $roastProfile, $id, $penjualId);
        return $stmt->execute();
    }

    public function updatePricing($id, $harga, $penjualId) {
        $stmt = $this->db->prepare("UPDATE produk SET harga = ? WHERE id = ? AND penjual_id = ? AND kategori_id = 2");
        $stmt->bind_param("dii", $harga, $id, $penjualId);
        return $stmt->execute();
    }

    public function discontinueProduct($id, $penjualId) {
        $stmt = $this->db->prepare("UPDATE produk SET status = 'nonaktif' WHERE id = ? AND penjual_id = ? AND kategori_id = 2");
        $stmt->bind_param("ii", $id, $penjualId);
        return $stmt->execute();
    }

    public function getRoastedProductStats($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_produk,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif_count,
                SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) as discontinued_count,
                SUM(stok) as total_stok,
                AVG(harga) as avg_harga,
                MIN(harga) as min_harga,
                MAX(harga) as max_harga,
                SUM(stok * harga) as total_nilai_stok
            FROM produk
            WHERE penjual_id = ? AND kategori_id = 2
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function searchRoastedProducts($query, $penjualId) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE (p.nama_produk LIKE ? OR p.deskripsi LIKE ?) 
            AND p.penjual_id = ? AND p.kategori_id = 2
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("ssi", $searchTerm, $searchTerm, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoastLevelAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN p.deskripsi LIKE '%light roast%' OR p.deskripsi LIKE '%light%' THEN 'Light Roast'
                    WHEN p.deskripsi LIKE '%medium roast%' OR p.deskripsi LIKE '%medium%' THEN 'Medium Roast'
                    WHEN p.deskripsi LIKE '%dark roast%' OR p.deskripsi LIKE '%dark%' THEN 'Dark Roast'
                    WHEN p.deskripsi LIKE '%espresso%' THEN 'Espresso Roast'
                    ELSE 'Unspecified'
                END as roast_level,
                COUNT(*) as product_count,
                SUM(p.stok) as total_stock,
                AVG(p.harga) as avg_price
            FROM produk p
            WHERE p.penjual_id = ? AND p.kategori_id = 2 AND p.status = 'aktif'
            GROUP BY roast_level
            ORDER BY product_count DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getBrewingMethodAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                CASE 
                    WHEN p.deskripsi LIKE '%espresso%' THEN 'Espresso'
                    WHEN p.deskripsi LIKE '%pour over%' OR p.deskripsi LIKE '%v60%' THEN 'Pour Over'
                    WHEN p.deskripsi LIKE '%french press%' THEN 'French Press'
                    WHEN p.deskripsi LIKE '%drip%' THEN 'Drip Coffee'
                    WHEN p.deskripsi LIKE '%cold brew%' THEN 'Cold Brew'
                    ELSE 'Multi-method'
                END as brewing_method,
                p.harga,
                p.stok
            FROM produk p
            WHERE p.penjual_id = ? AND p.kategori_id = 2 AND p.status = 'aktif'
            ORDER BY p.nama_produk
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCuppingNotesAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.nama_produk,
                p.deskripsi,
                CASE 
                    WHEN p.deskripsi LIKE '%fruity%' OR p.deskripsi LIKE '%berry%' OR p.deskripsi LIKE '%citrus%' THEN 'Fruity'
                    WHEN p.deskripsi LIKE '%nutty%' OR p.deskripsi LIKE '%chocolate%' OR p.deskripsi LIKE '%caramel%' THEN 'Nutty/Sweet'
                    WHEN p.deskripsi LIKE '%floral%' OR p.deskripsi LIKE '%jasmine%' THEN 'Floral'
                    WHEN p.deskripsi LIKE '%earthy%' OR p.deskripsi LIKE '%woody%' THEN 'Earthy'
                    WHEN p.deskripsi LIKE '%spicy%' OR p.deskripsi LIKE '%herb%' THEN 'Spicy/Herbal'
                    ELSE 'Complex'
                END as flavor_profile,
                p.harga,
                p.stok
            FROM produk p
            WHERE p.penjual_id = ? AND p.kategori_id = 2 AND p.status = 'aktif'
            ORDER BY flavor_profile, p.nama_produk
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPackagingSizeAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN p.berat <= 100 THEN 'Small (≤100g)'
                    WHEN p.berat <= 250 THEN 'Medium (101-250g)'
                    WHEN p.berat <= 500 THEN 'Large (251-500g)'
                    WHEN p.berat <= 1000 THEN 'XL (501-1000g)'
                    ELSE 'Bulk (>1000g)'
                END as package_size,
                COUNT(*) as product_count,
                SUM(p.stok) as total_stock,
                AVG(p.harga) as avg_price,
                AVG(p.harga / p.berat * 1000) as avg_price_per_kg
            FROM produk p
            WHERE p.penjual_id = ? AND p.kategori_id = 2 AND p.status = 'aktif'
            GROUP BY package_size
            ORDER BY avg_price_per_kg ASC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function checkRoastedProductOwnership($produkId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT id FROM produk 
            WHERE id = ? AND penjual_id = ? AND kategori_id = 2
        ");
        $stmt->bind_param("ii", $produkId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function validateRoastedProduct($data) {
        $requiredTerms = ['roast', 'coffee', 'bubuk', 'kopi'];
        $productName = strtolower($data['nama_produk']);
        
        foreach ($requiredTerms as $term) {
            if (strpos($productName, $term) !== false) {
                return true;
            }
        }
        
        return false;
    }

    public function buildRoastProfileDescription($roastData) {
        $description = "";
        
        if (isset($roastData['base_description'])) {
            $description .= $roastData['base_description'] . "\n\n";
        }
        
        if (isset($roastData['roast_level'])) {
            $description .= "🔥 Roast Level: " . $roastData['roast_level'] . "\n";
        }
        
        if (isset($roastData['cupping_notes'])) {
            $description .= "☕ Cupping Notes: " . $roastData['cupping_notes'] . "\n";
        }
        
        if (isset($roastData['brewing_recommendations'])) {
            $description .= "📖 Brewing Recommendations: " . $roastData['brewing_recommendations'] . "\n";
        }
        
        if (isset($roastData['origin'])) {
            $description .= "🌍 Origin: " . $roastData['origin'] . "\n";
        }
        
        if (isset($roastData['processing_method'])) {
            $description .= "⚙️ Processing: " . $roastData['processing_method'] . "\n";
        }
        
        if (isset($roastData['roast_date'])) {
            $description .= "📅 Roasted on: " . $roastData['roast_date'] . "\n";
        }
        
        return trim($description);
    }

    public function getRecentRoasts($penjualId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE p.penjual_id = ? AND p.kategori_id = 2
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $penjualId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPremiumRoasts($penjualId, $minPrice = 50000) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori,
                (p.harga / p.berat * 1000) as price_per_kg
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE p.penjual_id = ? AND p.kategori_id = 2 
            AND p.harga >= ? AND p.status = 'aktif'
            ORDER BY price_per_kg DESC
        ");
        $stmt->bind_param("id", $penjualId, $minPrice);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function bulkUpdateRoastProfiles($updates, $penjualId) {
        $success = 0;
        
        foreach ($updates as $update) {
            if (isset($update['id']) && isset($update['roast_data'])) {
                if ($this->updateRoastProfile($update['id'], $update['roast_data'], $penjualId)) {
                    $success++;
                }
            }
        }
        
        return $success;
    }

    public function bulkUpdatePricing($updates, $penjualId) {
        $success = 0;
        
        foreach ($updates as $update) {
            if (isset($update['id']) && isset($update['harga'])) {
                if ($this->updatePricing($update['id'], $update['harga'], $penjualId)) {
                    $success++;
                }
            }
        }
        
        return $success;
    }

    public function getDiscontinuedProducts($penjualId) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori,
                DATEDIFF(NOW(), p.created_at) as days_since_creation
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE p.penjual_id = ? AND p.kategori_id = 2 AND p.status = 'nonaktif'
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}