<?php
class AuthModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function createUser($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (nama_lengkap, email, no_telepon, alamat, password, role, nama_toko) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $nama_toko = isset($data['nama_toko']) ? $data['nama_toko'] : null;

        $stmt->bind_param(
            "sssssss",
            $data['nama_lengkap'],
            $data['email'],
            $data['no_telepon'],
            $data['alamat'],
            $data['password'],
            $data['role'],
            $nama_toko
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function checkEmailExists($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function checkPhoneExists($phone) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE no_telepon = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function getUserByIdentifier($identifier) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? OR no_telepon = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function updateUser($id, $data) {
        $setClause = [];
        $types = "";
        $values = [];

        foreach ($data as $field => $value) {
            $setClause[] = "$field = ?";
            $types .= "s";
            $values[] = $value;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE users SET " . implode(", ", $setClause) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);

        return $stmt->execute();
    }

    public function updatePassword($id, $hashedPassword) {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $id);
        return $stmt->execute();
    }
}