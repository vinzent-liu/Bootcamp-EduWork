-- SQL to create database and sample tables/data for e_commerce
CREATE DATABASE IF NOT EXISTS e_commerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE e_commerce;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_produk VARCHAR(255) NOT NULL,
  harga DECIMAL(10,2) NOT NULL DEFAULT 0,
  deskripsi TEXT,
  stok INT NOT NULL DEFAULT 0,
  kategori VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample data
INSERT INTO products (nama_produk, harga, deskripsi, stok, kategori) VALUES
('Kaos Polos Putih', 75000, 'Kaos katun nyaman untuk sehari-hari', 20, 'Pakaian'),
('Sneakers Sport', 350000, 'Sepatu olahraga ringan dan stylish', 10, 'Sepatu'),
('Tote Bag Kanvas', 120000, 'Tas kanvas serbaguna', 15, 'Aksesoris');
