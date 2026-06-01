// API Helper Functions
class GameAPI {
    constructor() {
        this.baseUrl = '/src/api';
    }

    async request(endpoint, action, data = {}) {
        try {
            const formData = new FormData();
            formData.append('action', action);
            
            for (let [key, value] of Object.entries(data)) {
                if (typeof value === 'object') {
                    formData.append(key, JSON.stringify(value));
                } else {
                    formData.append(key, value);
                }
            }

            const response = await fetch(`${this.baseUrl}/${endpoint}.php`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            return result;
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, error: error.message };
        }
    }

    async get(endpoint, action, params = {}) {
        try {
            const queryString = new URLSearchParams();
            queryString.append('action', action);
            for (let [key, value] of Object.entries(params)) {
                queryString.append(key, value);
            }

            const response = await fetch(`${this.baseUrl}/${endpoint}.php?${queryString}`, {
                method: 'GET'
            });

            const result = await response.json();
            return result;
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, error: error.message };
        }
    }

    // Auth
    async login(username, password) {
        return this.request('auth', 'login', { username, password });
    }

    async register(username, email, password) {
        return this.request('auth', 'register', { username, email, password });
    }

    async logout() {
        return this.request('auth', 'logout', {});
    }

    async getUser() {
        return this.get('auth', 'get_user', {});
    }

    // Game
    async getGarden() {
        return this.request('game', 'get_garden', {});
    }

    async plantCrop(x, y, cropId) {
        return this.request('game', 'plant_crop', { x, y, crop_id: cropId });
    }

    async harvestCrop(x, y) {
        return this.request('game', 'harvest_crop', { x, y });
    }

    async waterPlot(x, y) {
        return this.request('game', 'water_plot', { x, y });
    }

    async fertilizePlot(x, y) {
        return this.request('game', 'fertilize_plot', { x, y });
    }

    async getInventory() {
        return this.request('game', 'get_inventory', {});
    }

    async getShop() {
        return this.get('game', 'get_shop', {});
    }

    async buyItem(itemId, quantity = 1) {
        return this.request('game', 'buy_item', { item_id: itemId, quantity });
    }

    async sellItem(itemId, quantity = 1) {
        return this.request('game', 'sell_item', { item_id: itemId, quantity });
    }

    async getMarketplace(offset = 0, limit = 20) {
        return this.get('game', 'get_marketplace', { offset, limit });
    }

    async listItem(itemId, quantity, price) {
        return this.request('game', 'list_item', { item_id: itemId, quantity, price });
    }

    async buyListing(listingId) {
        return this.request('game', 'buy_listing', { listing_id: listingId });
    }

    async upgradeMarketplace() {
        return this.request('game', 'upgrade_marketplace', {});
    }

    async getRecipes() {
        return this.get('game', 'get_recipes', {});
    }

    async startProduction(machineId, recipeId) {
        return this.request('game', 'start_production', { machine_id: machineId, recipe_id: recipeId });
    }

    async getPests() {
        return this.get('game', 'get_pests', {});
    }

    async killPest(pestId) {
        return this.request('game', 'kill_pest', { pest_id: pestId });
    }

    async getFriends() {
        return this.get('game', 'get_friends', {});
    }

    async addFriend(friendId) {
        return this.request('game', 'add_friend', { friend_id: friendId });
    }

    async viewFriendGarden(friendId) {
        return this.get('game', 'view_friend_garden', { friend_id: friendId });
    }

    async getQuests() {
        return this.get('game', 'get_quests', {});
    }

    async completeQuest(questId) {
        return this.request('game', 'complete_quest', { quest_id: questId });
    }

    async getWalletHistory(limit = 50) {
        return this.get('game', 'get_wallet_history', { limit });
    }

    async getDeliveries() {
        return this.get('game', 'get_deliveries', {});
    }

    async completeDelivery(deliveryId) {
        return this.request('game', 'complete_delivery', { delivery_id: deliveryId });
    }
}

const api = new GameAPI();
