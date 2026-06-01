/*
 * Cloud Garden - Sample Data Seed
 * Run this script to populate initial game data
 */

-- Sample Crops
INSERT INTO crops (name, description, growth_time, harvest_amount_min, harvest_amount_max, exp_reward, sprite_file, stages, stage_images) VALUES
('Cà Chua', 'Cà chua đỏ mọng nước', 300, 1, 3, 15, 'tomato.png', 3, '["tomato_1.png", "tomato_2.png", "tomato_3.png"]'),
('Cà Rốt', 'Cà rốt ngọt ngon', 240, 2, 4, 12, 'carrot.png', 3, '["carrot_1.png", "carrot_2.png", "carrot_3.png"]'),
('Bắp', 'Bắp vàng tươi', 360, 1, 2, 20, 'corn.png', 3, '["corn_1.png", "corn_2.png", "corn_3.png"]'),
('Dâu Tây', 'Dâu tây đỏ căng', 180, 3, 6, 18, 'strawberry.png', 3, '["strawberry_1.png", "strawberry_2.png", "strawberry_3.png"]'),
('Dưa Hấu', 'Dưa hấu lớn', 420, 1, 1, 25, 'watermelon.png', 3, '["watermelon_1.png", "watermelon_2.png", "watermelon_3.png"]');

-- Sample Items (Seeds)
INSERT INTO items (name, description, type, sprite_file, sprite_frame_x, sprite_frame_y, buy_price, sell_price, rarity) VALUES
('Hạt Cà Chua', 'Hạt giống cà chua', 'seed', 'seeds.png', 0, 0, 50, 10, 'common'),
('Hạt Cà Rốt', 'Hạt giống cà rốt', 'seed', 'seeds.png', 32, 0, 40, 8, 'common'),
('Hạt Bắp', 'Hạt giống bắp', 'seed', 'seeds.png', 64, 0, 60, 12, 'common'),
('Hạt Dâu Tây', 'Hạt giống dâu tây', 'seed', 'seeds.png', 96, 0, 70, 15, 'uncommon'),
('Hạt Dưa Hấu', 'Hạt giống dưa hấu', 'seed', 'seeds.png', 128, 0, 100, 20, 'uncommon');

-- Sample Items (Tools & Materials)
INSERT INTO items (name, description, type, sprite_file, buy_price, sell_price, rarity) VALUES
('Phân Bón', 'Phân bón tăng tốc độ phát triển', 'fertilizer', 'fertilizer.png', 75, 30, 'common'),
('Nước Tưới', 'Nước tưới giảm thời gian phát triển', 'tool', 'water.png', 0, 0, 'common'),
('Chậu Cơ Bản', 'Chậu giảm 10% thời gian', 'pot', 'pot_basic.png', 200, 50, 'common'),
('Chậu Vàng', 'Chậu vàng giảm 20% thời gian', 'pot', 'pot_golden.png', 400, 100, 'uncommon'),
('Hoa Trang Trí', 'Trang trí vườn', 'decoration', 'flower.png', 100, 30, 'common');

-- Sample Level Definitions
INSERT INTO level_definitions (level, exp_required, exp_to_next, reward_coins, max_pots, max_decorations) VALUES
(1, 0, 100, 0, 5, 10),
(2, 100, 200, 100, 6, 12),
(3, 300, 350, 200, 7, 15),
(4, 650, 500, 300, 8, 18),
(5, 1150, 650, 500, 10, 20),
(6, 1800, 800, 750, 12, 25),
(7, 2600, 1000, 1000, 15, 30),
(8, 3600, 1200, 1500, 18, 35),
(9, 4800, 1500, 2000, 20, 40),
(10, 6300, 2000, 3000, 25, 50);

-- Sample World Tree Upgrades
INSERT INTO world_tree_upgrades (level, growth_time_reduction, upgrade_cost_coins, upgrade_cost_quantity) VALUES
(0, 0, 0, 0),
(1, 5, 500, 1),
(2, 10, 1000, 1),
(3, 15, 2000, 1),
(4, 20, 3000, 1),
(5, 25, 5000, 1);

-- Sample Pot Types
INSERT INTO pots (name, level, growth_time_reduction, sprite_file) VALUES
('Chậu Cơ Bản', 1, 10, 'pot_basic.png'),
('Chậu Bạc', 2, 15, 'pot_silver.png'),
('Chậu Vàng', 3, 20, 'pot_golden.png'),
('Chậu Kim Cương', 4, 25, 'pot_diamond.png');

-- Sample Pest Types
INSERT INTO pest_types (name, icon_file, color, damage_min, damage_max, spawn_rate) VALUES
('Sâu Xanh', 'pest_green.png', '#00FF00', 20, 40, 3),
('Bọ Đỏ', 'pest_red.png', '#FF0000', 30, 60, 5),
('Dế Đen', 'pest_black.png', '#000000', 40, 80, 4),
('Muỗi Vàng', 'pest_yellow.png', '#FFFF00', 25, 50, 6);

-- Sample Pest Drops
INSERT INTO pest_drops (pest_type_id, item_id, quantity_min, quantity_max, drop_chance) VALUES
(1, 3, 1, 2, 50),
(1, 4, 1, 1, 30),
(2, 3, 1, 3, 60),
(3, 4, 1, 2, 40),
(4, 5, 1, 1, 50);

-- Sample Quests
INSERT INTO quests (name, description, type, target_quantity, reward_coins, reward_exp, reward_item_id) VALUES
('Trồng Cây Đầu Tiên', 'Hãy trồng 1 cây', 'plant', 1, 50, 10, NULL),
('Thợ Nông Dân Nhí', 'Thu hoạch 5 cây', 'harvest', 5, 200, 50, NULL),
('Chiến Binh Sâu Bệnh', 'Tiêu diệt 10 sâu bệnh', 'kill_pest', 10, 300, 75, NULL),
('Chuyên Gia Tưới', 'Tưới 20 ô trồng', 'water', 20, 100, 25, NULL),
('Nông Dân Giỏi', 'Bón phân 10 lần', 'fertilize', 10, 150, 40, NULL);

-- Sample Expansion Packs
INSERT INTO expansions (name, width, height, cost_coins) VALUES
('Mở rộng nhỏ', 6, 6, 500),
('Mở rộng vừa', 8, 8, 1500),
('Mở rộng lớn', 10, 10, 3000),
('Mở rộng khổng lồ', 12, 12, 5000);

-- Sample Marketplace Config
INSERT INTO modules_config (module_name, enabled, config_data) VALUES
('marketplace', TRUE, '{"commission_rate": 10, "default_slots": 3, "slot_upgrade_cost": 100}'),
('shop', TRUE, '{"enable_recharge": true}'),
('quests', TRUE, '{"enable_daily_reset": true}'),
('pests', TRUE, '{"spawn_rate": 5, "damage_min": 20, "damage_max": 80}');
