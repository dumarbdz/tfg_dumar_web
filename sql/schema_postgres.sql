-- Esquema PostgreSQL para Neon
-- Equivalente a schema.sql pero compatible con PostgreSQL 17

DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_stock;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id SERIAL PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  saved_name    VARCHAR(200) DEFAULT NULL,
  saved_line1   VARCHAR(255) DEFAULT NULL,
  saved_postal  VARCHAR(32)  DEFAULT NULL,
  saved_city    VARCHAR(120) DEFAULT NULL,
  saved_country VARCHAR(120) DEFAULT NULL,
  is_admin BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at TIMESTAMP NOT NULL,
  used_at TIMESTAMP DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_pr_user ON password_resets(user_id);
CREATE INDEX idx_pr_expires ON password_resets(expires_at);

CREATE TABLE products (
  id SERIAL PRIMARY KEY,
  brand VARCHAR(50) NOT NULL,
  model VARCHAR(150) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE
);
CREATE INDEX idx_products_brand ON products(brand);

CREATE TABLE product_stock (
  id SERIAL PRIMARY KEY,
  product_id INTEGER NOT NULL,
  size VARCHAR(10) NOT NULL,
  quantity INTEGER NOT NULL DEFAULT 0,
  UNIQUE (product_id, size),
  CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE orders (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'completed',
  shipping_name VARCHAR(200) NOT NULL DEFAULT '',
  shipping_line1 VARCHAR(255) NOT NULL DEFAULT '',
  shipping_postal VARCHAR(32) NOT NULL DEFAULT '',
  shipping_city VARCHAR(120) NOT NULL DEFAULT '',
  shipping_country VARCHAR(120) NOT NULL DEFAULT '',
  payment_method VARCHAR(50) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
  id SERIAL PRIMARY KEY,
  order_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  size VARCHAR(10) NOT NULL,
  quantity INTEGER NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Tabla para sesiones PHP en entorno serverless (Vercel)
CREATE TABLE sessions (
  id TEXT PRIMARY KEY,
  data TEXT NOT NULL DEFAULT '',
  last_activity BIGINT NOT NULL
);
