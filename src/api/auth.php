<?php
header('Content-Type: application/json');
session_start();

require_once '../src/config/Database.php';
require_once '../src/config/Config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$db = (new Database())->getPDO();

switch ($action) {
    case 'register':
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$username || !$email || !$password) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            break;
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            $query = "INSERT INTO users (username, email, password, level, coins, garden_width, garden_height) 
                      VALUES (:username, :email, :password, :level, :coins, :width, :height)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':level', $level = 1);
            $stmt->bindParam(':coins', $coins = INITIAL_COINS);
            $stmt->bindParam(':width', $width = INITIAL_GARDEN_WIDTH);
            $stmt->bindParam(':height', $height = INITIAL_GARDEN_HEIGHT);
            $stmt->execute();

            $user_id = $db->lastInsertId();

            // Create garden plots
            for ($y = 0; $y < INITIAL_GARDEN_HEIGHT; $y++) {
                for ($x = 0; $x < INITIAL_GARDEN_WIDTH; $x++) {
                    $query = "INSERT INTO garden_plots (user_id, x, y, status) VALUES (:user_id, :x, :y, 'empty')";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':x', $x);
                    $stmt->bindParam(':y', $y);
                    $stmt->execute();
                }
            }

            // Create world tree
            $query = "INSERT INTO world_tree (user_id, level) VALUES (:user_id, :level)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':level', $level = 0);
            $stmt->execute();

            // Add initial seeds to inventory
            $query = "SELECT id FROM items WHERE type = 'seed' LIMIT " . INITIAL_SEEDS;
            $stmt = $db->prepare($query);
            $stmt->execute();
            $seeds = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($seeds as $seed) {
                $query = "INSERT INTO inventory (user_id, item_id, quantity) VALUES (:user_id, :item_id, :quantity)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':item_id', $seed['id']);
                $stmt->bindParam(':quantity', $qty = 5);
                $stmt->execute();
            }

            $_SESSION['user_id'] = $user_id;
            echo json_encode(['success' => true, 'user_id' => $user_id, 'message' => 'Account created']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            echo json_encode(['success' => false, 'error' => 'Missing credentials']);
            break;
        }

        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
            break;
        }

        $_SESSION['user_id'] = $user['id'];
        
        // Update last login
        $query = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user['id']);
        $stmt->execute();

        echo json_encode(['success' => true, 'user_id' => $user['id'], 'message' => 'Logged in']);
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out']);
        break;

    case 'get_user':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            break;
        }

        $query = "SELECT id, username, email, level, exp, coins, garden_width, garden_height, max_pots, max_decorations, marketplace_slots FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'user' => $user]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
?>
