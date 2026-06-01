<?php
class Craft {
    private $db;
    private $user_id;
    private $player;

    public function __construct($db, $user_id, $player) {
        $this->db = $db;
        $this->user_id = $user_id;
        $this->player = $player;
    }

    public function getMachines() {
        $query = "SELECT * FROM machines WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecipes() {
        $query = "SELECT r.*, i.name as output_name, i.sprite_file
                  FROM recipes r
                  JOIN items i ON r.output_item_id = i.id
                  ORDER BY r.name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecipeIngredients($recipe_id) {
        $query = "SELECT ri.*, i.name, i.sprite_file
                  FROM recipe_ingredients ri
                  JOIN items i ON ri.item_id = i.id
                  WHERE ri.recipe_id = :recipe_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':recipe_id', $recipe_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function startProduction($machine_id, $recipe_id) {
        // Check if machine exists and belongs to user
        $query = "SELECT * FROM machines WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $machine_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $machine = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$machine) {
            return array('success' => false, 'error' => 'Machine not found');
        }

        // Check recipe ingredients
        $query = "SELECT * FROM recipes WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $recipe_id);
        $stmt->execute();
        $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recipe) {
            return array('success' => false, 'error' => 'Recipe not found');
        }

        $ingredients = $this->getRecipeIngredients($recipe_id);
        foreach ($ingredients as $ingredient) {
            if (!$this->player->removeInventoryItem($ingredient['item_id'], $ingredient['quantity'])) {
                return array('success' => false, 'error' => 'Not enough ' . $ingredient['name']);
            }
        }

        // Check available slots
        $query = "SELECT COUNT(*) as count FROM production_slots WHERE machine_id = :machine_id AND status != 'ready'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':machine_id', $machine_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] >= $machine['max_slots']) {
            // Return ingredients
            foreach ($ingredients as $ingredient) {
                $this->player->addInventoryItem($ingredient['item_id'], $ingredient['quantity']);
            }
            return array('success' => false, 'error' => 'All slots are occupied');
        }

        $completion_time = date('Y-m-d H:i:s', time() + $recipe['production_time']);

        $query = "INSERT INTO production_slots (machine_id, recipe_id, started_at, completed_at, status) 
                  VALUES (:machine_id, :recipe_id, NOW(), :completed_at, 'producing')";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':machine_id', $machine_id);
        $stmt->bindParam(':recipe_id', $recipe_id);
        $stmt->bindParam(':completed_at', $completion_time);
        $stmt->execute();

        return array('success' => true, 'completion_time' => $completion_time);
    }

    public function harvestProduction($slot_id) {
        $query = "SELECT ps.*, r.output_item_id, r.output_quantity
                  FROM production_slots ps
                  JOIN recipes r ON ps.recipe_id = r.id
                  WHERE ps.id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $slot_id);
        $stmt->execute();
        $slot = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$slot || $slot['status'] !== 'ready') {
            return array('success' => false, 'error' => 'Production not ready');
        }

        $this->player->addInventoryItem($slot['output_item_id'], $slot['output_quantity']);
        $this->player->addExp(15);

        $query = "DELETE FROM production_slots WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $slot_id);
        $stmt->execute();

        return array('success' => true);
    }

    public function getProductionSlots($machine_id) {
        $query = "SELECT ps.*, r.output_item_id, i.name as output_name, i.sprite_file
                  FROM production_slots ps
                  JOIN recipes r ON ps.recipe_id = r.id
                  JOIN items i ON r.output_item_id = i.id
                  WHERE ps.machine_id = :machine_id
                  ORDER BY ps.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':machine_id', $machine_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
