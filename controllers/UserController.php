<?php
class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    public function getAllUsers() {
        $role = $_GET['role'] ?? null;
        
        if ($role) {
            $users = $this->userModel->getUsersByRole($role);
        } else {
            $users = $this->userModel->getAllUsers();
        }
        
        response(200, ['data' => $users]);
    }

    public function getUserById($id) {
        $user = $this->userModel->getUserById($id);
        
        if (!$user) {
            response(404, ['error' => 'User not found']);
            return;
        }
        
        response(200, ['data' => $user]);
    }

    public function createUser() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateUserInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        if ($this->userModel->checkEmailExists($input['email'])) {
            response(400, ['error' => 'Email already exists']);
            return;
        }

        if ($this->userModel->checkPhoneExists($input['no_telepon'])) {
            response(400, ['error' => 'Phone number already exists']);
            return;
        }

        $userId = $this->userModel->createUser($input);
        
        if ($userId) {
            $user = $this->userModel->getUserById($userId);
            response(201, ['message' => 'User created successfully', 'data' => $user]);
        } else {
            response(500, ['error' => 'Failed to create user']);
        }
    }

    public function updateUser($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->userModel->getUserById($id)) {
            response(404, ['error' => 'User not found']);
            return;
        }

        if (isset($input['email']) && $this->userModel->checkEmailExists($input['email'], $id)) {
            response(400, ['error' => 'Email already exists']);
            return;
        }

        if (isset($input['no_telepon']) && $this->userModel->checkPhoneExists($input['no_telepon'], $id)) {
            response(400, ['error' => 'Phone number already exists']);
            return;
        }

        if ($this->userModel->updateUser($id, $input)) {
            $user = $this->userModel->getUserById($id);
            response(200, ['message' => 'User updated successfully', 'data' => $user]);
        } else {
            response(500, ['error' => 'Failed to update user']);
        }
    }

    public function deleteUser($id) {
        if (!$this->userModel->getUserById($id)) {
            response(404, ['error' => 'User not found']);
            return;
        }

        if ($this->userModel->deleteUser($id)) {
            response(200, ['message' => 'User deleted successfully']);
        } else {
            response(500, ['error' => 'Failed to delete user']);
        }
    }

    public function updateUserStatus($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['status']) || !in_array($input['status'], ['aktif', 'nonaktif'])) {
            response(400, ['error' => 'Invalid status']);
            return;
        }

        if (!$this->userModel->getUserById($id)) {
            response(404, ['error' => 'User not found']);
            return;
        }

        if ($this->userModel->updateUserStatus($id, $input['status'])) {
            response(200, ['message' => 'User status updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update user status']);
        }
    }

    public function resetPassword($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['password']) || strlen($input['password']) < 6) {
            response(400, ['error' => 'Password must be at least 6 characters']);
            return;
        }

        if (!$this->userModel->getUserById($id)) {
            response(404, ['error' => 'User not found']);
            return;
        }

        if ($this->userModel->resetPassword($id, $input['password'])) {
            response(200, ['message' => 'Password reset successfully']);
        } else {
            response(500, ['error' => 'Failed to reset password']);
        }
    }

    public function searchUsers() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $users = $this->userModel->searchUsers($query);
        response(200, ['data' => $users]);
    }

    public function getUserStats() {
        $stats = $this->userModel->getUserStats();
        response(200, ['data' => $stats]);
    }

    private function validateUserInput($input) {
        $required = ['nama_lengkap', 'email', 'no_telepon', 'alamat', 'password', 'role'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!in_array($input['role'], ['admin', 'pembeli', 'pengepul', 'roasting', 'penjual'])) {
            return false;
        }

        if (strlen($input['password']) < 6) {
            return false;
        }

        return true;
    }
}