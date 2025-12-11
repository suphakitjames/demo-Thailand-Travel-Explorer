-- =====================================================
-- ระบบค้นหาสถานที่ท่องเที่ยว อัจฉริยะ
-- Database Schema for Smart Thailand Tourism Search
-- =====================================================

-- สร้าง Database
CREATE DATABASE IF NOT EXISTS tourism_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE tourism_db;

-- =====================================================
-- ตาราง: users (ผู้ใช้งานทั้งหมด)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL,
    role ENUM('user', 'owner', 'admin') DEFAULT 'user' COMMENT 'user=นักท่องเที่ยว, owner=ผู้ประกอบการ, admin=ผู้ดูแล',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    email_verified_at DATETIME NULL,
    remember_token VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: provinces (จังหวัด)
-- =====================================================
CREATE TABLE IF NOT EXISTS provinces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_th VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    region ENUM('north', 'northeast', 'central', 'east', 'west', 'south') NOT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    INDEX idx_region (region)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: categories (หมวดหมู่สถานที่)
-- =====================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_th VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NULL COMMENT 'Icon class เช่น fa-mountain',
    description TEXT NULL,
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: moods (อารมณ์/บรรยากาศ)
-- =====================================================
CREATE TABLE IF NOT EXISTS moods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_th VARCHAR(50) NOT NULL,
    name_en VARCHAR(50) NOT NULL,
    icon VARCHAR(50) NULL,
    color VARCHAR(20) NULL COMMENT 'สีสำหรับ UI เช่น #FF5733',
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: budget_levels (ระดับงบประมาณ)
-- =====================================================
CREATE TABLE IF NOT EXISTS budget_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_th VARCHAR(50) NOT NULL,
    name_en VARCHAR(50) NOT NULL,
    min_price DECIMAL(10,2) NULL,
    max_price DECIMAL(10,2) NULL,
    icon VARCHAR(50) NULL
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: seasons (ฤดูกาลเที่ยว)
-- =====================================================
CREATE TABLE IF NOT EXISTS seasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_th VARCHAR(50) NOT NULL,
    name_en VARCHAR(50) NOT NULL,
    months VARCHAR(100) NULL COMMENT 'เดือนที่เหมาะ เช่น 11,12,1,2',
    icon VARCHAR(50) NULL,
    description TEXT NULL
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: places (สถานที่ท่องเที่ยว)
-- =====================================================
CREATE TABLE IF NOT EXISTS places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tat_place_id VARCHAR(50) NULL UNIQUE COMMENT 'ID จาก TAT API',
    name_th VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description_th TEXT NULL,
    description_en TEXT NULL,
    history TEXT NULL COMMENT 'ประวัติสถานที่',
    
    -- Location
    province_id INT NULL,
    district VARCHAR(100) NULL COMMENT 'อำเภอ',
    sub_district VARCHAR(100) NULL COMMENT 'ตำบล',
    address TEXT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    
    -- Category & Filter
    category_id INT NULL,
    budget_level_id INT NULL,
    
    -- Opening Hours
    opening_hours JSON NULL COMMENT 'เวลาเปิด-ปิด แต่ละวัน',
    is_open_24h TINYINT(1) DEFAULT 0,
    
    -- Pricing
    entrance_fee_thai DECIMAL(10,2) NULL COMMENT 'ค่าเข้าชม คนไทย',
    entrance_fee_foreigner DECIMAL(10,2) NULL COMMENT 'ค่าเข้าชม ต่างชาติ',
    is_free TINYINT(1) DEFAULT 0,
    
    -- Facilities (สิ่งอำนวยความสะดวก)
    has_parking TINYINT(1) DEFAULT 0,
    has_restroom TINYINT(1) DEFAULT 0,
    has_wheelchair_access TINYINT(1) DEFAULT 0,
    has_wifi TINYINT(1) DEFAULT 0,
    has_restaurant TINYINT(1) DEFAULT 0,
    has_accommodation TINYINT(1) DEFAULT 0,
    
    -- Contact
    phone VARCHAR(100) NULL,
    website VARCHAR(255) NULL,
    facebook VARCHAR(255) NULL,
    instagram VARCHAR(255) NULL,
    
    -- Media
    thumbnail VARCHAR(255) NULL COMMENT 'รูปปก',
    gallery JSON NULL COMMENT 'รูป Gallery',
    video_url VARCHAR(255) NULL,
    
    -- Statistics
    view_count INT DEFAULT 0,
    review_count INT DEFAULT 0,
    avg_rating DECIMAL(2,1) DEFAULT 0.0,
    avg_rating_cleanliness DECIMAL(2,1) DEFAULT 0.0,
    avg_rating_service DECIMAL(2,1) DEFAULT 0.0,
    avg_rating_value DECIMAL(2,1) DEFAULT 0.0,
    popularity_score DECIMAL(5,2) DEFAULT 0.00 COMMENT 'คะแนนความนิยม (คำนวณจากสูตร)',
    
    -- Ownership
    owner_id INT NULL COMMENT 'เจ้าของ (ถ้ามี)',
    is_verified TINYINT(1) DEFAULT 0 COMMENT 'ผ่านการตรวจสอบแล้ว',
    is_featured TINYINT(1) DEFAULT 0 COMMENT 'แนะนำพิเศษ',
    is_hidden_gem TINYINT(1) DEFAULT 0 COMMENT 'ที่เที่ยวลับ',
    status ENUM('pending', 'approved', 'rejected', 'draft') DEFAULT 'approved',
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (budget_level_id) REFERENCES budget_levels(id) ON DELETE SET NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_province (province_id),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_featured (is_featured),
    INDEX idx_popularity (popularity_score),
    INDEX idx_location (latitude, longitude),
    FULLTEXT idx_search (name_th, name_en, description_th)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: place_moods (สถานที่-อารมณ์ Many-to-Many)
