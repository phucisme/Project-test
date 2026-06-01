<?php
class Garden {
    private $db;
    private $user_id;
    private $player;

    public function __construct($db, $user_id, $player) {
        $this->db = $db;
        $this->user_id = $user_id;
        $this->player = $player;
    }

    public function getGarden() {
        $query = "SELECT * FROM garden_plots WHERE user_id = :user_id ORDER BY y, x";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPlotDetail($x, $y) {
        $query = "SELECT gp.*, c.name as crop_name, c.growth_time, c.stages as crop_stages, 
                         p.name as pot_name, p.level as pot_level, p.growth_time_reduction,
                         d.name as decoration_name,
                         pt.name as pest_name, pt.color as pest_color
                  FROM garden_plots gp
                  LEFT JOIN crops c ON gp.crop_id = c.id
                  LEFT JOIN pots p ON gp.pot_id = p.id
                  LEFT JOIN decorations d ON gp.decoration_id = d.id
                  LEFT JOIN pests pest ON gp.pest_id = pest.id
                  LEFT JOIN pest_types pt ON pest.pest_type_id = pt.id
                  WHERE gp.user_id = :user_id AND gp.x = :x AND gp.y = :y";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':x', $x, PDO::PARAM_INT);
        $stmt->bindParam(':y', $y, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function plantCrop($x, $y, $crop_id) {
        $plot = $this->getPlotDetail($x, $y);
        
        if (!$plot || $plot['status'] !== 'empty') {
            return array('success' => false, 'error' => 'Plot is not empty');
        }

        // Get crop info
        $query = "SELECT * FROM crops WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $crop_id);
        $stmt->execute();
        $crop = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$crop) {
            return array('success' => false, 'error' => 'Crop not found');
        }

        // Check if player has seed
        $item_query = "SELECT * FROM items WHERE type = 'seed' AND id = :crop_id";
        $item_stmt = $this->db->prepare($item_query);
        $item_stmt->bindParam(':crop_id', $crop_id);
        $item_stmt->execute();
        $seed = $item_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$seed) {
            return array('success' => false, 'error' => 'Seed not available');
        }

        if (!$this->player->removeInventoryItem($crop_id, 1)) {
            return array('success' => false, 'error' => 'Not enough seeds');
        }

        // Calculate ready time
        $growth_time = $crop['growth_time'];
        
        // Apply world tree reduction
        $world_tree = $this->getWorldTreeReduction();
        $growth_time = $growth_time * (1 - $world_tree['reduction'] / 100);
        
        // Apply pot reduction
        if ($plot['pot_id']) {
            $pot_query = "SELECT growth_time_reduction FROM pots WHERE id = :id";
            $pot_stmt = $this->db->prepare($pot_query);
            $pot_stmt->bindParam(':id', $plot['pot_id']);
            $pot_stmt->execute();
            $pot = $pot_stmt->fetch(PDO::FETCH_ASSOC);
            if ($pot) {
                $growth_time = $growth_time * (1 - $pot['growth_time_reduction'] / 100);
            }
        }

        $ready_at = date('Y-m-d H:i:s', time() + $growth_time);

        $query = "UPDATE garden_plots 
                  SET status = 'planted', crop_id = :crop_id, planted_at = NOW(), ready_at = :ready_at
                  WHERE user_id = :user_id AND x = :x AND y = :y";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':crop_id', $crop_id);
        $stmt->bindParam(':ready_at', $ready_at);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':x', $x, PDO::PARAM_INT);
        $stmt->bindParam(':y', $y, PDO::PARAM_INT);
        $stmt->execute();

        // Log action
        $this->player->logAction('plant_crop', array('x' => $x, 'y' => $y, 'crop_id' => $crop_id));

        return array('success' => true, 'ready_at' => $ready_at);
    }

