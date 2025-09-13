-- Digital Wedding Invitation Platform Database Schema

CREATE DATABASE wedding_invitation_platform;
USE wedding_invitation_platform;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subscription_plan ENUM('free', 'premium', 'business') DEFAULT 'free',
    subscription_expires DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Wedding invitations table
CREATE TABLE invitations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    groom_name VARCHAR(100) NOT NULL,
    bride_name VARCHAR(100) NOT NULL,
    wedding_date DATE NOT NULL,
    wedding_time TIME NOT NULL,
    venue_name VARCHAR(200),
    venue_address TEXT,
    venue_maps_link TEXT,
    theme_id INT,
    background_music VARCHAR(255),
    cover_image VARCHAR(255),
    story TEXT,
    live_streaming_link VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    slug VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Themes table
CREATE TABLE themes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    preview_image VARCHAR(255),
    css_file VARCHAR(255),
    is_premium BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Gallery table
CREATE TABLE gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invitation_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    caption TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
);

-- Guests table
CREATE TABLE guests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invitation_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    guest_type ENUM('family', 'friend', 'colleague', 'other') DEFAULT 'friend',
    invitation_sent BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
);

-- RSVP responses table
CREATE TABLE rsvp_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invitation_id INT NOT NULL,
    guest_name VARCHAR(100) NOT NULL,
    attendance ENUM('yes', 'no', 'maybe') NOT NULL,
    guest_count INT DEFAULT 1,
    message TEXT,
    phone VARCHAR(20),
    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
);

-- Guest messages/wishes table
CREATE TABLE guest_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invitation_id INT NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
);

-- Digital gifts table
CREATE TABLE digital_gifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invitation_id INT NOT NULL,
    account_type ENUM('bank', 'e-wallet', 'crypto') NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(100) NOT NULL,
    qr_code_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
);

-- Gift transactions table
CREATE TABLE gift_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invitation_id INT NOT NULL,
    gift_id INT NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2),
    message TEXT,
    transaction_proof VARCHAR(255),
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE,
    FOREIGN KEY (gift_id) REFERENCES digital_gifts(id) ON DELETE CASCADE
);

-- Insert sample themes
INSERT INTO themes (name, preview_image, css_file, is_premium) VALUES
('Classic Elegant', '/assets/themes/classic/preview.jpg', 'classic.css', FALSE),
('Modern Minimalist', '/assets/themes/modern/preview.jpg', 'modern.css', FALSE),
('Romantic Garden', '/assets/themes/garden/preview.jpg', 'garden.css', TRUE),
('Luxury Gold', '/assets/themes/luxury/preview.jpg', 'luxury.css', TRUE),
('Traditional Indonesian', '/assets/themes/traditional/preview.jpg', 'traditional.css', TRUE);

-- Insert admin user
INSERT INTO users (username, email, password, full_name, subscription_plan) VALUES
('admin', 'admin@wevitation.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'business');