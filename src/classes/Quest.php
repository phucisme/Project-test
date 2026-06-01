<?php
class Quest {
    private $db;
    private $user_id;
    private $player;

    public function __construct($db, $user_id, $player) {
        $this->db = $db;
        $this->user_id = $user_id;
        $this->player = $player;
    }

    public function getQuests() {
        $query = "SELECT pq.*, q.name, q.description, q.type, q.target_quantity, q.reward_coins, q.reward_exp, q.reward_item_id, q.reward_item_quantity, i.name as reward_item_name
                  FROM player_quests pq
                  JOIN quests q ON pq.quest_id = q.id
                  LEFT JOIN items i ON q.reward_item_id = i.id
                  WHERE pq.user_id = :user_id
                  ORDER BY pq.completed, pq.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function completeQuest($quest_id) {
        $query = "SELECT pq.*, q.reward_coins, q.reward_exp, q.reward_item_id, q.reward_item_quantity
                  FROM player_quests pq
                  JOIN quests q ON pq.quest_id = q.id
                  WHERE pq.id = :id AND pq.user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $quest_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $quest = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quest || $quest['completed']) {
            return array('success' => false, 'error' => 'Quest not found or already completed');
        }

        $query = "UPDATE player_quests SET completed = TRUE, completed_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $quest_id);
        $stmt->execute();

        // Give rewards
        if ($quest['reward_coins'] > 0) {
            $this->player->addCoins($quest['reward_coins'], 'reward', 'quest_complete_' . $quest_id);
        }
        if ($quest['reward_exp'] > 0) {
            $this->player->addExp($quest['reward_exp']);
        }
        if ($quest['reward_item_id'] > 0) {
            $this->player->addInventoryItem($quest['reward_item_id'], $quest['reward_item_quantity']);
        }

        return array('success' => true, 'rewards' => $quest);
    }

    public function updateProgress($quest_id, $amount = 1) {
        $query = "SELECT * FROM player_quests WHERE id = :id AND user_id = :user_id AND completed = FALSE";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $quest_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $quest = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quest) {
            return false;
        }

        $new_progress = $quest['progress'] + $amount;

        $query = "SELECT target_quantity FROM quests WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $quest['quest_id']);
        $stmt->execute();
        $quest_def = $stmt->fetch(PDO::FETCH_ASSOC);

        $query = "UPDATE player_quests SET progress = :progress WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':progress', $new_progress);
        $stmt->bindParam(':id', $quest_id);
        $stmt->execute();

        if ($new_progress >= $quest_def['target_quantity']) {
            return $this->completeQuest($quest_id);
        }

        return true;
    }

    public function addDelivery($item_id, $quantity) {
        $query = "INSERT INTO deliveries (user_id, item_id, quantity, reward_coins, reward_exp) 
                  VALUES (:user_id, :item_id, :quantity, :reward_coins, :reward_exp)";
        $stmt = $this->db->prepare($query);
        $reward_coins = $quantity * 50;
        $reward_exp = $quantity * 10;
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':reward_coins', $reward_coins);
        $stmt->bindParam(':reward_exp', $reward_exp);
        $stmt->execute();

        return array('success' => true);
    }

    public function getDeliveries() {
        $query = "SELECT d.*, i.name, i.sprite_file FROM deliveries d
                  JOIN items i ON d.item_id = i.id
                  WHERE d.user_id = :user_id
                  ORDER BY d.completed, d.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function completeDelivery($delivery_id) {
        $query = "SELECT * FROM deliveries WHERE id = :id AND user_id = :user_id AND completed = FALSE";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $delivery_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$delivery) {
            return array('success' => false, 'error' => 'Delivery not found');
        }

        // Check if player has the items
        if (!$this->player->removeInventoryItem($delivery['item_id'], $delivery['quantity'])) {
            return array('success' => false, 'error' => 'Not enough items');
        }

        $query = "UPDATE deliveries SET completed = TRUE, completed_at = NOW(), cooldown_until = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $delivery_id);
        $stmt->execute();

        $this->player->addCoins($delivery['reward_coins'], 'reward', 'delivery_complete_' . $delivery_id);
        $this->player->addExp($delivery['reward_exp']);

        return array('success' => true, 'rewards' => array('coins' => $delivery['reward_coins'], 'exp' => $delivery['reward_exp']));
    }
}
?>
