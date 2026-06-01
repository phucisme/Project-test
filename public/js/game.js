// Main Game Logic
class CloudGarden {
    constructor() {
        this.currentUser = null;
        this.garden = [];
        this.inventory = [];
        this.shop = [];
        this.recipes = [];
        this.quests = [];
        this.friends = [];
        this.autoPlantEnabled = false;
        this.autoHarvestEnabled = false;
        this.selectedPlot = null;
        this.gameLoopInterval = null;
    }

    // Initialize game
    async init() {
        console.log('Initializing Cloud Garden...');
        
        // Check if user is logged in
        const authResult = await api.getUser();
        if (!authResult.success) {
            this.showAuthPage();
            return;
        }

        this.currentUser = authResult.user;
        await this.loadGameData();
        this.showGamePage();
        this.renderGarden();
        this.startGameLoop();
    }

    // Load all game data
    async loadGameData() {
        const [gardenResult, inventoryResult, shopResult, recipesResult, questsResult, friendsResult] = await Promise.all([
            api.getGarden(),
            api.getInventory(),
            api.getShop(),
            api.getRecipes(),
            api.getQuests(),
            api.getFriends()
        ]);

        if (gardenResult.success) this.garden = gardenResult.garden;
        if (inventoryResult.success) this.inventory = inventoryResult.inventory;
        if (shopResult.success) this.shop = shopResult.items;
        if (recipesResult.success) this.recipes = recipesResult.recipes;
        if (questsResult.success) this.quests = questsResult.quests;
        if (friendsResult.success) this.friends = friendsResult.friends;

        console.log('Game data loaded');
    }

    // Update player UI
    updatePlayerUI() {
        document.getElementById('player-level').textContent = this.currentUser.level;
        document.getElementById('player-exp').textContent = this.currentUser.exp;
        document.getElementById('player-coins').textContent = this.currentUser.coins.toLocaleString();
    }

    // Render garden
    renderGarden() {
        const container = document.getElementById('garden-container');
        container.innerHTML = '';
        container.style.gridTemplateColumns = `repeat(${this.currentUser.garden_width}, 1fr)`;

        for (let y = 0; y < this.currentUser.garden_height; y++) {
            for (let x = 0; x < this.currentUser.garden_width; x++) {
                const plot = this.garden.find(p => p.x === x && p.y === y);
                const plotEl = this.createPlotElement(plot, x, y);
                container.appendChild(plotEl);
            }
        }
    }