    public function harvestCrop($x, $y) {
        $plot = $this->getPlotDetail($x, $y);

        if (!$plot || $plot['status'] !== 'ready') {
            return array('success' => false, 'error' => 'Plot is not ready to harvest');
        }

        // Get crop info
        $query = "SELECT * FROM crops WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $plot['crop_id']);
        $stmt->execute();
        $crop = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate harvest amount
        $harvest_amount = rand($crop['harvest_amount_min'], $crop['harvest_amount_max']);

        // Add to inventory
        $this->player->addInventoryItem($plot['crop_id'], $harvest_amount);

        // Reward coins and exp
        $harvest_reward_coins = $harvest_amount * 10;
        $this->player->addCoins($harvest_reward_coins, 'earn', 'harvest_crop');
        $this->player->addExp($crop['exp_reward']);

        // Clear plot
        $query = "UPDATE garden_plots SET status = 'empty', crop_id = NULL, planted_at = NULL, ready_at = NULL, pest_id = NULL WHERE user_id = :user_id AND x = :x AND y = :y";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':x', $x, PDO::PARAM_INT);
        $stmt->bindParam(':y', $y, PDO::PARAM_INT);
        $stmt->execute();

        // Log action
        $this->player->logAction('harvest_crop', array('x' => $x, 'y' => $y, 'amount' => $harvest_amount));

