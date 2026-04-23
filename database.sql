CREATE DATABASE IF NOT EXISTS scc_db;
USE scc_db;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    level INT NOT NULL
);

INSERT INTO roles (name, level) VALUES
('SuperAdmin', 100),
('Admin', 90),
('Accounting', 80),
('Management', 70),
('Technical', 60),
('EventManager', 50),
('SocialMediaManager', 40),
('Member', 10)
ON DUPLICATE KEY UPDATE level=VALUES(level);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    session VARCHAR(50) DEFAULT NULL,
    blood_group VARCHAR(10) DEFAULT NULL,
    profile_pic VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expire DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    start_date DATETIME NOT NULL,
    end_date DATETIME DEFAULT NULL,
    type ENUM('Free', 'Paid') DEFAULT 'Free',
    fee DECIMAL(10,2) DEFAULT 0,
    status ENUM('Upcoming', 'Ongoing', 'Completed') DEFAULT 'Upcoming',
    image VARCHAR(255) DEFAULT NULL,
    tags VARCHAR(500) DEFAULT NULL,
    meta_description VARCHAR(300) DEFAULT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('Bkash', 'Nagad') NOT NULL,
    sender_number VARCHAR(20) NOT NULL,
    trx_id VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_id) REFERENCES events(id)
);

CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    subtitle VARCHAR(100),
    position VARCHAR(100),
    session VARCHAR(50),
    email VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_type VARCHAR(100),
    module VARCHAR(50),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    visibility ENUM('Public', 'Members') DEFAULT 'Public',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id)
);

CREATE TABLE IF NOT EXISTS post_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('site_name', 'SCC Computer Club'),
('registration_fee', '0'),
('contact_email', 'no-reply@miniearn.site'),
('smtp_host', 'mail.miniearn.site'),
('smtp_port', '465'),
('smtp_user', 'no-reply@miniearn.site'),
('smtp_pass', ''),
('smtp_from_name', 'SCC Computer Club'),
('smtp_from_email', 'no-reply@miniearn.site'),
('smtp_secure', 'ssl');
