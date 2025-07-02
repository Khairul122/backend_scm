<?php
class KategoriModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllKategori() {
        $stmt = $this->db->prepare("
            SELECT k.*, COUNT(p.id) as total_products
            FROM kategori_produk k
            LEFT JOIN produk p ON k.id = p.kategori_id AND p.status = 'aktif'
            GROUP BY k.id
            ORDER BY k.nama_kategori ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $kategori = [];
        while ($row = $result->fetch_assoc()) {
            $kategori[] = $row;
        }
        return $kategori;
    }

    public function getKategoriById($id) {
        $stmt = $this->db->prepare("
            SELECT k.*, COUNT(p.id) as total_products
            FROM kategori_produk k
            LEFT JOIN produk p ON k.id = p.kategori_id AND p.status = 'aktif'
            WHERE k.id = ?
            GROUP BY k.id
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createKategori($data) {
        $stmt = $this->db->prepare("
            INSERT INTO kategori_produk (nama_kategori, deskripsi) 
            VALUES (?, ?)
        ");
        $stmt->bind_param("ss", $data['nama_kategori'], $data['deskripsi']);
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateKategori($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE kategori_produk 
            SET nama_kategori = ?, deskripsi = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $data['nama_kategori'], $data['deskripsi'], $id);
        return $stmt->execute();
    }

    public function deleteKategori($id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as product_count 
            FROM produk 
            WHERE kategori_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['product_count'] > 0) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM kategori_produk WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function checkKategoriExists($nama_kategori, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("
                SELECT id FROM kategori_produk 
                WHERE nama_kategori = ? AND id != ?
            ");
            $stmt->bind_param("si", $nama_kategori, $excludeId);
        } else {
            $stmt = $this->db->prepare("
                SELECT id FROM kategori_produk 
                WHERE nama_kategori = ?
            ");
            $stmt->bind_param("s", $nama_kategori);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function getKategoriWithProducts($kategoriId) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_toko
            FROM produk p
            JOIN users u ON p.penjual_id = u.id
            WHERE p.kategori_id = ? AND p.status = 'aktif'
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $kategoriId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }
}