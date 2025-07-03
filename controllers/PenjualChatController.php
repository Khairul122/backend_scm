<?php
class PenjualChatController {
    private $penjualChatModel;

    public function __construct() {
        $this->penjualChatModel = new PenjualChatModel();
    }

    public function getConversations() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied. Seller role required.']);
            return;
        }

        $conversations = $this->penjualChatModel->getConversationsByProduct($currentUser['id']);
        
        response(200, [
            'message' => 'Chat conversations retrieved successfully',
            'data' => $conversations
        ]);
    }

    public function getChatMessages($produkId, $pembeliId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $messages = $this->penjualChatModel->getChatMessages($produkId, $pembeliId, $currentUser['id']);
        
        if (empty($messages)) {
            response(404, ['error' => 'Conversation not found or access denied']);
            return;
        }
        
        response(200, [
            'message' => 'Chat messages retrieved successfully',
            'data' => $messages
        ]);
    }

    public function sendMessage() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateMessageInput($input)) {
            response(400, ['error' => 'Invalid input data. Required: produk_id, pembeli_id, message']);
            return;
        }

        $messageId = $this->penjualChatModel->sendMessage(
            $input['produk_id'],
            $input['pembeli_id'],
            $currentUser['id'],
            $input['message']
        );

        if ($messageId) {
            $message = $this->penjualChatModel->getMessageById($messageId, $currentUser['id']);
            response(201, [
                'message' => 'Message sent successfully',
                'data' => $message
            ]);
        } else {
            response(500, ['error' => 'Failed to send message. Product ownership required.']);
        }
    }

    public function getConversationHistory($produkId, $pembeliId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $history = $this->penjualChatModel->getConversationHistory($produkId, $pembeliId, $currentUser['id']);
        
        if (empty($history)) {
            response(404, ['error' => 'Conversation history not found or access denied']);
            return;
        }
        
        response(200, [
            'message' => 'Conversation history retrieved successfully',
            'data' => $history
        ]);
    }

    public function searchConversations() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $conversations = $this->penjualChatModel->searchConversations($currentUser['id'], $query);
        
        response(200, [
            'message' => 'Search results retrieved successfully',
            'data' => $conversations
        ]);
    }

    public function getChatStatistics() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->penjualChatModel->getChatStatistics($currentUser['id']);
        
        response(200, [
            'message' => 'Chat statistics retrieved successfully',
            'data' => $statistics
        ]);
    }

    public function getProductsWithChats() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $products = $this->penjualChatModel->getProductsWithChats($currentUser['id']);
        
        response(200, [
            'message' => 'Products with chat activity retrieved successfully',
            'data' => $products
        ]);
    }

    public function getActiveCustomers() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $customers = $this->penjualChatModel->getActiveCustomers($currentUser['id'], $limit);
        
        response(200, [
            'message' => 'Active customers retrieved successfully',
            'data' => $customers
        ]);
    }

    public function getPopularInquiryProducts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $products = $this->penjualChatModel->getPopularInquiryProducts($currentUser['id']);
        
        response(200, [
            'message' => 'Popular inquiry products retrieved successfully',
            'data' => $products
        ]);
    }

    public function getUnreadCount() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $unreadCount = $this->penjualChatModel->getUnreadConversationsCount($currentUser['id']);
        
        response(200, [
            'message' => 'Unread chat count retrieved successfully',
            'data' => ['unread_count' => $unreadCount]
        ]);
    }

    public function getResponseTimeAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->penjualChatModel->getResponseTimeAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Response time analysis retrieved successfully',
            'data' => $analysis
        ]);
    }

    public function getChatTrends() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $days = $_GET['days'] ?? 30;
        $trends = $this->penjualChatModel->getChatTrends($currentUser['id'], $days);
        
        response(200, [
            'message' => "Chat trends for last {$days} days retrieved successfully",
            'data' => $trends
        ]);
    }

    public function getFrequentQuestions() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $questions = $this->penjualChatModel->getFrequentQuestions($currentUser['id'], $limit);
        
        response(200, [
            'message' => 'Frequent questions retrieved successfully',
            'data' => $questions
        ]);
    }

    public function getChatConversionAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->penjualChatModel->getChatConversionAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Chat conversion analysis retrieved successfully',
            'data' => $analysis
        ]);
    }

    public function markConversationAsRead($produkId, $pembeliId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if ($this->penjualChatModel->markConversationAsRead($produkId, $pembeliId, $currentUser['id'])) {
            response(200, ['message' => 'Conversation marked as read successfully']);
        } else {
            response(404, ['error' => 'Conversation not found or access denied']);
        }
    }

    public function getChatOverview() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->penjualChatModel->getChatStatistics($currentUser['id']);
        $activeCustomers = $this->penjualChatModel->getActiveCustomers($currentUser['id'], 5);
        $popularProducts = $this->penjualChatModel->getPopularInquiryProducts($currentUser['id']);
        $unreadCount = $this->penjualChatModel->getUnreadConversationsCount($currentUser['id']);
        $conversations = $this->penjualChatModel->getConversationsByProduct($currentUser['id']);
        $responseTimeAnalysis = $this->penjualChatModel->getResponseTimeAnalysis($currentUser['id']);
        $conversionAnalysis = $this->penjualChatModel->getChatConversionAnalysis($currentUser['id']);
        $recentTrends = $this->penjualChatModel->getChatTrends($currentUser['id'], 7);

        response(200, [
            'message' => 'Chat overview retrieved successfully',
            'data' => [
                'statistics' => $statistics,
                'active_customers' => $activeCustomers,
                'popular_products' => array_slice($popularProducts, 0, 5),
                'unread_count' => $unreadCount,
                'recent_conversations' => array_slice($conversations, 0, 10),
                'response_time_analysis' => $responseTimeAnalysis,
                'conversion_analysis' => $conversionAnalysis,
                'weekly_trends' => $recentTrends
            ]
        ]);
    }

    public function quickReply() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['template']) || empty($input['template'])) {
            response(400, ['error' => 'Quick reply template required']);
            return;
        }

        if (!$this->validateMessageInput($input)) {
            response(400, ['error' => 'Invalid input data. Required: produk_id, pembeli_id, template']);
            return;
        }

        $quickReplies = [
            'greeting' => 'Halo! Terima kasih sudah menghubungi kami. Ada yang bisa kami bantu?',
            'product_info' => 'Produk ini tersedia dengan kualitas premium. Untuk informasi lebih detail, silakan tanyakan spesifikasi yang Anda butuhkan.',
            'price_inquiry' => 'Harga yang tertera sudah termasuk kemasan yang aman. Kami juga memberikan diskon untuk pembelian dalam jumlah tertentu.',
            'shipping_info' => 'Kami melayani pengiriman ke seluruh Indonesia dengan berbagai pilihan kurir. Estimasi pengiriman 2-5 hari kerja.',
            'availability' => 'Produk ini tersedia dan siap kirim. Stok terbatas, jadi segera lakukan pemesanan ya!',
            'payment_info' => 'Kami menerima pembayaran via transfer bank dan COD (untuk area tertentu). Pembayaran aman dan terpercaya.',
            'thank_you' => 'Terima kasih sudah berbelanja dengan kami! Jangan ragu menghubungi kami jika ada pertanyaan lain.',
            'follow_up' => 'Apakah ada pertanyaan lain yang bisa kami bantu? Kami siap melayani Anda dengan sepenuh hati.'
        ];

        $message = $quickReplies[$input['template']] ?? $input['template'];

        $messageId = $this->penjualChatModel->sendMessage(
            $input['produk_id'],
            $input['pembeli_id'],
            $currentUser['id'],
            $message
        );

        if ($messageId) {
            $sentMessage = $this->penjualChatModel->getMessageById($messageId, $currentUser['id']);
            response(201, [
                'message' => 'Quick reply sent successfully',
                'data' => $sentMessage
            ]);
        } else {
            response(500, ['error' => 'Failed to send quick reply']);
        }
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
        $required = ['produk_id', 'pembeli_id'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['produk_id']) || !is_numeric($input['pembeli_id'])) {
            return false;
        }

        if (isset($input['message'])) {
            if (strlen($input['message']) > 1000) {
                return false;
            }
        }

        return true;
    }
}