-- =====================================================
CREATE TABLE IF NOT EXISTS place_moods (
    place_id INT NOT NULL,
    mood_id INT NOT NULL,
    PRIMARY KEY (place_id, mood_id),
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (mood_id) REFERENCES moods(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: place_seasons (สถานที่-ฤดูกาล Many-to-Many)
-- =====================================================
CREATE TABLE IF NOT EXISTS place_seasons (
    place_id INT NOT NULL,
    season_id INT NOT NULL,
    PRIMARY KEY (place_id, season_id),
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: place_images (รูปภาพสถานที่)
-- =====================================================
CREATE TABLE IF NOT EXISTS place_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    caption VARCHAR(255) NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    INDEX idx_place (place_id)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: reviews (รีวิว)
-- =====================================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Ratings (1-5)
    rating_overall TINYINT NOT NULL CHECK (rating_overall BETWEEN 1 AND 5),
    rating_cleanliness TINYINT NULL CHECK (rating_cleanliness BETWEEN 1 AND 5),
    rating_service TINYINT NULL CHECK (rating_service BETWEEN 1 AND 5),
    rating_value TINYINT NULL CHECK (rating_value BETWEEN 1 AND 5),
    
    -- Content
    title VARCHAR(255) NULL,
    content TEXT NOT NULL,
    visit_date DATE NULL COMMENT 'วันที่ไปเที่ยว',
    
    -- Stats
    helpful_count INT DEFAULT 0 COMMENT 'จำนวนคนกด Like',
    
    -- Moderation
    status ENUM('pending', 'approved', 'rejected', 'spam') DEFAULT 'approved',
    admin_note TEXT NULL,
    
    -- Owner Reply
    owner_reply TEXT NULL,
    owner_reply_at DATETIME NULL,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_place (place_id),
    INDEX idx_user (user_id),
    INDEX idx_rating (rating_overall),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: review_images (รูปภาพในรีวิว)
-- =====================================================
CREATE TABLE IF NOT EXISTS review_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    caption VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    INDEX idx_review (review_id)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: review_likes (กด Like รีวิว)
-- =====================================================
CREATE TABLE IF NOT EXISTS review_likes (
    user_id INT NOT NULL,
    review_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, review_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: trips (ทริปที่วางแผน)
-- =====================================================
CREATE TABLE IF NOT EXISTS trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    cover_image VARCHAR(255) NULL,
    is_public TINYINT(1) DEFAULT 0 COMMENT 'แชร์สาธารณะ',
    total_distance DECIMAL(10,2) NULL COMMENT 'ระยะทางรวม (กม.)',
    total_duration INT NULL COMMENT 'เวลาเดินทางรวม (นาที)',
    status ENUM('planning', 'ongoing', 'completed', 'cancelled') DEFAULT 'planning',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: trip_items (สถานที่ในทริป)
-- =====================================================
CREATE TABLE IF NOT EXISTS trip_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    place_id INT NOT NULL,
    day_number INT DEFAULT 1 COMMENT 'วันที่เท่าไหร่ของทริป',
    sort_order INT DEFAULT 0 COMMENT 'ลำดับในวันนั้น',
    start_time TIME NULL COMMENT 'เวลาที่จะไปถึง',
    end_time TIME NULL COMMENT 'เวลาที่จะออก',
    note TEXT NULL COMMENT 'บันทึกเพิ่มเติม',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    INDEX idx_trip (trip_id),
    INDEX idx_day (trip_id, day_number)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: favorites (รายการโปรด)
-- =====================================================
CREATE TABLE IF NOT EXISTS favorites (
    user_id INT NOT NULL,
    place_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, place_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: search_history (ประวัติการค้นหา)
-- =====================================================
CREATE TABLE IF NOT EXISTS search_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    search_query VARCHAR(255) NOT NULL,
    filters JSON NULL COMMENT 'ตัวกรองที่ใช้',
    results_count INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: comments (ความคิดเห็น)
-- =====================================================
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT NULL COMMENT 'ตอบกลับ Comment ไหน',
    content TEXT NOT NULL,
    status ENUM('active', 'hidden', 'deleted') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_place (place_id),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: activity_logs (บันทึกกิจกรรม - Security)
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'login, logout, create, update, delete',
    target_type VARCHAR(50) NULL COMMENT 'place, review, user',
    target_id INT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =====================================================
-- ตาราง: settings (ตั้งค่าระบบ)
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description VARCHAR(255) NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
