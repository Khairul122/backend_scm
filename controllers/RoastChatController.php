<?php
class RoastChatController {
    private $roastChatModel;

    public function __construct() {
        $this->roastChatModel = new RoastChatModel();
    }

    public function getConversations() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied. Roasting/Seller role required.']);
            return;
        }

        $conversations = $this->roastChatModel->getConversationsByProduct($currentUser['id']);
        
        response(200, [
            'message' => 'Chat conversations retrieved successfully',
            'data' => $conversations
        ]);
    }

    public function getChatMessages($produkId, $pembeliId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!$this->roastChatModel->checkProductOwnership($produkId, $currentUser['id'])) {
            response(403, ['error' => 'You can only view chats for your own products']);
            return;
        }

        $messages = $this->roastChatModel->getChatMessages($produkId, $pembeliId, $currentUser['id']);
        
        response(200, [
            'message' => 'Chat messages retrieved successfully',
            'data' => $messages
        ]);
    }

    public function sendMessage() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateMessageInput($input)) {
            response(400, ['error' => 'Invalid input data. Product ID, buyer ID, and message are required.']);
            return;
        }

        if (!$this->roastChatModel->checkProductOwnership($input['produk_id'], $currentUser['id'])) {
            response(403, ['error' => 'You can only respond to chats for your own products']);
            return;
        }

        $messageId = $this->roastChatModel->sendMessage(
            $input['produk_id'],
            $input['pembeli_id'],
            $currentUser['id'],
            $input['message'],
            'penjual'
        );

        if ($messageId) {
            $message = $this->roastChatModel->getMessageById($messageId, $currentUser['id']);
            response(201, [
                'message' => 'Message sent successfully',
                'data' => $message
            ]);
        } else {
            response(500, ['error' => 'Failed to send message']);
        }
    }

    public function getConversationHistory($produkId, $pembeliId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!$this->roastChatModel->checkProductOwnership($produkId, $currentUser['id'])) {
            response(403, ['error' => 'You can only view chat history for your own products']);
            return;
        }

        $history = $this->roastChatModel->getConversationHistory($produkId, $pembeliId, $currentUser['id']);
        
        response(200, [
            'message' => 'Conversation history retrieved successfully',
            'data' => $history
        ]);
    }

    public function searchConversations() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $conversations = $this->roastChatModel->searchConversations($currentUser['id'], $query);
        
        response(200, [
            'message' => 'Search results retrieved successfully',
            'data' => $conversations
        ]);
    }

    public function getChatStatistics() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->roastChatModel->getChatStatistics($currentUser['id']);
        
        response(200, [
            'message' => 'Chat statistics retrieved successfully',
            'data' => $statistics
        ]);
    }

    public function getProductsWithChats() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $products = $this->roastChatModel->getProductsWithChats($currentUser['id']);
        
        response(200, [
            'message' => 'Products with chat activity retrieved successfully',
            'data' => $products
        ]);
    }

    public function getRecentCustomers() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $customers = $this->roastChatModel->getRecentCustomers($currentUser['id'], $limit);
        
        response(200, [
            'message' => 'Recent customers retrieved successfully',
            'data' => $customers
        ]);
    }

    public function getPopularProducts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $products = $this->roastChatModel->getPopularProducts($currentUser['id']);
        
        response(200, [
            'message' => 'Popular products in chat retrieved successfully',
            'data' => $products
        ]);
    }

    public function getUnreadCount() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $unreadCount = $this->roastChatModel->getUnreadCount($currentUser['id']);
        
        response(200, [
            'message' => 'Unread chat count retrieved successfully',
            'data' => ['unread_count' => $unreadCount]
        ]);
    }

    public function getChatAnalytics() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $analytics = $this->roastChatModel->getChatAnalytics($currentUser['id'], $startDate, $endDate);
        
        response(200, [
            'message' => 'Chat analytics retrieved successfully',
            'data' => $analytics
        ]);
    }

    public function getChatOverview() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->roastChatModel->getChatStatistics($currentUser['id']);
        $recentCustomers = $this->roastChatModel->getRecentCustomers($currentUser['id'], 5);
        $popularProducts = $this->roastChatModel->getPopularProducts($currentUser['id']);
        $unreadCount = $this->roastChatModel->getUnreadCount($currentUser['id']);
        $conversations = $this->roastChatModel->getConversationsByProduct($currentUser['id']);

        response(200, [
            'message' => 'Chat overview retrieved successfully',
            'data' => [
                'statistics' => $statistics,
                'recent_customers' => $recentCustomers,
                'popular_products' => $popularProducts,
                'unread_count' => $unreadCount,
                'recent_conversations' => array_slice($conversations, 0, 10)
            ]
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

    private function validateMessageInput($input) {
        $required = ['produk_id', 'pembeli_id', 'message'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['produk_id']) || !is_numeric($input['pembeli_id'])) {
            return false;
        }

        if (strlen($input['message']) > 1000) {
            return false;
        }

        return true;
    }
}