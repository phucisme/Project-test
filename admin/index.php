<?php
// Admin Panel - Dashboard
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

require_once '../src/config/Database.php';
require_once '../src/config/Config.php';

$db = (new Database())->getPDO();

// Get statistics
$query = "SELECT COUNT(*) as total FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$user_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT COUNT(*) as total FROM player_quests WHERE completed = TRUE";
$stmt = $db->prepare($query);
$stmt->execute();
$quest_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT SUM(coins) as total_coins FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$economy_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT COUNT(DISTINCT user_id) as active_players FROM player_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$stmt = $db->prepare($query);
$stmt->execute();
$activity_stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Cloud Garden</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .admin-container {
            display: flex;
            height: 100vh;
        }

        .admin-sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            overflow-y: auto;
        }

        .admin-sidebar h2 {
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .admin-sidebar nav a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        .admin-sidebar nav a:hover,
        .admin-sidebar nav a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .admin-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .admin-header {
            background: white;
            border-bottom: 1px solid #ddd;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-main {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #4CAF50;
        }

        .data-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .data-table h3 {
            padding: 15px 20px;
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
        }

        .data-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f5f5f5;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .data-table tr:hover {
            background: #f9f9f9;
        }

        .btn {
            padding: 8px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #45a049;
        }

        .btn-danger {
            background: #f44336;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-overlay.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal {
            background: white;
            border-radius: 8px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .modal h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 80px;
            }

            .admin-sidebar h2 {
                display: none;
            }

            .admin-sidebar nav a {
                text-align: center;
                padding: 12px 5px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <h2>⚙️ Admin</h2>
            <nav>
                <a href="#" class="nav-link active" onclick="loadDashboard()">Dashboard</a>
                <a href="#" class="nav-link" onclick="loadUsers()">Người chơi</a>
                <a href="#" class="nav-link" onclick="loadCrops()">Cây trồng</a>
                <a href="#" class="nav-link" onclick="loadItems()">Vật phẩm</a>
                <a href="#" class="nav-link" onclick="loadQuests()">Nhiệm vụ</a>
                <a href="#" class="nav-link" onclick="loadPests()">Sâu bệnh</a>
                <a href="#" class="nav-link" onclick="loadSettings()">Cài đặt</a>
                <a href="#" class="nav-link" onclick="logout()">Đăng xuất</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="admin-content">
            <header class="admin-header">
                <h1>Cloud Garden - Admin Panel</h1>
                <p id="user-info">Admin</p>
            </header>

            <main class="admin-main" id="admin-main">
                <!-- Dashboard -->
                <div id="dashboard-view">
                    <h2>Dashboard</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Tổng người chơi</h3>
                            <div class="stat-value"><?php echo $user_stats['total']; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Hoạt động 24h</h3>
                            <div class="stat-value"><?php echo $activity_stats['active_players']; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Tổng xu trong hệ</h3>
                            <div class="stat-value"><?php echo number_format($economy_stats['total_coins'] ?? 0); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Nhiệm vụ hoàn thành</h3>
                            <div class="stat-value"><?php echo $quest_stats['total']; ?></div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="modal">
        <div class="modal">
            <button class="modal-close" onclick="closeModal()">×</button>
            <div id="modal-content"></div>
        </div>
    </div>

    <script>
        function loadDashboard() {
            document.getElementById('admin-main').innerHTML = `
                <h2>Dashboard</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Tổng người chơi</h3>
                        <div class="stat-value"><?php echo $user_stats['total']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Hoạt động 24h</h3>
                        <div class="stat-value"><?php echo $activity_stats['active_players']; ?></div>
                    </div>
                </div>
            `;
        }

        function loadUsers() {
            alert('Tính năng quản lý người chơi sẽ được thêm');
        }

        function loadCrops() {
            alert('Tính năng quản lý cây trồng sẽ được thêm');
        }

        function loadItems() {
            alert('Tính năng quản lý vật phẩm sẽ được thêm');
        }

        function loadQuests() {
            alert('Tính năng quản lý nhiệm vụ sẽ được thêm');
        }

        function loadPests() {
            alert('Tính năng quản lý sâu bệnh sẽ được thêm');
        }

        function loadSettings() {
            alert('Tính năng cài đặt sẽ được thêm');
        }

        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }

        function closeModal() {
            document.getElementById('modal').classList.remove('show');
        }

        // Set active menu
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                event.target.classList.add('active');
            });
        });
    </script>
</body>
</html>
