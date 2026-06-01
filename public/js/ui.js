// UI Helper Functions

function switchAuthTab(tab) {
    document.querySelectorAll('.auth-tab').forEach(el => el.classList.add('hidden'));
    document.getElementById(tab + '-tab').classList.remove('hidden');
}

function switchView(viewName) {
    // Hide all views
    document.querySelectorAll('.view').forEach(el => el.classList.add('hidden'));
    
    // Show selected view
    const viewEl = document.getElementById(`view-${viewName}`);
    if (viewEl) {
        viewEl.classList.remove('hidden');
        
        // Render view content based on type
        switch(viewName) {
            case 'inventory':
                renderInventory();
                break;
            case 'shop':
                renderShop();
                break;
            case 'marketplace':
                renderMarketplace();
                break;
            case 'craft':
                renderCraft();
                break;
            case 'quests':
                renderQuests();
                break;
            case 'friends':
                renderFriends();
                break;
        }
    }
    
    // Close menu
    document.getElementById('side-menu').classList.add('hidden');
}

function openMenu() {
    const menu = document.getElementById('side-menu');
    menu.classList.toggle('hidden');
}

function closeMenu() {
    document.getElementById('side-menu').classList.add('hidden');
}

function openModal(content) {
    const modal = document.getElementById('modal-dialog');
    const modalBody = document.getElementById('modal-body');
    modalBody.innerHTML = content;
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal-dialog').classList.add('hidden');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => notification.classList.add('show'), 10);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showTooltip(x, y, text) {
    const tooltip = document.getElementById('tooltip');
    tooltip.textContent = text;
    tooltip.style.left = x + 'px';
    tooltip.style.top = y + 'px';
    tooltip.classList.remove('hidden');
}

function hideTooltip() {
    document.getElementById('tooltip').classList.add('hidden');
}

// Inventory View
function renderInventory() {
    const container = document.getElementById('inventory-list');
    if (game.inventory.length === 0) {
        container.innerHTML = '<p class="empty-message">Túi đồ trống</p>';
        return;
    }

    const itemsByType = {};
    game.inventory.forEach(item => {
        if (!itemsByType[item.type]) itemsByType[item.type] = [];
        itemsByType[item.type].push(item);
    });

    let html = '';
    for (const [type, items] of Object.entries(itemsByType)) {
        html += `<div class="inventory-section">
            <h3>${getTypeLabel(type)}</h3>
            <div class="items-grid">`;

        items.forEach(item => {
            html += `
                <div class="inventory-item" onmouseover="showTooltip(event.clientX, event.clientY, '${item.name} (Bán: ${item.sell_price} xu)')">
                    <div class="item-image">
                        <div class="sprite" style="background-image: url('images/sprites.png'); background-position: ${item.sprite_frame_x}px ${item.sprite_frame_y}px;"></div>
                    </div>
                    <div class="item-info">
                        <p class="item-name">${item.name}</p>
                        <p class="item-quantity">x${item.quantity}</p>
                        <button onclick="game.sellItemUI(${item.item_id}, 1)" class="item-btn">Bán 1</button>
                        <button onclick="game.sellItemUI(${item.item_id}, ${item.quantity})" class="item-btn">Bán hết</button>
                    </div>
                </div>
            `;
        });

        html += '</div></div>';
    }

    container.innerHTML = html;
}

// Shop View
function renderShop() {
    const container = document.getElementById('shop-items');
    container.innerHTML = '';

    const itemsByType = {};
    game.shop.forEach(item => {
        if (item.buy_price > 0) {
            if (!itemsByType[item.type]) itemsByType[item.type] = [];
            itemsByType[item.type].push(item);
        }
    });

    let html = '';
    for (const [type, items] of Object.entries(itemsByType)) {
        html += `<div class="shop-section">
            <h3>${getTypeLabel(type)}</h3>
            <div class="items-grid">`;

        items.forEach(item => {
            html += `
                <div class="shop-item" onmouseover="showTooltip(event.clientX, event.clientY, '${item.name}')">
                    <div class="item-image">
                        <div class="sprite" style="background-image: url('images/sprites.png'); background-position: ${item.sprite_frame_x}px ${item.sprite_frame_y}px;"></div>
                    </div>
                    <div class="item-info">
                        <p class="item-name">${item.name}</p>
                        <p class="item-price">💰 ${item.buy_price}</p>
                        <button onclick="game.buyItemUI(${item.id}, 1)" class="item-btn primary">Mua</button>
                    </div>
                </div>
            `;
        });

        html += '</div></div>';
    }

    container.innerHTML = html;
}