    // Create plot element
    createPlotElement(plot, x, y) {
        const plotEl = document.createElement('div');
        plotEl.className = 'garden-plot';
        plotEl.id = `plot-${x}-${y}`;

        if (!plot) {
            plotEl.classList.add('empty');
            plotEl.innerHTML = '<span class="empty-icon">🌱</span>';
        } else {
            plotEl.classList.add(plot.status);
            plotEl.dataset.plotId = plot.id;
            
            let content = '';
            
            if (plot.status === 'empty' && plot.decoration_id) {
                content = '<span class="deco-item">🎨</span>';
            } else if (plot.status === 'planted') {
                content = '<span class="growing-plant">🌿</span>';
                if (plot.pest_id) {
                    content += '<span class="pest-icon pest-active" onclick="game.killPestUI(' + plot.pest_id + ')">🐛</span>';
                }
            } else if (plot.status === 'ready') {
                content = '<span class="ready-plant harvest-ready">🌻</span>';
            }

            plotEl.innerHTML = content;
        }

        plotEl.addEventListener('click', () => this.selectPlot(x, y, plot));
        plotEl.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.showPlotContextMenu(x, y, plot);
        });

        return plotEl;
    }

    // Select plot
    selectPlot(x, y, plot) {
        this.selectedPlot = { x, y, data: plot };
        
        let info = `<strong>Ô trồng (${x}, ${y})</strong><br>`;
        
        if (!plot) {
            info += 'Trống<br>';
            info += `<button onclick="game.showPlantModal(${x}, ${y})">🌱 Trồng</button>`;
        } else {
            info += `Trạng thái: ${plot.status}<br>`;
            
            if (plot.status === 'planted') {
                info += `<p>Cây: ${plot.crop_name}</p>`;
                const readyAt = new Date(plot.ready_at);
                const now = new Date();
                const timeLeft = Math.max(0, Math.floor((readyAt - now) / 1000));
                info += `Thời gian còn lại: ${this.formatTime(timeLeft)}<br>`;
                
                if (timeLeft > 0) {
                    info += `<button onclick="game.waterPlotUI(${x}, ${y})">💧 Tưới</button>`;
                    info += `<button onclick="game.fertilizePlotUI(${x}, ${y})">🌾 Bón phân</button>`;
                }
            } else if (plot.status === 'ready') {
                info += `<button onclick="game.harvestUI(${x}, ${y})">✂️ Thu hoạch</button>`;
            }

            if (plot.pot_name) {
                info += `<br>Chậu: ${plot.pot_name}`;
            }
        }

        document.getElementById('plot-info').innerHTML = info;
    }

    // Plant crop
    async plantCrop(x, y, cropId) {
        const result = await api.plantCrop(x, y, cropId);
        if (result.success) {
            showNotification('✅ Đã trồng cây thành công!', 'success');
            await this.loadGameData();
            this.renderGarden();
            closeModal();
        } else {
            showNotification(result.error, 'error');
        }
    }

    // Harvest
    async harvestUI(x, y) {
        const result = await api.harvestCrop(x, y);
        if (result.success) {
            showNotification(`✂️ Thu hoạch được ${result.harvest_amount} sản phẩm! Nhận ${result.reward_coins} xu`, 'success');
            this.currentUser.coins += result.reward_coins;
            this.currentUser.exp += result.reward_exp;
            this.updatePlayerUI();
            await this.loadGameData();
            this.renderGarden();
        } else {
            showNotification(result.error, 'error');
        }
    }

    // Water plot
    async waterPlotUI(x, y) {
        const result = await api.waterPlot(x, y);
        if (result.success) {
            showNotification('💧 Đã tưới nước!', 'success');
            await this.loadGameData();
            this.renderGarden();
        } else {
            showNotification(result.error, 'error');
        }
    }

    // Fertilize plot
    async fertilizePlotUI(x, y) {
        const result = await api.fertilizePlot(x, y);
        if (result.success) {
            showNotification('🌾 Bón phân thành công!', 'success');
            await this.loadGameData();
            this.renderGarden();
        } else {
            showNotification(result.error, 'error');
        }
    }

    // Kill pest
    killPestUI(pestId) {
        const modal = document.getElementById('modal-dialog');
        const modalBody = document.getElementById('modal-body');
        
        modalBody.innerHTML = `
            <h2>🐛 Tiêu diệt sâu bệnh?</h2>
            <p>Bạn có chắc muốn tiêu diệt sâu bệnh này không?</p>
            <button onclick="game.killPest(${pestId})">Có, tiêu diệt</button>
            <button onclick="closeModal()">Hủy</button>
        `;
        
        modal.classList.remove('hidden');
    }

    async killPest(pestId) {
        const result = await api.killPest(pestId);
        if (result.success) {
            showNotification('✅ Đã tiêu diệt sâu bệnh!', 'success');
            this.currentUser.coins += result.coins_earned;
            this.currentUser.exp += result.exp_earned;
            this.updatePlayerUI();
            await this.loadGameData();
            this.renderGarden();
            closeModal();
        } else {
            showNotification(result.error, 'error');
        }
    }

    // Show plant modal
    showPlantModal(x, y) {
        const modal = document.getElementById('modal-dialog');
        const modalBody = document.getElementById('modal-body');
        
        const cropOptions = this.inventory
            .filter(item => item.type === 'seed')
            .map(seed => `<button onclick="game.plantCrop(${x}, ${y}, ${seed.item_id})" class="crop-option">
                ${seed.name} <small>(${seed.quantity})</small>
            </button>`).join('');

        modalBody.innerHTML = `
            <h2>🌱 Chọn cây để trồng</h2>
            <div class="crop-list">
                ${cropOptions || '<p>Bạn không có hạt giống nào</p>'}
            </div>
        `;
        
        modal.classList.remove('hidden');
    }

    // Show plot context menu
    showPlotContextMenu(x, y, plot) {
        // Implementation for context menu
    }

    // Format time
    formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        if (hours > 0) {
            return `${hours}h ${minutes}m ${secs}s`;
        } else if (minutes > 0) {
            return `${minutes}m ${secs}s`;
        } else {
            return `${secs}s`;
        }
    }

    // Game loop
    startGameLoop() {
        this.gameLoopInterval = setInterval(() => {
            this.updateGardenStatus();
            this.updatePlayerUI();
        }, 5000); // Update every 5 seconds
    }

    // Update garden status
    updateGardenStatus() {
        const now = new Date();
        this.garden.forEach(plot => {
            if (plot.status === 'planted' && plot.ready_at) {
                const readyAt = new Date(plot.ready_at);
                if (now >= readyAt) {
                    plot.status = 'ready';
                }
            }
        });

        this.renderGarden();
    }

    // Toggle auto plant
    toggleAutoPlant() {
        this.autoPlantEnabled = !this.autoPlantEnabled;
        showNotification(this.autoPlantEnabled ? '🤖 Tự động trồng cây: BẬT' : '🤖 Tự động trồng cây: TẮT', 'info');
    }

    // Toggle auto harvest
    toggleAutoHarvest() {
        this.autoHarvestEnabled = !this.autoHarvestEnabled;
        showNotification(this.autoHarvestEnabled ? '🤖 Tự động thu hoạch: BẬT' : '🤖 Tự động thu hoạch: TẮT', 'info');
    }

    // Show auth page
    showAuthPage() {
        document.getElementById('auth-page').classList.remove('hidden');
        document.getElementById('game-page').classList.add('hidden');
        document.getElementById('loading-screen').classList.add('hidden');
    }

    // Show game page
    showGamePage() {
        document.getElementById('auth-page').classList.add('hidden');
        document.getElementById('game-page').classList.remove('hidden');
        document.getElementById('loading-screen').classList.add('hidden');
        this.updatePlayerUI();
    }

    // Logout
    async logout() {
        const result = await api.logout();
        if (result.success) {
            this.showAuthPage();
            clearInterval(this.gameLoopInterval);
        }
    }
}