        return array('success' => true, 'harvest_amount' => $harvest_amount, 'reward_coins' => $harvest_reward_coins, 'reward_exp' => $crop['exp_reward']);
    }

    public function waterPlot($x, $y) {
        $plot = $this->getPlotDetail($x, $y);

        if (!$plot || $plot['status'] !== 'planted') {
            return array('success' => false, 'error' => 'No growing crop in this plot');
        }

        // Check cooldown
        $watering_cooldown = WATERING_COOLDOWN;
        if ($plot['watered_at']) {
            $last_water = strtotime($plot['watered_at']);
            $time_since = time() - $last_water;
            if ($time_since < $watering_cooldown) {
                return array('success' => false, 'error' => 'Cooldown active', 'cooldown_remaining' => $watering_cooldown - $time_since);
            }
        }

        // Check limit
        if ($plot['water_times'] >= WATERING_LIMIT_PER_PLANT) {
            return array('success' => false, 'error' => 'Watering limit reached for this plant');
        }

        // Reduce growth time
        $reduction_seconds = $plot['growth_time'] * WATERING_TIME_REDUCTION / 100;
        $new_ready_at = date('Y-m-d H:i:s', strtotime($plot['ready_at']) - $reduction_seconds);

        $query = "UPDATE garden_plots 
                  SET watered_at = NOW(), water_times = water_times + 1, ready_at = :new_ready_at
                  WHERE user_id = :user_id AND x = :x AND y = :y";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':new_ready_at', $new_ready_at);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':x', $x, PDO::PARAM_INT);
        $stmt->bindParam(':y', $y, PDO::PARAM_INT);
        $stmt->execute();

        $this->player->logAction('water_plot', array('x' => $x, 'y' => $y));

        return array('success' => true, 'new_ready_at' => $new_ready_at);
    }

    public function fertilizePlot($x, $y) {
        $plot = $this->getPlotDetail($x, $y);

        if (!$plot || $plot['status'] !== 'planted') {
            return array('success' => false, 'error' => 'No growing crop in this plot');
        }

        // Check if has fertilizer
        $query = "SELECT * FROM items WHERE type = 'fertilizer' LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $fertilizer_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fertilizer_item || !$this->player->removeInventoryItem($fertilizer_item['id'], 1)) {
            return array('success' => false, 'error' => 'Not enough fertilizer');
        }

        // Check cooldown
        $fertilize_cooldown = FERTILIZE_COOLDOWN;
        if ($plot['fertilized_at']) {
            $last_fertilize = strtotime($plot['fertilized_at']);
            $time_since = time() - $last_fertilize;
            if ($time_since < $fertilize_cooldown) {
                return array('success' => false, 'error' => 'Cooldown active');
            }
        }

        // Check limit
        if ($plot['fertilize_times'] >= FERTILIZE_LIMIT_PER_PLANT) {
            return array('success' => false, 'error' => 'Fertilizing limit reached for this plant');
        }

        // Reduce growth time immediately
        $reduction_seconds = $plot['growth_time'] * FERTILIZE_TIME_REDUCTION / 100;
        $new_ready_at = date('Y-m-d H:i:s', strtotime($plot['ready_at']) - $reduction_seconds);

        // Check if already ready
        if (strtotime($new_ready_at) < time()) {
            $new_ready_at = date('Y-m-d H:i:s');
        }

        $query = "UPDATE garden_plots 
                  SET fertilized_at = NOW(), fertilize_times = fertilize_times + 1, ready_at = :new_ready_at
                  WHERE user_id = :user_id AND x = :x AND y = :y";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':new_ready_at', $new_ready_at);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':x', $x, PDO::PARAM_INT);
        $stmt->bindParam(':y', $y, PDO::PARAM_INT);
        $stmt->execute();

        $this->player->logAction('fertilize_plot', array('x' => $x, 'y' => $y));

        return array('success' => true, 'new_ready_at' => $new_ready_at);
    }

    public function getPests() {
        $query = "SELECT gp.x, gp.y, pest.id as pest_id, pt.id as pest_type_id, pt.name, pt.icon_file, pt.color, pt.sprite_frame_x, pt.sprite_frame_y
                  FROM pests pest
                  JOIN garden_plots gp ON pest.plot_id = gp.id
                  LEFT JOIN pest_types pt ON pest.pest_type_id = pt.id
                  WHERE gp.user_id = :user_id AND gp.status = 'planted'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function killPest($pest_id) {
        $query = "SELECT pest.*, gp.x, gp.y, pt.damage_min, pt.damage_max 
                  FROM pests pest
                  JOIN garden_plots gp ON pest.plot_id = gp.id
                  LEFT JOIN pest_types pt ON pest.pest_type_id = pt.id
                  WHERE pest.id = :pest_id AND gp.user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':pest_id', $pest_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $pest = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pest) {
            return array('success' => false, 'error' => 'Pest not found');
        }

        // Get drops
        $drops = $this->getPestDrops($pest['pest_type_id']);
        $items_earned = array();

        foreach ($drops as $drop) {
            if (rand(1, 100) <= $drop['drop_chance']) {
                $quantity = rand($drop['quantity_min'], $drop['quantity_max']);
                $this->player->addInventoryItem($drop['item_id'], $quantity);
                $items_earned[] = array('item_id' => $drop['item_id'], 'quantity' => $quantity);
            }
        }

        // Remove pest
        $query = "DELETE FROM pests WHERE id = :pest_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':pest_id', $pest_id);
        $stmt->execute();

        // Update plot
        $query = "UPDATE garden_plots SET pest_id = NULL WHERE id = (SELECT plot_id FROM pests WHERE id = :pest_id LIMIT 1)";

        // Give rewards
        $this->player->addCoins(25, 'earn', 'kill_pest');
        $this->player->addExp(5);

        $this->player->logAction('kill_pest', array('x' => $pest['x'], 'y' => $pest['y'], 'pest_type_id' => $pest['pest_type_id']));

        return array('success' => true, 'items_earned' => $items_earned, 'coins_earned' => 25, 'exp_earned' => 5);
    }

    private function getPestDrops($pest_type_id) {
        $query = "SELECT pd.*, i.name, i.sprite_file 
                  FROM pest_drops pd
                  JOIN items i ON pd.item_id = i.id
                  WHERE pd.pest_type_id = :pest_type_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':pest_type_id', $pest_type_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function placePot($x, $y, $pot_id) {
        // Implementation
        return array('success' => true);
    }

    public function placeDecoration($x, $y, $decoration_id) {
        // Implementation
        return array('success' => true);
    }

    private function getWorldTreeReduction() {
        $query = "SELECT wt.level, wtu.growth_time_reduction 
                  FROM world_tree wt
                  LEFT JOIN world_tree_upgrades wtu ON wt.level = wtu.level
                  WHERE wt.user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $world_tree = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$world_tree) {
            return array('reduction' => 0);
        }

        return array('reduction' => $world_tree['growth_time_reduction'] ?? 0);
    }
}
?>
