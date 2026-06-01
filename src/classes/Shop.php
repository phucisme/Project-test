<?php
class Shop {
    private $db;
    private $player;

    public function __construct($db, $player) {
        $this->db = $db;
        $this->player = $player;
    }

    public function getShopItems() {
        $query = "SELECT * FROM items WHERE buy_price > 0 ORDER BY type, name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buyItem($item_id, $quantity = 1) {
        $query = "SELECT * FROM items WHERE id = :id AND buy_price > 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $item_id);
        $stmt->execute();
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            return array('success' => false, 'error' => 'Item not available for purchase');
        }

        $total_cost = $item['buy_price'] * $quantity;
        $user = $this->player->getUserData();

        if ($user['coins'] < $total_cost) {
            return array('success' => false, 'error' => 'Not enough coins');
        }

        $this->player->removeCoins($total_cost, 'spend', 'shop_buy_' . $item_id);
        $this->player->addInventoryItem($item_id, $quantity);

        return array('success' => true, 'total_cost' => $total_cost);
    }

    public function sellItem($item_id, $quantity = 1) {
        $query = "SELECT * FROM items WHERE id = :id AND sell_price > 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $item_id);
        $stmt->execute();
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            return array('success' => false, 'error' => 'Item not available for sale');
        }

        if (!$this->player->removeInventoryItem($item_id, $quantity)) {
            return array('success' => false, 'error' => 'Not enough items');
        }

        $total_price = $item['sell_price'] * $quantity;
        $this->player->addCoins($total_price, 'earn', 'shop_sell_' . $item_id);

        return array('success' => true, 'total_price' => $total_price);
    }
}
?>
