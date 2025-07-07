<?php
class AuthController {
    private $authModel;

    public function __construct() {
        $this->authModel = new AuthModel();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            response(405, ['error' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            response(400, [
                'error' => 'Invalid JSON input',
                'debug' => [
                    'raw_input' => file_get_contents('php://input'),
                    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set'
                ]
            ]);
            return;
        }

        if (empty($input['identifier']) || empty($input['password'])) {
            response(400, ['error' => 'Email/phone and password are required']);
            return;
        }

        $user = $this->authModel->getUserByIdentifier($input['identifier']);

        if (!$user) {
            response(401, ['error' => 'User not found']);
            return;
        }

        if ($user['password'] !== $input['password']) {
            response(401, ['error' => 'Invalid password']);
            return;
        }

        if ($user['status'] !== 'aktif') {
            response(403, ['error' => 'Account is inactive']);
            return;
        }

        $token = base64_encode(json_encode([
            'user_id' => $user['id'],
            'role' => $user['role'],
            'exp' => time() + (24 * 60 * 60)
        ]));

        unset($user['password']);
        
        response(200, [
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
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
            $user = $this->authModel->getUserById($userId);
            unset($user['password']);
            response(201, ['message' => 'User registered successfully', 'user' => $user]);
        } else {
            response(500, ['error' => 'Failed to create user']);
        }
    }

    public function profile() {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            response(401, ['error' => 'Unauthorized']);
        }

        $userData = $this->authModel->getUserById($user['user_id']);
        unset($userData['password']);

        response(200, ['user' => $userData]);
    }

    public function updateProfile() {
        $user = $this->getCurrentUser();
        
        if (!$user) {
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

        $updated = $this->authModel->updateUser($user['user_id'], $updateData);

        if ($updated) {
            $userData = $this->authModel->getUserById($user['user_id']);
            unset($userData['password']);
            response(200, ['message' => 'Profile updated', 'user' => $userData]);
        } else {
            response(500, ['error' => 'Update failed']);
        }
    }

    public function changePassword() {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            response(401, ['error' => 'Unauthorized']);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['current_password']) || empty($input['new_password'])) {
            response(400, ['error' => 'Current and new password required']);
        }

        $userData = $this->authModel->getUserById($user['user_id']);

        if ($userData['password'] !== $input['current_password']) {
            response(400, ['error' => 'Current password incorrect']);
        }

        $updated = $this->authModel->updatePassword($user['user_id'], $input['new_password']);

        if ($updated) {
            response(200, ['message' => 'Password changed successfully']);
        } else {
            response(500, ['error' => 'Failed to change password']);
        }
    }

    public function verifyToken() {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            response(401, ['error' => 'Invalid token']);
        }

        response(200, [
            'valid' => true,
            'user_id' => $user['user_id'],
            'role' => $user['role']
        ]);
    }

    private function getCurrentUser() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        $decoded = json_decode(base64_decode($token), true);

        if (!$decoded || $decoded['exp'] < time()) {
            return null;
        }

        return $decoded;
    }
}