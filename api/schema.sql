CREATE DATABASE IF NOT EXISTS calzado_oxlaj CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE calzado_oxlaj;

-- Tablas en español
CREATE TABLE IF NOT EXISTS productos (
  id INT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  imagen VARCHAR(255),
  etiquetas VARCHAR(255),
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS producto_categorias (
  producto_id INT NOT NULL,
  categoria_id INT NOT NULL,
  PRIMARY KEY(producto_id, categoria_id)
);

CREATE TABLE IF NOT EXISTS carrito (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL DEFAULT 1,
  UNIQUE KEY uniq_prod (producto_id)
);

CREATE TABLE IF NOT EXISTS favoritos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  UNIQUE KEY uniq_fav (producto_id)
);

CREATE TABLE IF NOT EXISTS testimonios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  texto TEXT NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Usuarios para autenticación y roles
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(180) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mensajes_contacto (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120),
  correo VARCHAR(180),
  mensaje TEXT,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  estado VARCHAR(30) DEFAULT 'pendiente',
  total DECIMAL(10,2) DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pedido_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  precio DECIMAL(10,2) NOT NULL
);

-- Datos iniciales mínimos
INSERT IGNORE INTO productos (id,titulo,precio,imagen,etiquetas) VALUES
 (1,'Zapato casual clásico',350,'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.21_2fc74464.jpg','casual'),
 (2,'Tenis deportivos',420,'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.20_ffb7cb86.jpg','deporte');

INSERT IGNORE INTO testimonios (nombre,texto) VALUES
 ('María G.','Excelente calidad y comodidad.'),
 ('Carlos R.','Servicio rápido y amable.');

-- Usuarios por defecto
INSERT IGNORE INTO usuarios (id,nombre,correo,password_hash,rol) VALUES
  (1,'Administrador','admin@oxlaj.local','$2y$10$0kXyq1VvC2m6yHqkS1q7HuAau0nTqv7e1H0t3Y8w6bqN4n8X7w8d2','admin'),
  (2,'Cliente Demo','cliente@oxlaj.local','$2y$10$2uQyNf0mG5l0vYV2bqk2qO3m1s2t3u4v5w6x7y8z9a0b1c2d3e4f6','cliente');

-- Las contraseñas hash corresponden a:
-- admin@oxlaj.local / admin123
-- cliente@oxlaj.local / cliente123
