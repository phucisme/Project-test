-- Cloud Garden Game Database Schema

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    level INT DEFAULT 1,
    exp INT DEFAULT 0,
    coins INT DEFAULT 1000,
    garden_width INT DEFAULT 4,
    garden_height INT DEFAULT 4,
    max_pots INT DEFAULT 5,
    max_decorations INT DEFAULT 10,
    marketplace_slots INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS garden_plots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    x INT NOT NULL,
    y INT NOT NULL,
    status ENUM('empty', 'planted', 'ready') DEFAULT 'empty',
    crop_id INT NULL,
    planted_at TIMESTAMP NULL,
    ready_at TIMESTAMP NULL,
    pot_id INT NULL,
    decoration_id INT NULL,
    pest_id INT NULL,
    watered_at TIMESTAMP NULL,
    fertilized_at TIMESTAMP NULL,
    water_times INT DEFAULT 0,
    fertilize_times INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plot (user_id, x, y)
);

CREATE TABLE IF NOT EXISTS crops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    growth_time INT NOT NULL, -- seconds
    harvest_amount_min INT DEFAULT 1,
    harvest_amount_max INT DEFAULT 3,
    exp_reward INT DEFAULT 10,
    sprite_file VARCHAR(255),
    stages INT DEFAULT 2,
    stage_images JSON,
    overlay_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('seed', 'crop', 'tool', 'decoration', 'fertilizer', 'cloud', 'pot', 'machine_part', 'recipe_ingredient', 'other') NOT NULL,
    sprite_file VARCHAR(255),
    sprite_frame_x INT DEFAULT 0,
    sprite_frame_y INT DEFAULT 0,
    sprite_width INT DEFAULT 32,
    sprite_height INT DEFAULT 32,
    overlay_data JSON,
    effects JSON,
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') DEFAULT 'common',
    sell_price INT DEFAULT 0,
    buy_price INT DEFAULT 0,
    max_stack INT DEFAULT 99,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id),
    UNIQUE KEY unique_inventory (user_id, item_id)
);

CREATE TABLE IF NOT EXISTS pots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    level INT DEFAULT 1,
    growth_time_reduction DECIMAL(5,2) DEFAULT 0, -- percentage
    sprite_file VARCHAR(255),
    overlay_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS decorations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sprite_file VARCHAR(255),
    overlay_data JSON,
    effects JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pest_type_id INT NOT NULL,
    plot_id INT NOT NULL,
    attack_time TIMESTAMP,
    damage INT DEFAULT 60, -- damage to growth time in seconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plot_id) REFERENCES garden_plots(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pest_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    icon_file VARCHAR(255),
    color VARCHAR(7) DEFAULT '#FF0000',
    sprite_frame_x INT DEFAULT 0,
    sprite_frame_y INT DEFAULT 0,
    damage_min INT DEFAULT 30,
    damage_max INT DEFAULT 60,
    spawn_rate DECIMAL(5,2) DEFAULT 5, -- percentage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pest_drops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pest_type_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_min INT DEFAULT 1,
    quantity_max INT DEFAULT 1,
    drop_chance DECIMAL(5,2) DEFAULT 30, -- percentage
    FOREIGN KEY (pest_type_id) REFERENCES pest_types(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

CREATE TABLE IF NOT EXISTS world_tree (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    level INT DEFAULT 0,
    growth_time_reduction DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_world_tree (user_id)
);

CREATE TABLE IF NOT EXISTS world_tree_upgrades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    level INT NOT NULL,
    growth_time_reduction DECIMAL(5,2) DEFAULT 0,
    upgrade_cost_item_id INT,
    upgrade_cost_quantity INT DEFAULT 1,
    upgrade_cost_coins INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS machines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    tier INT DEFAULT 1,
    level INT DEFAULT 1,
    max_slots INT DEFAULT 1,
    sprite_file VARCHAR(255),
    overlay_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS machine_definitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    tier INT NOT NULL,
    level INT NOT NULL,
    unlock_cost_coins INT DEFAULT 0,
    unlock_cost_item_id INT,
    unlock_cost_quantity INT DEFAULT 1,
    upgrade_cost_coins INT DEFAULT 0,
    upgrade_cost_item_id INT,
    upgrade_cost_quantity INT DEFAULT 1,
    max_slots INT DEFAULT 1,
    sprite_file VARCHAR(255),
    overlay_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tier_level (tier, level)
);

CREATE TABLE IF NOT EXISTS production_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    machine_id INT NOT NULL,
    recipe_id INT NOT NULL,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    status ENUM('waiting', 'producing', 'ready') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id)
);

