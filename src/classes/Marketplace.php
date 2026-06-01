<?php
class Marketplace {
    private $db;
    private $user_id;
    private $player;

    public function __construct($db, $user_id, $player) {
        $this->db = $db;
        $this->user_id = $user_id;
        $this->player = $player;
    }

    public function listItem($item_id, $quantity, $price_per_unit) {
        $user = $this->player->getUserData();

        if (!$this->player->removeInventoryItem($item_id, $quantity)) {
            return array('success' => false, 'error' => 'Not enough items');
        }

        // Count active listings
        $query = "SELECT COUNT(*) as count FROM marketplace_listings WHERE user_id = :user_id AND sold = FALSE";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] >= $user['marketplace_slots']) {
            // Return item if slots exceeded
            $this->player->addInventoryItem($item_id, $quantity);
            return array('success' => false, 'error' => 'Marketplace slots full');
        }

        $commission = $quantity * $price_per_unit * MARKETPLACE_COMMISSION_RATE / 100;

        $query = "INSERT INTO marketplace_listings (user_id, item_id, quantity, price_per_unit, commission_paid) 
                  VALUES (:user_id, :item_id, :quantity, :price_per_unit, :commission)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':price_per_unit', $price_per_unit);
        $stmt->bindParam(':commission', $commission);
        $stmt->execute();

        return array('success' => true, 'commission_paid' => $commission);
    }

    public function buyListing($listing_id) {
        $query = "SELECT * FROM marketplace_listings WHERE id = :id AND sold = FALSE";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $listing_id);
        $stmt->execute();
        $listing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$listing) {
            return array('success' => false, 'error' => 'Listing not found');
        }

        $total_cost = $listing['quantity'] * $listing['price_per_unit'];
        $user = $this->player->getUserData();

        if ($user['coins'] < $total_cost) {
            return array('success' => false, 'error' => 'Not enough coins');
        }

        $this->player->removeCoins($total_cost, 'marketplace_buy', 'listing_' . $listing_id);
        $this->player->addInventoryItem($listing['item_id'], $listing['quantity']);

        // Give coins to seller
        $seller_coins = $total_cost - $listing['commission_paid'];
        $query = "UPDATE users SET coins = coins + :coins WHERE id = :seller_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':coins', $seller_coins);
        $stmt->bindParam(':seller_id', $listing['user_id']);
        $stmt->execute();

        // Mark listing as sold
        $query = "UPDATE marketplace_listings SET sold = TRUE, sold_to_user_id = :buyer_id, sold_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':buyer_id', $this->user_id);
        $stmt->bindParam(':id', $listing_id);
        $stmt->execute();

        return array('success' => true, 'total_cost' => $total_cost);
    }

    public function cancelListing($listing_id) {
        $query = "SELECT * FROM marketplace_listings WHERE id = :id AND user_id = :user_id AND sold = FALSE";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $listing_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $listing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$listing) {
            return array('success' => false, 'error' => 'Listing not found');
        }

        $this->player->addInventoryItem($listing['item_id'], $listing['quantity']);

        $query = "UPDATE marketplace_listings SET sold = TRUE WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $listing_id);
        $stmt->execute();

        return array('success' => true);
    }

    public function getListings($offset = 0, $limit = 20) {
        $query = "SELECT ml.*, i.name, i.sprite_file, i.type, u.username
                  FROM marketplace_listings ml
                  JOIN items i ON ml.item_id = i.id
                  JOIN users u ON ml.user_id = u.id
                  WHERE ml.sold = FALSE
                  ORDER BY ml.created_at DESC
                  LIMIT :offset, :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function upgradeMarketplaceSlots() {
        $user = $this->player->getUserData();
        $upgrade_cost = MARKETPLACE_SLOT_UPGRADE_COST;

        if ($user['coins'] < $upgrade_cost) {
            return array('success' => false, 'error' => 'Not enough coins');
        }

        $this->player->removeCoins($upgrade_cost, 'spend', 'marketplace_slot_upgrade');

        $query = "UPDATE users SET marketplace_slots = marketplace_slots + 1 WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $this->user_id);
        $stmt->execute();

        return array('success' => true);
    }
}
?>
