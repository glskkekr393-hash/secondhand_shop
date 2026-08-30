CREATE DATABASE IF NOT EXISTS secondhand_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE secondhand_shop;

CREATE TABLE users(
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(150) UNIQUE NOT NULL,
 password VARCHAR(255) NOT NULL,
 role ENUM('user','admin') DEFAULT 'user',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories(
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL
);

CREATE TABLE products(
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 category_id INT NOT NULL,
 name VARCHAR(200) NOT NULL,
 price DECIMAL(10,2) NOT NULL,
 item_condition VARCHAR(50) NOT NULL,
 description TEXT,
 image VARCHAR(255),
 status ENUM('pending','approved','sold','rejected') DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(category_id) REFERENCES categories(id)
);

CREATE TABLE favorites(
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 product_id INT NOT NULL,
 UNIQUE KEY fav(user_id,product_id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
);

INSERT INTO categories(name) VALUES
('โทรศัพท์มือถือ'),('คอมพิวเตอร์'),('เกมและอุปกรณ์'),('เครื่องเสียง'),('เสื้อผ้า'),('กล้อง'),('ของใช้ทั่วไป');

INSERT INTO users(name,email,password,role) VALUES
('ผู้ดูแลระบบ','admin@example.com',MD5('admin123'),'admin');
