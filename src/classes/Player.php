<?php
class Player {
    private $db;
    private $user_id;
    private $user_data;

    public function __construct($db, $user_id) {
        $this->db = $db;
        $this->user_id = $user_id;
        $this->loadUserData();
    }

    private function loadUserData() {
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $this->user_id);
        $stmt->execute();
        $this->user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserData() {
        return $this->user_data;
    }

    public function addExp($amount) {
        $query = "UPDATE users SET exp = exp + :amount WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':id', $this->user_id);
        $stmt->execute();

        // Check for level up
        $this->checkLevelUp();
    }

    private function checkLevelUp() {
        $query = "SELECT level, exp FROM users WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $this->user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $query_next = "SELECT exp_required FROM level_definitions WHERE level = :level";
        $stmt_next = $this->db->prepare($query_next);
        $next_level = $user['level'] + 1;
        $stmt_next->bindParam(':level', $next_level);
        $stmt_next->execute();
        $next_level_def = $stmt_next->fetch(PDO::FETCH_ASSOC);

        if ($next_level_def && $user['exp'] >= $next_level_def['exp_required']) {
            $this->levelUp($user['level']);
        }
    }

    private function levelUp($current_level) {
        $next_level = $current_level + 1;

        // Get reward for new level
        $query = "SELECT reward_coins, reward_item_id, reward_item_quantity, max_pots, max_decorations 
                  FROM level_definitions WHERE level = :level";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':level', $next_level);
        $stmt->execute();
        $reward = $stmt->fetch(PDO::FETCH_ASSOC);

        // Update user level and stats
        $query = "UPDATE users SET level = :level, max_pots = :max_pots, max_decorations = :max_decorations WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':level', $next_level);
        $stmt->bindParam(':max_pots', $reward['max_pots']);
        $stmt->bindParam(':max_decorations', $reward['max_decorations']);
        $stmt->bindParam(':id', $this->user_id);
        $stmt->execute();

        // Add reward coins
        if ($reward['reward_coins'] > 0) {
            $this->addCoins($reward['reward_coins'], 'reward', 'level_up_' . $next_level);
        }

        // Add reward item
        if ($reward['reward_item_id']) {
            $this->addInventoryItem($reward['reward_item_id'], $reward['reward_item_quantity']);
        }

        $this->loadUserData();
    }

    public function addCoins($amount, $type = 'reward', $reference = '', $reference_id = null) {
        $query = "UPDATE users SET coins = coins + :amount WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':id', $this->user_id);
        $stmt->execute();

        // Log transaction
        $balance = $this->user_data['coins'] + $amount;
        $query_log = "INSERT INTO wallet_history (user_id, transaction_type, amount, reference_id, reference_type, description, balance_after) 
                      VALUES (:user_id, :type, :amount, :ref_id, :ref_type, :desc, :balance)";
        $stmt_log = $this->db->prepare($query_log);
        $stmt_log->bindParam(':user_id', $this->user_id);
        $stmt_log->bindParam(':type', $type);
        $stmt_log->bindParam(':amount', $amount);
        $stmt_log->bindParam(':ref_id', $reference_id);
        $stmt_log->bindParam(':ref_type', $reference);
        $stmt_log->bindParam(':desc', $reference);
        $stmt_log->bindParam(':balance', $balance);
        $stmt_log->execute();

        $this->loadUserData();
    }

    public function removeCoins($amount, $type = 'spend', $reference = '') {
        if ($this->user_data['coins'] < $amount) {
            return false;
        }

        $query = "UPDATE users SET coins = coins - :amount WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':id', $this->user_id);
        $stmt->execute();

        // Log transaction
        $balance = $this->user_data['coins'] - $amount;
        $query_log = "INSERT INTO wallet_history (user_id, transaction_type, amount, reference_type, description, balance_after) 
                      VALUES (:user_id, :type, :amount, :ref_type, :desc, :balance)";
        $stmt_log = $this->db->prepare($query_log);
        $stmt_log->bindParam(':user_id', $this->user_id);
        $stmt_log->bindParam(':type', $type);
        $stmt_log->bindParam(':amount', $amount);
        $stmt_log->bindParam(':ref_type', $reference);
        $stmt_log->bindParam(':desc', $reference);
        $stmt_log->bindParam(':balance', $balance);
        $stmt_log->execute();

        $this->loadUserData();
        return true;
    }

    public function addInventoryItem($item_id, $quantity = 1) {
        $query = "SELECT quantity FROM inventory WHERE user_id = :user_id AND item_id = :item_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $query = "UPDATE inventory SET quantity = quantity + :quantity WHERE user_id = :user_id AND item_id = :item_id";
        } else {
            $query = "INSERT INTO inventory (user_id, item_id, quantity) VALUES (:user_id, :item_id, :quantity)";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();

        return true;
    }

    public function removeInventoryItem($item_id, $quantity = 1) {
        $query = "SELECT quantity FROM inventory WHERE user_id = :user_id AND item_id = :item_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing || $existing['quantity'] < $quantity) {
            return false;
        }

        if ($existing['quantity'] == $quantity) {
            $query = "DELETE FROM inventory WHERE user_id = :user_id AND item_id = :item_id";
        } else {
            $query = "UPDATE inventory SET quantity = quantity - :quantity WHERE user_id = :user_id AND item_id = :item_id";
        }

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':item_id', $item_id);
        if ($existing['quantity'] != $quantity) {
            $stmt->bindParam(':quantity', $quantity);
        }
        $stmt->execute();

        return true;
    }

    public function getInventory() {
        $query = "SELECT i.*, it.name, it.sprite_file, it.type, it.sell_price, it.rarity 
                  FROM inventory i 
                  JOIN items it ON i.item_id = it.id 
                  WHERE i.user_id = :user_id 
                  ORDER BY it.type, it.name";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function expandGarden($width, $height) {
        $query = "UPDATE users SET garden_width = :width, garden_height = :height WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':width', $width);
        $stmt->bindParam(':height', $height);
        $stmt->bindParam(':id', $this->user_id);
        $stmt->execute();

        $this->loadUserData();
        return true;
    }

    public function getWalletHistory($limit = 50) {
        $query = "SELECT * FROM wallet_history WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function logAction($action_type, $action_data) {
        $json_data = json_encode($action_data);
        $query = "INSERT INTO player_logs (user_id, action_type, action_data) VALUES (:user_id, :action_type, :action_data)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':action_type', $action_type);
        $stmt->bindParam(':action_data', $json_data);
        return $stmt->execute();
    }
}
?>
