<?php
session_start();

class AuthController {
    private $authModel;

    public function __construct() {
        $this->authModel = new AuthModel();
    }

    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['nama_lengkap']) || empty($input['email']) || empty($input['no_telepon']) || 
            empty($input['alamat']) || empty($input['password']) || empty($input['role'])) {
            response(400, ['error' => 'All fields are required']);
        }

        $validRoles = ['pembeli', 'pengepul', 'roasting', 'penjual'];
        if (!in_array($input['role'], $validRoles)) {
            response(400, ['error' => 'Invalid role']);
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            response(400, ['error' => 'Invalid email format']);
        }

        if ($this->authModel->checkEmailExists($input['email'])) {
            response(409, ['error' => 'Email already exists']);
        }

        if ($this->authModel->checkPhoneExists($input['no_telepon'])) {
            response(409, ['error' => 'Phone number already exists']);
        }

        $userId = $this->authModel->createUser($input);
        
        if ($userId) {
            response(201, ['message' => 'User registered successfully']);
        } else {
            response(500, ['error' => 'Failed to create user']);
        }
    }

    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['identifier']) || empty($input['password'])) {
            response(400, ['error' => 'Email/phone and password are required']);
        }

        $user = $this->authModel->getUserByIdentifier($input['identifier']);

        if (!$user || $user['password'] !== $input['password']) {
            response(401, ['error' => 'Invalid credentials']);
        }

        if ($user['status'] !== 'aktif') {
            response(403, ['error' => 'Account is inactive']);
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'nama_lengkap' => $user['nama_lengkap'],
            'email' => $user['email'],
            'no_telepon' => $user['no_telepon'],
            'alamat' => $user['alamat'],
            'role' => $user['role'],
            'nama_toko' => $user['nama_toko']
        ];
        
        response(200, ['message' => 'Login successful', 'user' => $_SESSION['user']]);
    }

    public function logout() {
        session_unset();
        session_destroy();
        response(200, ['message' => 'Logged out successfully']);
    }

    public function profile() {
        if (!isset($_SESSION['user'])) {
            response(401, ['error' => 'Unauthorized']);
        }

        response(200, ['user' => $_SESSION['user']]);
    }

    public function updateProfile() {
        if (!isset($_SESSION['user'])) {
            response(401, ['error' => 'Unauthorized']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $updateData = [];

        if (isset($input['nama_lengkap'])) {
            $updateData['nama_lengkap'] = $input['nama_lengkap'];
        }
        if (isset($input['alamat'])) {
            $updateData['alamat'] = $input['alamat'];
        }
        if (isset($input['nama_toko'])) {
            $updateData['nama_toko'] = $input['nama_toko'];
        }

        if (empty($updateData)) {
            response(400, ['error' => 'No data to update']);
        }

        $updated = $this->authModel->updateUser($_SESSION['user']['id'], $updateData);

        if ($updated) {
            foreach ($updateData as $key => $value) {
                $_SESSION['user'][$key] = $value;
            }
            response(200, ['message' => 'Profile updated', 'user' => $_SESSION['user']]);
        } else {
            response(500, ['error' => 'Update failed']);
        }
    }

    public function changePassword() {
        if (!isset($_SESSION['user'])) {
            response(401, ['error' => 'Unauthorized']);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['current_password']) || empty($input['new_password'])) {
            response(400, ['error' => 'Current and new password required']);
        }

        $userData = $this->authModel->getUserById($_SESSION['user']['id']);

        if ($userData['password'] !== $input['current_password']) {
            response(400, ['error' => 'Current password incorrect']);
        }

        $updated = $this->authModel->updatePassword($_SESSION['user']['id'], $input['new_password']);

        if ($updated) {
            response(200, ['message' => 'Password changed successfully']);
        } else {
            response(500, ['error' => 'Failed to change password']);
        }
    }
}