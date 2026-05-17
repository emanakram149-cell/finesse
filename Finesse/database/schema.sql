-- Finesse Database Schema
-- NOTE: Do NOT include CREATE DATABASE / USE here.
-- Railway (and most hosts) already create the database for you.
-- Just run this file against the database Railway provides.

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categories
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT IGNORE INTO categories (name, slug) VALUES
('Dresses','dresses'),('Tops','tops'),('Bottoms','bottoms'),
('Jumpsuits & Rompers','jumpsuits'),('Outerwear','outerwear'),
('Handbags','handbags'),('Jewelry','jewelry'),('Shoes','shoes'),
('Accessories','accessories'),('Swimsuits','swimsuits'),('Sets','sets');

-- Closet items
CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  color VARCHAR(40) DEFAULT NULL,
  style_tag VARCHAR(60) DEFAULT NULL,
  image VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Saved outfits
CREATE TABLE IF NOT EXISTS outfits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  item_ids TEXT NOT NULL, -- JSON array of item ids
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Planner
CREATE TABLE IF NOT EXISTS planner (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  outfit_id INT DEFAULT NULL,
  date DATE NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (outfit_id) REFERENCES outfits(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Feedback
CREATE TABLE IF NOT EXISTS feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  message VARCHAR(1500) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admin
CREATE TABLE IF NOT EXISTS admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL DEFAULT 'Admin'
) ENGINE=InnoDB;

-- Default admin: email = admin@finesse.com  /  password = admin123
-- Change this password immediately after first login!
INSERT IGNORE INTO admin (email, password, name) VALUES
('admin@finesse.com', '$2y$10$sBIJwuP7TEBMvc1rDx0zWOzw49x5pATtyWxq1Zgdz1rLMcVge1ofy', 'Laleh Admin');
