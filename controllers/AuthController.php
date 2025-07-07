<?php
class AuthController {
    private $authModel;

    public function __construct() {
        $this->authModel = new AuthModel();
    }

    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        error_log('=== REGISTER ATTEMPT ===');
        error_log('Input data: ' . print_r($input, true));
        
        if (empty($input['nama_lengkap']) || empty($input['email']) || empty($input['no_telepon']) || 
            empty($input['alamat']) || empty($input['password']) || empty($input['role'])) {
            error_log('Register failed: Missing required fields');
            response(400, [
                'error' => 'All fields are required',
                'debug' => [
                    'required_fields' => ['nama_lengkap', 'email', 'no_telepon', 'alamat', 'password', 'role'],
                    'received_fields' => array_keys($input),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        $validRoles = ['pembeli', 'pengepul', 'roasting', 'penjual'];
        if (!in_array($input['role'], $validRoles)) {
            error_log('Register failed: Invalid role - ' . $input['role']);
            response(400, [
                'error' => 'Invalid role',
                'debug' => [
                    'provided_role' => $input['role'],
                    'valid_roles' => $validRoles,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            error_log('Register failed: Invalid email format - ' . $input['email']);
            response(400, [
                'error' => 'Invalid email format',
                'debug' => [
                    'provided_email' => $input['email'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        if ($this->authModel->checkEmailExists($input['email'])) {
            error_log('Register failed: Email already exists - ' . $input['email']);
            response(409, [
                'error' => 'Email already exists',
                'debug' => [
                    'email' => $input['email'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        if ($this->authModel->checkPhoneExists($input['no_telepon'])) {
            error_log('Register failed: Phone already exists - ' . $input['no_telepon']);
            response(409, [
                'error' => 'Phone number already exists',
                'debug' => [
                    'phone' => $input['no_telepon'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        $userId = $this->authModel->createUser($input);
        
        if ($userId) {
            error_log('Register successful: User ID ' . $userId . ' created for ' . $input['email']);
            response(201, [
                'message' => 'User registered successfully',
                'debug' => [
                    'user_id' => $userId,
                    'email' => $input['email'],
                    'role' => $input['role'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            error_log('Register failed: Database error for ' . $input['email']);
            response(500, [
                'error' => 'Failed to create user',
                'debug' => [
                    'email' => $input['email'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }
    }

    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);

        error_log('=== LOGIN ATTEMPT ===');
        error_log('Login attempt for: ' . ($input['identifier'] ?? 'NO IDENTIFIER'));

        if (empty($input['identifier']) || empty($input['password'])) {
            error_log('Login failed: Missing credentials');
            response(400, [
                'error' => 'Email/phone and password are required',
                'debug' => [
                    'has_identifier' => !empty($input['identifier']),
                    'has_password' => !empty($input['password']),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        $user = $this->authModel->getUserByIdentifier($input['identifier']);

        if (!$user || $user['password'] !== $input['password']) {
            error_log('Login failed: Invalid credentials for ' . $input['identifier']);
            response(401, [
                'error' => 'Invalid credentials',
                'debug' => [
                    'identifier' => $input['identifier'],
                    'user_found' => $user !== null,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        if ($user['status'] !== 'aktif') {
            error_log('Login failed: Inactive account for ' . $user['email']);
            response(403, [
                'error' => 'Account is inactive',
                'debug' => [
                    'email' => $user['email'],
                    'status' => $user['status'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        $tokenPayload = [
            'id' => $user['id'],
            'nama_lengkap' => $user['nama_lengkap'],
            'email' => $user['email'],
            'no_telepon' => $user['no_telepon'],
            'alamat' => $user['alamat'],
            'role' => $user['role'],
            'nama_toko' => $user['nama_toko'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60)
        ];

        $token = base64_encode(json_encode($tokenPayload));
        
        error_log('Login successful for: ' . $user['email'] . ' (role: ' . $user['role'] . ')');
        
        response(200, [
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'nama_lengkap' => $user['nama_lengkap'],
                'email' => $user['email'],
                'no_telepon' => $user['no_telepon'],
                'alamat' => $user['alamat'],
                'role' => $user['role'],
                'nama_toko' => $user['nama_toko']
            ],
            'debug' => [
                'token_expires_at' => date('Y-m-d H:i:s', $tokenPayload['exp']),
                'login_timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    public function logout() {
        $user = $this->getCurrentUser();
        error_log('Logout request from: ' . ($user['email'] ?? 'NO TOKEN'));
        
        response(200, [
            'message' => 'Logged out successfully',
            'debug' => [
                'user' => $user['email'] ?? 'NO TOKEN',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    public function profile() {
        $user = $this->getCurrentUser();

        if (!$user) {
            error_log('Profile request: No valid token');
            response(401, [
                'error' => 'Unauthorized',
                'debug' => [
                    'token_valid' => false,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        error_log('Profile request for: ' . $user['email']);
        
        response(200, [
            'user' => $user,
            'debug' => [
                'token_expires_at' => date('Y-m-d H:i:s', $user['exp']),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    public function updateProfile() {
        $user = $this->getCurrentUser();

        if (!$user) {
            error_log('Profile update: No valid token');
            response(401, [
                'error' => 'Unauthorized',
                'debug' => [
                    'token_valid' => false,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        error_log('Profile update for: ' . $user['email'] . ' with data: ' . print_r($input, true));
        
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
            error_log('Profile update failed: No data to update');
            response(400, [
                'error' => 'No data to update',
                'debug' => [
                    'input_received' => $input,
                    'valid_fields' => ['nama_lengkap', 'alamat', 'nama_toko'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        $updated = $this->authModel->updateUser($user['id'], $updateData);

        if ($updated) {
            $updatedUser = $this->authModel->getUserById($user['id']);
            
            $newTokenPayload = [
                'id' => $updatedUser['id'],
                'nama_lengkap' => $updatedUser['nama_lengkap'],
                'email' => $updatedUser['email'],
                'no_telepon' => $updatedUser['no_telepon'],
                'alamat' => $updatedUser['alamat'],
                'role' => $updatedUser['role'],
                'nama_toko' => $updatedUser['nama_toko'],
                'iat' => time(),
                'exp' => time() + (24 * 60 * 60)
            ];

            $newToken = base64_encode(json_encode($newTokenPayload));

            error_log('Profile updated successfully for: ' . $user['email']);

            response(200, [
                'message' => 'Profile updated',
                'token' => $newToken,
                'user' => [
                    'id' => $updatedUser['id'],
                    'nama_lengkap' => $updatedUser['nama_lengkap'],
                    'email' => $updatedUser['email'],
                    'no_telepon' => $updatedUser['no_telepon'],
                    'alamat' => $updatedUser['alamat'],
                    'role' => $updatedUser['role'],
                    'nama_toko' => $updatedUser['nama_toko']
                ],
                'debug' => [
                    'updated_fields' => array_keys($updateData),
                    'new_token_expires_at' => date('Y-m-d H:i:s', $newTokenPayload['exp']),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            error_log('Profile update failed for: ' . $user['email']);
            response(500, [
                'error' => 'Update failed',
                'debug' => [
                    'user' => $user['email'],
                    'attempted_fields' => array_keys($updateData),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }
    }

    public function changePassword() {
        $user = $this->getCurrentUser();

        if (!$user) {
            error_log('Password change: No valid token');
            response(401, [
                'error' => 'Unauthorized',
                'debug' => [
                    'token_valid' => false,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        error_log('Password change request for: ' . $user['email']);

        if (empty($input['current_password']) || empty($input['new_password'])) {
            error_log('Password change failed: Missing passwords');
            response(400, [
                'error' => 'Current and new password required',
                'debug' => [
                    'has_current' => !empty($input['current_password']),
                    'has_new' => !empty($input['new_password']),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        $userData = $this->authModel->getUserById($user['id']);

        if ($userData['password'] !== $input['current_password']) {
            error_log('Password change failed: Incorrect current password for ' . $user['email']);
            response(400, [
                'error' => 'Current password incorrect',
                'debug' => [
                    'user' => $user['email'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }

        $updated = $this->authModel->updatePassword($user['id'], $input['new_password']);

        if ($updated) {
            error_log('Password changed successfully for: ' . $user['email']);
            response(200, [
                'message' => 'Password changed successfully',
                'debug' => [
                    'user' => $user['email'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            error_log('Password change failed: Database error for ' . $user['email']);
            response(500, [
                'error' => 'Failed to change password',
                'debug' => [
                    'user' => $user['email'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }
    }

    private function getCurrentUser() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            error_log('AuthController: No Bearer token found');
            return null;
        }

        $token = $matches[1];
        error_log('AuthController: Token received: ' . substr($token, 0, 50) . '...');
        
        $decoded = json_decode(base64_decode($token), true);

        if (!$decoded) {
            error_log('AuthController: Token decode failed');
            return null;
        }

        if ($decoded['exp'] < time()) {
            error_log('AuthController: Token expired at ' . date('Y-m-d H:i:s', $decoded['exp']));
            return null;
        }

        error_log('AuthController: Token valid for user: ' . $decoded['email']);
        return $decoded;
    }
}
?>