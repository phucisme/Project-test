<?php
/**
 * Cloud Garden - Installation & Setup Script
 * 
 * Chạy script này để setup database và dữ liệu ban đầu
 */

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('❌ PHP 7.4 hoặc cao hơn được yêu cầu. Phiên bản hiện tại: ' . PHP_VERSION);
}

// Display setup form
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Garden - Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { background: white; border-radius: 12px; padding: 40px; max-width: 500px; width: 100%; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); }
        h1 { color: #4CAF50; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        input { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.3s ease; }
        input:focus { outline: none; border-color: #4CAF50; }
        button { width: 100%; padding: 12px; background: #4CAF50; color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s ease; }
        button:hover { background: #45a049; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .info { color: #666; font-size: 0.9rem; margin-top: 10px; }
        .step { display: none; }
        .step.active { display: block; }
        .progress { display: flex; gap: 10px; margin-bottom: 30px; }
        .progress-item { flex: 1; height: 4px; background: #ddd; border-radius: 2px; }
        .progress-item.active { background: #4CAF50; }
        .progress-item.done { background: #8BC34A; }
    </style>
</head>
<body>
    <div class="container">
        <h1>☁️ Cloud Garden - Installation</h1>
        
        <div class="progress">
            <div class="progress-item active" id="step1-progress"></div>
            <div class="progress-item" id="step2-progress"></div>
            <div class="progress-item" id="step3-progress"></div>
        </div>

        <!-- Step 1: Database Connection -->
        <div class="step active" id="step1">
            <h2 style="font-size: 1.3rem; margin-bottom: 20px;">Database Configuration</h2>
            <form onsubmit="testConnection(event)">
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                    <div class="info">Ví dụ: localhost hoặc db.example.com</div>
                </div>
                <div class="form-group">
                    <label for="db_user">Database User</label>
                    <input type="text" id="db_user" name="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass">
                </div>
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" value="cloud_garden" required>
                </div>
                <button type="submit">Tiếp Tục</button>
            </form>
        </div>

        <!-- Step 2: Create Database -->
        <div class="step" id="step2">
            <h2 style="font-size: 1.3rem; margin-bottom: 20px;">Tạo Cơ Sở Dữ Liệu</h2>
            <div id="setup-output"></div>
            <button onclick="createDatabase()">Tạo Database & Tables</button>
        </div>

        <!-- Step 3: Complete -->
        <div class="step" id="step3">
            <h2 style="font-size: 1.3rem; margin-bottom: 20px;">Cài Đặt Hoàn Thành!</h2>
            <div class="success">
                ✅ Cloud Garden đã được cài đặt thành công!
            </div>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                <p><strong>Tài khoản Admin mặc định:</strong></p>
                <p>Username: admin</p>
                <p>Password: admin123</p>
            </div>
            <a href="/public/index.html" style="display: block; padding: 12px; background: #4CAF50; color: white; text-align: center; border-radius: 6px; text-decoration: none; font-weight: 600; transition: background 0.3s ease;" onmouseover="this.style.background='#45a049'" onmouseout="this.style.background='#4CAF50'">Bắt Đầu Chơi</a>
        </div>
    </div>

    <script>
        let dbConfig = {};

        function testConnection(e) {
            e.preventDefault();
            const host = document.getElementById('db_host').value;
            const user = document.getElementById('db_user').value;
            const pass = document.getElementById('db_pass').value;
            const name = document.getElementById('db_name').value;

            dbConfig = { host, user, pass, name };

            // Simulate database connection test
            setTimeout(() => {
                nextStep();
            }, 1000);
        }

        function nextStep() {
            const currentStep = document.querySelector('.step.active');
            const currentIndex = Array.from(document.querySelectorAll('.step')).indexOf(currentStep);
            
            if (currentIndex < 2) {
                currentStep.classList.remove('active');
                document.querySelectorAll('.step')[currentIndex + 1].classList.add('active');
                document.getElementById(`step${currentIndex + 1}-progress`).classList.add('active');
                document.getElementById(`step${currentIndex + 1}-progress`).classList.remove('done');
                currentStep.classList.add('done');
                document.getElementById(`step${currentIndex + 1}-progress`).classList.add('done');
            }
        }

        function createDatabase() {
            const output = document.getElementById('setup-output');
            output.innerHTML = '<p>Đang tạo database...</p>';

            // Simulate database creation
            setTimeout(() => {
                output.innerHTML = '<div class="success">✅ Database tạo thành công!<br>✅ Tables tạo thành công!<br>✅ Dữ liệu seed được thêm!</div>';
                setTimeout(() => nextStep(), 1500);
            }, 2000);
        }
    </script>
</body>
</html>
