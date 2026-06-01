<?php
// Game Configuration
define('GAME_NAME', 'Cloud Garden');
define('VERSION', '1.0.0');
define('CACHE_VERSION', '1.0.0');

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'cloud_garden');
define('DB_USER', 'root');
define('DB_PASS', '');

// Game Settings
define('INITIAL_COINS', 1000);
define('INITIAL_GARDEN_WIDTH', 4);
define('INITIAL_GARDEN_HEIGHT', 4);
define('INITIAL_SEEDS', 5);
define('INITIAL_LEVEL', 1);

// Experience & Leveling
define('EXP_PER_HARVEST', 10);
define('EXP_PER_PEST_KILL', 5);
define('EXP_PER_CRAFT', 15);

// Garden Mechanics
define('WATERING_COOLDOWN', 300); // 5 minutes in seconds
define('WATERING_TIME_REDUCTION', 10); // percentage
define('WATERING_LIMIT_PER_PLANT', 3);

define('FERTILIZE_COOLDOWN', 600); // 10 minutes
define('FERTILIZE_TIME_REDUCTION', 20); // percentage
define('FERTILIZE_LIMIT_PER_PLANT', 2);

define('PEST_CHECK_COOLDOWN', 60); // 1 minute

// Pest System
define('PEST_SPAWN_RATE', 5); // percentage per growth tick
define('PEST_DAMAGE_MIN', 30); // seconds
define('PEST_DAMAGE_MAX', 60);

// Marketplace
define('MARKETPLACE_COMMISSION_RATE', 10); // percentage
define('MARKETPLACE_SLOT_UPGRADE_COST', 100); // coins per slot
define('MARKETPLACE_DEFAULT_SLOTS', 3);

// Pots & Decorations
define('DEFAULT_MAX_POTS', 5);
define('DEFAULT_MAX_DECORATIONS', 10);

// Shop Prices
$SHOP_ITEMS = array(
    'basic_seed' => 50,
    'golden_pot' => 200,
    'fertilizer' => 75,
    'decoration_1' => 100
);

// Cloud Tier System
$CLOUD_TIERS = array(
    0 => array('name' => 'Personal', 'width' => 4, 'height' => 4, 'expansion_cost' => 0),
    1 => array('name' => 'Small Cloud', 'width' => 6, 'height' => 6, 'expansion_cost' => 500),
    2 => array('name' => 'Medium Cloud', 'width' => 8, 'height' => 8, 'expansion_cost' => 1500),
    3 => array('name' => 'Large Cloud', 'width' => 10, 'height' => 10, 'expansion_cost' => 3000),
    4 => array('name' => 'Mega Cloud', 'width' => 12, 'height' => 12, 'expansion_cost' => 5000)
);

// Admin Settings
$ADMIN_SETTINGS = array(
    'enable_shop' => true,
    'enable_marketplace' => true,
    'enable_quests' => true,
    'enable_multiplayer' => true,
    'enable_world_tree' => true,
    'pest_spawn_enabled' => true
);

// API Response Codes
define('API_SUCCESS', 200);
define('API_ERROR', 400);
define('API_UNAUTHORIZED', 401);
define('API_FORBIDDEN', 403);
define('API_NOT_FOUND', 404);
define('API_SERVER_ERROR', 500);

// Preload Resources
$PRELOAD_RESOURCES = array(
    'images' => array(
        'public/images/plants/tomato.png',
        'public/images/plants/carrot.png',
        'public/images/pots/pot_basic.png',
        'public/images/pots/pot_golden.png',
        'public/images/pests/bug_1.png',
        'public/images/decorations/flower.png'
    ),
    'fonts' => array(
        'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700'
    ),
    'scripts' => array(
        'public/js/game.js',
        'public/js/ui.js',
        'public/js/api.js'
    )
);

?>