CREATE TABLE IF NOT EXISTS recipes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    output_item_id INT NOT NULL,
    output_quantity INT DEFAULT 1,
    production_time INT NOT NULL, -- seconds
    sprite_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (output_item_id) REFERENCES items(id)
);

CREATE TABLE IF NOT EXISTS recipe_ingredients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

CREATE TABLE IF NOT EXISTS expansions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    width INT NOT NULL,
    height INT NOT NULL,
    cost_coins INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS level_definitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    level INT NOT NULL UNIQUE,
    exp_required INT NOT NULL,
    exp_to_next INT NOT NULL,
    reward_coins INT DEFAULT 0,
    reward_item_id INT,
    reward_item_quantity INT DEFAULT 1,
    max_pots INT DEFAULT 5,
    max_decorations INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reward_item_id) REFERENCES items(id)
);

CREATE TABLE IF NOT EXISTS quests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('harvest', 'kill_pest', 'water', 'fertilize', 'plant', 'unlock_machine', 'expand_garden', 'buy_item', 'level_up', 'complete_recipe') NOT NULL,
    target_quantity INT DEFAULT 1,
    reward_coins INT DEFAULT 0,
    reward_exp INT DEFAULT 0,
    reward_item_id INT,
    reward_item_quantity INT DEFAULT 1,
    trigger_condition JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reward_item_id) REFERENCES items(id)
);

CREATE TABLE IF NOT EXISTS player_quests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    quest_id INT NOT NULL,
    progress INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES quests(id)
);

CREATE TABLE IF NOT EXISTS deliveries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    reward_coins INT DEFAULT 0,
    reward_exp INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    cooldown_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

CREATE TABLE IF NOT EXISTS friendships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_id)
);

CREATE TABLE IF NOT EXISTS marketplace_listings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    price_per_unit INT NOT NULL,
    commission_paid INT DEFAULT 0,
    sold BOOLEAN DEFAULT FALSE,
    sold_to_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sold_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (sold_to_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS wallet_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_type ENUM('earn', 'spend', 'recharge', 'reward', 'marketplace_sell', 'marketplace_buy') NOT NULL,
    amount INT NOT NULL,
    reference_id INT,
    reference_type VARCHAR(50),
    description TEXT,
    balance_after INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at)
);

CREATE TABLE IF NOT EXISTS player_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    action_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_action (user_id, action_type, created_at)
);

CREATE TABLE IF NOT EXISTS cloud_themes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    sprite_file VARCHAR(255),
    default_theme BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_cloud_themes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    tier INT NOT NULL,
    cloud_theme_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cloud_theme_id) REFERENCES cloud_themes(id),
    UNIQUE KEY unique_user_tier (user_id, tier)
);

CREATE TABLE IF NOT EXISTS modules_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    module_name VARCHAR(255) UNIQUE NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    config_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS preload_resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resource_type ENUM('image', 'font', 'script', 'style') NOT NULL,
    resource_path VARCHAR(255) NOT NULL,
    cache_version VARCHAR(50) DEFAULT '1.0',
    required BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX idx_user_level ON users(level);
CREATE INDEX idx_plot_user ON garden_plots(user_id);
CREATE INDEX idx_plot_status ON garden_plots(status);
CREATE INDEX idx_inventory_user ON inventory(user_id);
CREATE INDEX idx_quest_user ON player_quests(user_id);
CREATE INDEX idx_marketplace_user ON marketplace_listings(user_id);
CREATE INDEX idx_marketplace_active ON marketplace_listings(sold, created_at);
CREATE INDEX idx_friendship_user ON friendships(user_id);
