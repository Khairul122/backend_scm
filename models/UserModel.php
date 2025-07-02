<?php
class UserModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllUsers() {
        $stmt = $this->db->prepare("
            SELECT id, nama_lengkap, email, no_telepon, alamat, role, nama_toko, status, created_at 
            FROM users 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getUsersByRole($role) {
        $stmt = $this->db->prepare("
            SELECT id, nama_lengkap, email, no_telepon, alamat, role, nama_toko, status, created_at 
            FROM users 
            WHERE role = ? 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getUserById($id) {
        $stmt = $this->db->prepare("
            SELECT id, nama_lengkap, email, no_telepon, alamat, role, nama_toko, status, created_at 
            FROM users 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createUser($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (nama_lengkap, email, no_telepon, alamat, password, role, nama_toko, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $status = $data['status'] ?? 'aktif';
        
        $stmt->bind_param(
            "ssssssss",
            $data['nama_lengkap'],
            $data['email'],
            $data['no_telepon'],
            $data['alamat'],
            $hashedPassword,
            $data['role'],
            $data['nama_toko'],
            $status
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateUser($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['nama_lengkap'])) {
            $fields[] = "nama_lengkap = ?";
            $types .= "s";
            $values[] = $data['nama_lengkap'];
        }

        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $types .= "s";
            $values[] = $data['email'];
        }

        if (isset($data['no_telepon'])) {
            $fields[] = "no_telepon = ?";
            $types .= "s";
            $values[] = $data['no_telepon'];
        }

        if (isset($data['alamat'])) {
            $fields[] = "alamat = ?";
            $types .= "s";
            $values[] = $data['alamat'];
        }

        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $types .= "s";
            $values[] = $data['role'];
        }

        if (isset($data['nama_toko'])) {
            $fields[] = "nama_toko = ?";
            $types .= "s";
            $values[] = $data['nama_toko'];
        }

        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $types .= "s";
            $values[] = $data['status'];
        }

        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $types .= "s";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteUser($id) {
        $stmt = $this->db->prepare("UPDATE users SET status = 'nonaktif' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function updateUserStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function resetPassword($id, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $id);
        return $stmt->execute();
    }

    public function searchUsers($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT id, nama_lengkap, email, no_telepon, alamat, role, nama_toko, status, created_at 
            FROM users 
            WHERE nama_lengkap LIKE ? OR email LIKE ? OR no_telepon LIKE ? OR nama_toko LIKE ?
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getUserStats() {
        $stmt = $this->db->prepare("
            SELECT 
                role,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) as nonaktif
            FROM users 
            GROUP BY role
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function checkEmailExists($email, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $excludeId);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
        }
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function checkPhoneExists($phone, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE no_telepon = ? AND id != ?");
            $stmt->bind_param("si", $phone, $excludeId);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE no_telepon = ?");
            $stmt->bind_param("s", $phone);
        }
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}