// Marketplace View
async function renderMarketplace() {
    const result = await api.getMarketplace();
    const container = document.getElementById('marketplace-listings');
    
    if (!result.success || result.listings.length === 0) {
        container.innerHTML = '<p class="empty-message">Không có danh sách nào</p>';
        return;
    }

    let html = '<div class="listings-grid">';
    result.listings.forEach(listing => {
        html += `
            <div class="marketplace-listing">
                <div class="listing-header">
                    <p class="seller-name">${listing.username}</p>
                </div>
                <div class="listing-item">
                    <div class="item-image">
                        <div class="sprite" style="background-image: url('images/sprites.png');"></div>
                    </div>
                    <div class="listing-info">
                        <p class="item-name">${listing.name}</p>
                        <p class="listing-price">💰 ${listing.price_per_unit} xu/cái</p>
                        <p class="listing-quantity">x${listing.quantity}</p>
                        <button onclick="game.buyListingUI(${listing.id})" class="listing-btn">Mua</button>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Craft View
function renderCraft() {
    const container = document.getElementById('craft-machines');
    
    if (game.recipes.length === 0) {
        container.innerHTML = '<p class="empty-message">Không có công thức nào</p>';
        return;
    }

    let html = '<div class="recipes-grid">';
    game.recipes.forEach(recipe => {
        const ingredients = recipe.ingredients.map(ing => `${ing.name} x${ing.quantity}`).join(', ');
        html += `
            <div class="recipe-card">
                <h3>${recipe.name}</h3>
                <p><strong>Nguyên liệu:</strong> ${ingredients}</p>
                <p><strong>Sản phẩm:</strong> ${recipe.output_name}</p>
                <p><strong>Thời gian:</strong> ${recipe.production_time}s</p>
                <button onclick="game.startProductionUI(${recipe.id})" class="craft-btn">Chế tạo</button>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Quests View
function renderQuests() {
    const container = document.getElementById('quests-list');
    
    if (game.quests.length === 0) {
        container.innerHTML = '<p class="empty-message">Không có nhiệm vụ nào</p>';
        return;
    }

    let html = '';
    game.quests.forEach(quest => {
        const completed = quest.completed ? 'completed' : '';
        const progress = Math.floor((quest.progress / quest.target_quantity) * 100);
        
        html += `
            <div class="quest-card ${completed}">
                <div class="quest-header">
                    <h3>${quest.name}</h3>
                    <span class="quest-type">${quest.type}</span>
                </div>
                <p class="quest-description">${quest.description}</p>
                <div class="quest-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progress}%"></div>
                    </div>
                    <p class="progress-text">${quest.progress}/${quest.target_quantity}</p>
                </div>
                <div class="quest-rewards">
                    <p>💰 ${quest.reward_coins} xu</p>
                    <p>⭐ ${quest.reward_exp} EXP</p>
                </div>
                ${quest.completed ? 
                    '<button class="quest-btn" onclick="game.completeQuestUI(' + quest.id + ')">Nhận thưởng</button>' : 
                    ''}
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Friends View
function renderFriends() {
    const container = document.getElementById('friends-list');
    
    if (game.friends.length === 0) {
        container.innerHTML = '<p class="empty-message">Bạn chưa có bạn bè nào</p>';
        return;
    }

    let html = '<div class="friends-grid">';
    game.friends.forEach(friend => {
        html += `
            <div class="friend-card">
                <p class="friend-name">${friend.username}</p>
                <p class="friend-level">Level ${friend.level}</p>
                <p class="friend-garden">${friend.garden_width}x${friend.garden_height}</p>
                <button onclick="game.viewFriendGardenUI(${friend.id})" class="friend-btn">Xem vườn</button>
                <button onclick="game.removeFriendUI(${friend.id})" class="friend-btn danger">Hủy kết bạn</button>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Type label helper
function getTypeLabel(type) {
    const labels = {
        'seed': '🌱 Hạt giống',
        'crop': '🌾 Cây trồng',
        'tool': '🔧 Dụng cụ',
        'decoration': '🎨 Trang trí',
        'fertilizer': '🌾 Phân bón',
        'cloud': '☁️ Mây',
        'pot': '🪴 Chậu',
        'other': '📦 Khác'
    };
    return labels[type] || type;
}

// Game action UIs
async function game.buyItemUI(itemId, quantity) {
    const result = await api.buyItem(itemId, quantity);
    if (result.success) {
        showNotification(`✅ Mua thành công! Chi phí: ${result.total_cost} xu`, 'success');
        game.currentUser.coins -= result.total_cost;
        game.updatePlayerUI();
        renderShop();
        renderInventory();
    } else {
        showNotification(result.error, 'error');
    }
}

async function game.sellItemUI(itemId, quantity) {
    const result = await api.sellItem(itemId, quantity);
    if (result.success) {
        showNotification(`✅ Bán thành công! Nhận: ${result.total_price} xu`, 'success');
        game.currentUser.coins += result.total_price;
        game.updatePlayerUI();
        renderInventory();
    } else {
        showNotification(result.error, 'error');
    }
}

async function game.buyListingUI(listingId) {
    const result = await api.buyListing(listingId);
    if (result.success) {
        showNotification(`✅ Mua thành công! Chi phí: ${result.total_cost} xu`, 'success');
        game.currentUser.coins -= result.total_cost;
        game.updatePlayerUI();
        renderMarketplace();
        renderInventory();
    } else {
        showNotification(result.error, 'error');
    }
}

// Close modal when clicking outside
document.addEventListener('click', (e) => {
    const modal = document.getElementById('modal-dialog');
    if (e.target === modal) {
        closeModal();
    }
});

// Handle login
document.getElementById('login-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;
    
    const result = await api.login(username, password);
    if (result.success) {
        game.init();
    } else {
        showNotification(result.error, 'error');
    }
});

// Handle register
document.getElementById('register-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('register-username').value;
    const email = document.getElementById('register-email').value;
    const password = document.getElementById('register-password').value;
    const confirm = document.getElementById('register-confirm').value;
    
    if (password !== confirm) {
        showNotification('Mật khẩu không khớp!', 'error');
        return;
    }
    
    const result = await api.register(username, email, password);
    if (result.success) {
        showNotification('✅ Tài khoản tạo thành công!', 'success');
        switchAuthTab('login');
    } else {
        showNotification(result.error, 'error');
    }
});

function logout() {
    game.logout();
}
