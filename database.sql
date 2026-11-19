CREATE DATABASE IF NOT EXISTS hardware_inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hardware_inventory_db;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 user_id INT AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(50) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','sales','storekeeper','procurement','manager') NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
 category_id INT AUTO_INCREMENT PRIMARY KEY,
 category_name VARCHAR(100) NOT NULL UNIQUE,
 description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
 supplier_id INT AUTO_INCREMENT PRIMARY KEY,
 supplier_name VARCHAR(150) NOT NULL,
 phone VARCHAR(30),
 address VARCHAR(255),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
 product_id INT AUTO_INCREMENT PRIMARY KEY,
 category_id INT NULL,
 product_code VARCHAR(50) NOT NULL UNIQUE,
 product_name VARCHAR(150) NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
 quantity_in_stock INT NOT NULL DEFAULT 0,
 reorder_level INT NOT NULL DEFAULT 5,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_product_category FOREIGN KEY(category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE purchases (
 purchase_id INT AUTO_INCREMENT PRIMARY KEY,
 supplier_id INT NOT NULL,
 user_id INT NOT NULL,
 purchase_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 total_amount DECIMAL(12,2) NOT NULL,
 CONSTRAINT fk_purchase_supplier FOREIGN KEY(supplier_id) REFERENCES suppliers(supplier_id),
 CONSTRAINT fk_purchase_user FOREIGN KEY(user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
 purchase_item_id INT AUTO_INCREMENT PRIMARY KEY,
 purchase_id INT NOT NULL,
 product_id INT NOT NULL,
 quantity INT NOT NULL,
 unit_cost DECIMAL(12,2) NOT NULL,
 CONSTRAINT fk_pi_purchase FOREIGN KEY(purchase_id) REFERENCES purchases(purchase_id),
 CONSTRAINT fk_pi_product FOREIGN KEY(product_id) REFERENCES products(product_id)
) ENGINE=InnoDB;

CREATE TABLE sales (
 sale_id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 total_amount DECIMAL(12,2) NOT NULL,
 payment_method VARCHAR(30) NOT NULL DEFAULT 'Cash',
 CONSTRAINT fk_sale_user FOREIGN KEY(user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
 sale_item_id INT AUTO_INCREMENT PRIMARY KEY,
 sale_id INT NOT NULL,
 product_id INT NOT NULL,
 quantity INT NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL,
 CONSTRAINT fk_si_sale FOREIGN KEY(sale_id) REFERENCES sales(sale_id),
 CONSTRAINT fk_si_product FOREIGN KEY(product_id) REFERENCES products(product_id)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
 movement_id INT AUTO_INCREMENT PRIMARY KEY,
 product_id INT NOT NULL,
 user_id INT NOT NULL,
 movement_type ENUM('IN','OUT','ADJUSTMENT') NOT NULL,
 quantity INT NOT NULL,
 movement_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 notes VARCHAR(255),
 CONSTRAINT fk_sm_product FOREIGN KEY(product_id) REFERENCES products(product_id),
 CONSTRAINT fk_sm_user FOREIGN KEY(user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

INSERT INTO categories(category_name,description) VALUES
('Cement','Cement and binding materials'),('Roofing','Roofing sheets and accessories'),
('Plumbing','Pipes and plumbing accessories'),('Electrical','Electrical supplies');
INSERT INTO suppliers(supplier_name,phone,address) VALUES
('Sample Building Supplies','0700000000','Uganda');