// Global game instance
let game = new CloudGarden();

// Form handlers
async function handleLogin(e) {
    e.preventDefault();
    
    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;
    
    const result = await api.login(username, password);
    if (result.success) {
        game.init();
    } else {
        showNotification('❌ ' + (result.error || 'Đăng nhập thất bại'), 'error');
    }
}

async function handleRegister(e) {
    e.preventDefault();
    
    const username = document.getElementById('register-username').value;
    const email = document.getElementById('register-email').value;
    const password = document.getElementById('register-password').value;
    const confirm_password = document.getElementById('register-confirm').value;
    
    // Client-side validation
    if (username.length < 3) {
        showNotification('❌ Tên đăng nhập phải từ 3 ký tự trở lên', 'error');
        return;
    }
    
    if (!email.includes('@')) {
        showNotification('❌ Email không hợp lệ', 'error');
        return;
    }
    
    if (password.length < 6) {
        showNotification('❌ Mật khẩu phải từ 6 ký tự trở lên', 'error');
        return;
    }
    
    if (password !== confirm_password) {
        showNotification('❌ Mật khẩu xác nhận không khớp', 'error');
        return;
    }
    
    const result = await api.register(username, email, password, confirm_password);
    if (result.success) {
        showNotification('✅ Đăng ký thành công! Đang vào game...', 'success');
        setTimeout(() => {
            game.init();
        }, 1000);
    } else {
        showNotification('❌ ' + (result.error || 'Đăng ký thất bại'), 'error');
    }
}

// Initialize on load
window.addEventListener('load', () => {
    // Register service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('js/sw.js').catch(err => console.log('SW registration failed:', err));
    }
    
    // Setup form handlers
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    game.init();
});
