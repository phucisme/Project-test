# Cloud Garden - Documentation

## 📖 Tài Liệu Hệ Thống

### Kiến Trúc Game

#### Backend (PHP)
- **Authentication**: Xử lý đăng nhập, đăng ký, phiên làm việc
- **Game API**: Xử lý tất cả hành động của người chơi
- **Database**: MySQL lưu trữ dữ liệu
- **Classes**: Các lớp xử lý logic game

#### Frontend (JavaScript/CSS)
- **API Client**: Giao tiếp với backend
- **Game Logic**: Quản lý trạng thái game
- **UI Components**: Giao diện người dùng
- **Service Worker**: Caching & offline support

### Luồng Chơi Chính

```
1. Tạo Tài Khoản / Đăng Nhập
   ↓
2. Khởi Tạo Vườn (4x4, 5 hạt giống, 1000 xu)
   ↓
3. Trồng Cây
   ├─ Chọn hạt giống
   ├─ Tiêu hao hạt giống
   └─ Tính toán thời gian phát triển
   ↓
4. Chăm Sóc Cây (Tưới, Bón Phân)
   ├─ Tưới: -10% thời gian (Max 3 lần)
   ├─ Bón Phân: -20% ngay lập tức (Max 2 lần)
   └─ Cooldown 5-10 phút
   ↓
5. Thu Hoạch
   ├─ Nhận nông sản (random 1-3)
   ├─ Nhận coin (10 xu/sản phẩm)
   └─ Nhận EXP (5-20 điểm)
   ↓
6. Nâng Cấp / Mở Khóa
   ├─ Tăng level → Nhận thưởng
   └─ Mở khóa tính năng mới
```

### Hệ Thống Item

#### Loại Item
1. **seed** - Hạt giống
2. **crop** - Sản phẩm nông sản
3. **tool** - Dụng cụ (phân bón, etc)
4. **decoration** - Trang trí
5. **fertilizer** - Phân bón
6. **cloud** - Mây (thay đổi giao diện tầng)
7. **pot** - Chậu trồng
8. **machine_part** - Phụ tùng máy
9. **other** - Khác

#### Sprite Frame System
- Mỗi item có sprite_file, sprite_frame_x, sprite_frame_y
- Tiết kiệm tài nguyên bằng sprite sheet
- Hỗ trợ animation qua frame khác nhau

### Hệ Thống Cấp Độ

```
Level 1: EXP: 0, Max Pots: 5, Max Decor: 10
Level 2: EXP: 100, Max Pots: 6, Max Decor: 12
Level 3: EXP: 300, Max Pots: 7, Max Decor: 15
...
Level 10: EXP: 6300, Max Pots: 25, Max Decor: 50
```

### Hệ Thống Sâu Bệnh

1. **Spawn**: Ngẫu nhiên 5% mỗi tick khi cây đang phát triển
2. **Damage**: 20-80 giây (tuỳ loại sâu)
3. **Kill**: Nhận thưởng 25 xu + 5 EXP + Random item
4. **Drop Rate**: Mỗi loại sâu có bảng loot riêng

### Hệ Thống Chế Tạo

1. **Recipe**: Yêu cầu nguyên liệu → Sản phẩm
2. **Production Time**: Tuỳ công thức
3. **Machines**: Tối đa 4 máy (tier 1-4)
4. **Slots**: Nâng cấp máy → Tăng slot
5. **Rewards**: EXP khi hoàn thành

### Hệ Thống Chợ (Marketplace)

1. **Listing**: Bán item ≤ 3 slots mặc định
2. **Commission**: 10% phí giao dịch
3. **Upgrade**: +1 slot = 100 xu
4. **Duration**: Listing mãi mãi (hoặc hủy)
5. **Cancel**: Trả item vào túi

### Hệ Thống Bạn Bè

1. **Add Friend**: Yêu cầu kết bạn (pending)
2. **Accept**: Chuyển trạng thái accepted
3. **View Garden**: Chỉ xem, không tương tác
4. **Remove**: Xoá kết bạn
5. **Suggest**: Gợi ý người chơi random

### Hệ Thống Nhiệm Vụ

#### Quest Types
- harvest (Thu hoạch X lần)
- kill_pest (Tiêu diệt X sâu)
- water (Tưới X lần)
- fertilize (Bón phân X lần)
- plant (Trồng X cây)
- unlock_machine (Mở khóa máy)
- expand_garden (Mở rộng vườn)
- buy_item (Mua X item)
- level_up (Lên cấp)
- complete_recipe (Chế tạo X)

#### Rewards
- Coins: 50-500 xu
- EXP: 10-100 điểm
- Items: Random item

#### Delivery Quest
- Yêu cầu giao X item loại Y
- Cooldown 24 giờ
- Phần thưởng: 50 xu/item + 10 EXP/item

### Hệ Thống Quản Trị

#### Dashboard
- Tổng số người chơi
- Hoạt động 24h
- Tổng xu trong hệ
- Nhiệm vụ hoàn thành

#### Quản Lý
- Cây trồng: Tạo, sửa, xoá
- Vật phẩm: Cấu hình sprite, giá
- Gói mở rộng: Kích thước, giá
- Cấp độ: EXP, phần thưởng
- Máy móc: Tier, unlock cost
- Công thức: Nguyên liệu, thời gian
- Sâu bệnh: Icon, color, damage
- Cài đặt: Module config

### Performance Optimization

1. **Database**
   - Indexes trên các trường hay sử dụng
   - Query optimization
   - Connection pooling

2. **Frontend**
   - Lazy loading ảnh
   - CSS sprites
   - Minification
   - Compression (gzip)

3. **Caching**
   - Service Worker cache
   - Browser cache headers
   - CDN cho static assets

4. **API**
   - Response gzip
   - Pagination
   - Rate limiting

### Security

1. **Authentication**
   - Password hashing (bcrypt)
   - Session management
   - CSRF protection

2. **Database**
   - Prepared statements
   - Input validation
   - SQL injection prevention

3. **API**
   - Input sanitization
   - Rate limiting
   - CORS headers

4. **Frontend**
   - XSS prevention
   - Content Security Policy
   - Security headers

### Mở Rộng & Tùy Chỉnh

#### Thêm Cây Trồng
```php
INSERT INTO crops (name, growth_time, ...) VALUES (...)
```

#### Thêm Vật Phẩm
```php
INSERT INTO items (name, type, sprite_file, ...) VALUES (...)
```

#### Thêm Quest
```php
INSERT INTO quests (name, type, target_quantity, ...) VALUES (...)
```

### Debugging

#### Enable Debug Mode
```php
define('DEBUG_MODE', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### Check Logs
```bash
tail -f logs/game.log
tail -f logs/error.log
```

#### Database Check
```sql
SELECT * FROM player_logs WHERE user_id = X ORDER BY created_at DESC;
SELECT * FROM wallet_history WHERE user_id = X ORDER BY created_at DESC;
```

### API Response Format

#### Success
```json
{
  "success": true,
  "data": {...}
}
```

#### Error
```json
{
  "success": false,
  "error": "Error message"
}
```

### Constants & Configuration

#### Game Settings
- INITIAL_COINS = 1000
- INITIAL_SEEDS = 5
- INITIAL_LEVEL = 1
- WATERING_COOLDOWN = 300 (5 min)
- FERTILIZE_COOLDOWN = 600 (10 min)
- PEST_SPAWN_RATE = 5 (%)
- MARKETPLACE_COMMISSION_RATE = 10 (%)

#### Thay Đổi Cài Đặt
Sửa trong `src/config/Config.php`

---

**Cần thêm thông tin? Liên hệ support@cloudgarden.game**
