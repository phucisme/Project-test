<?php
header('Content-Type: application/json');
session_start();

require_once '../src/config/Database.php';
require_once '../src/config/Config.php';
require_once '../src/classes/Player.php';
require_once '../src/classes/Garden.php';
require_once '../src/classes/Shop.php';
require_once '../src/classes/Marketplace.php';
require_once '../src/classes/Craft.php';
require_once '../src/classes/Friends.php';
require_once '../src/classes/Quest.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$db = (new Database())->getPDO();
$user_id = $_SESSION['user_id'];
$player = new Player($db, $user_id);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_user':
        echo json_encode(['success' => true, 'user' => $player->getUserData()]);
        break;

    case 'get_garden':
        $garden = new Garden($db, $user_id, $player);
        $plots = $garden->getGarden();
        echo json_encode(['success' => true, 'garden' => $plots]);
        break;

    case 'plant_crop':
        $x = $_POST['x'] ?? 0;
        $y = $_POST['y'] ?? 0;
        $crop_id = $_POST['crop_id'] ?? 0;
        $garden = new Garden($db, $user_id, $player);
        $result = $garden->plantCrop($x, $y, $crop_id);
        echo json_encode($result);
        break;

    case 'harvest_crop':
        $x = $_POST['x'] ?? 0;
        $y = $_POST['y'] ?? 0;
        $garden = new Garden($db, $user_id, $player);
        $result = $garden->harvestCrop($x, $y);
        echo json_encode($result);
        break;

    case 'water_plot':
        $x = $_POST['x'] ?? 0;
        $y = $_POST['y'] ?? 0;
        $garden = new Garden($db, $user_id, $player);
        $result = $garden->waterPlot($x, $y);
        echo json_encode($result);
        break;

    case 'fertilize_plot':
        $x = $_POST['x'] ?? 0;
        $y = $_POST['y'] ?? 0;
        $garden = new Garden($db, $user_id, $player);
        $result = $garden->fertilizePlot($x, $y);
        echo json_encode($result);
        break;

    case 'get_inventory':
        $inventory = $player->getInventory();
        echo json_encode(['success' => true, 'inventory' => $inventory]);
        break;

    case 'get_shop':
        $shop = new Shop($db, $player);
        $items = $shop->getShopItems();
        echo json_encode(['success' => true, 'items' => $items]);
        break;

    case 'buy_item':
        $item_id = $_POST['item_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;
        $shop = new Shop($db, $player);
        $result = $shop->buyItem($item_id, $quantity);
        echo json_encode($result);
        break;

    case 'sell_item':
        $item_id = $_POST['item_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;
        $shop = new Shop($db, $player);
        $result = $shop->sellItem($item_id, $quantity);
        echo json_encode($result);
        break;

    case 'get_marketplace':
        $offset = $_GET['offset'] ?? 0;
        $limit = $_GET['limit'] ?? 20;
        $marketplace = new Marketplace($db, $user_id, $player);
        $listings = $marketplace->getListings($offset, $limit);
        echo json_encode(['success' => true, 'listings' => $listings]);
        break;

    case 'list_item':
        $item_id = $_POST['item_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;
        $price = $_POST['price'] ?? 100;
        $marketplace = new Marketplace($db, $user_id, $player);
        $result = $marketplace->listItem($item_id, $quantity, $price);
        echo json_encode($result);
        break;

    case 'buy_listing':
        $listing_id = $_POST['listing_id'] ?? 0;
        $marketplace = new Marketplace($db, $user_id, $player);
        $result = $marketplace->buyListing($listing_id);
        echo json_encode($result);
        break;

    case 'upgrade_marketplace':
        $marketplace = new Marketplace($db, $user_id, $player);
        $result = $marketplace->upgradeMarketplaceSlots();
        echo json_encode($result);
        break;

    case 'get_recipes':
        $craft = new Craft($db, $user_id, $player);
        $recipes = $craft->getRecipes();
        foreach ($recipes as &$recipe) {
            $recipe['ingredients'] = $craft->getRecipeIngredients($recipe['id']);
        }
        echo json_encode(['success' => true, 'recipes' => $recipes]);
        break;

    case 'start_production':
        $machine_id = $_POST['machine_id'] ?? 0;
        $recipe_id = $_POST['recipe_id'] ?? 0;
        $craft = new Craft($db, $user_id, $player);
        $result = $craft->startProduction($machine_id, $recipe_id);
        echo json_encode($result);
        break;

    case 'get_pests':
        $garden = new Garden($db, $user_id, $player);
        $pests = $garden->getPests();
        echo json_encode(['success' => true, 'pests' => $pests]);
        break;

    case 'kill_pest':
        $pest_id = $_POST['pest_id'] ?? 0;
        $garden = new Garden($db, $user_id, $player);
        $result = $garden->killPest($pest_id);
        echo json_encode($result);
        break;

    case 'get_friends':
        $friends = new Friends($db, $user_id);
        $friend_list = $friends->getFriends();
        echo json_encode(['success' => true, 'friends' => $friend_list]);
        break;

    case 'add_friend':
        $friend_id = $_POST['friend_id'] ?? 0;
        $friends = new Friends($db, $user_id);
        $result = $friends->addFriend($friend_id);
        echo json_encode($result);
        break;

    case 'view_friend_garden':
        $friend_id = $_GET['friend_id'] ?? 0;
        $friends = new Friends($db, $user_id);
        $result = $friends->viewFriendGarden($friend_id);
        echo json_encode($result);
        break;

    case 'get_quests':
        $quest = new Quest($db, $user_id, $player);
        $quests = $quest->getQuests();
        echo json_encode(['success' => true, 'quests' => $quests]);
        break;

    case 'complete_quest':
        $quest_id = $_POST['quest_id'] ?? 0;
        $quest = new Quest($db, $user_id, $player);
        $result = $quest->completeQuest($quest_id);
        echo json_encode($result);
        break;

    case 'get_wallet_history':
        $limit = $_GET['limit'] ?? 50;
        $history = $player->getWalletHistory($limit);
        echo json_encode(['success' => true, 'history' => $history]);
        break;

    case 'get_deliveries':
        $quest = new Quest($db, $user_id, $player);
        $deliveries = $quest->getDeliveries();
        echo json_encode(['success' => true, 'deliveries' => $deliveries]);
        break;

    case 'complete_delivery':
        $delivery_id = $_POST['delivery_id'] ?? 0;
        $quest = new Quest($db, $user_id, $player);
        $result = $quest->completeDelivery($delivery_id);
        echo json_encode($result);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
?>
