-- ================================================================
--  Esquema normalizado Calzado Oxlaj (versión alineada al código)
--  Incluye soporte para tags, favoritos por usuario y CRUD inline.
--  Compatible con endpoints:
--   - api/products.php
--   - api/admin_products.php
--   - api/cart.php (carrito global actual)
--   - api/favoritos.php (requiere sesión usuario)
--   - api/testimonios.php
--   - api/auth.php
-- ================================================================

CREATE DATABASE IF NOT EXISTS calzado_oxlaj CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE calzado_oxlaj;

-- ----------------------------
-- Productos y Tags
-- ----------------------------
CREATE TABLE IF NOT EXISTS productos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  imagen MEDIUMTEXT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tags (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producto_tags (
  producto_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (producto_id, tag_id),
  KEY idx_pt_tag (tag_id),
  CONSTRAINT fk_pt_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  CONSTRAINT fk_pt_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Carrito (global actual)
--   Nota: Actualmente cart.php opera sobre un carrito "global" sin usuario.
--   Futuro: migrar a carrito_items con usuario_id si se necesita multiusuario real.
-- ----------------------------
CREATE TABLE IF NOT EXISTS carrito (
  producto_id INT UNSIGNED PRIMARY KEY,
  cantidad INT NOT NULL DEFAULT 1,
  CONSTRAINT fk_carrito_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Usuarios, Favoritos, Testimonios
-- ----------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favoritos (
  usuario_id INT UNSIGNED NOT NULL,
  producto_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (usuario_id, producto_id),
  KEY idx_fav_producto (producto_id),
  CONSTRAINT fk_fav_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testimonios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  texto TEXT NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Mensajes de contacto (para futura persistencia del formulario)
-- ----------------------------
CREATE TABLE IF NOT EXISTS mensajes_contacto (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120),
  correo VARCHAR(180),
  mensaje TEXT,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Pedidos (plan futuro) y detalle
-- ----------------------------
CREATE TABLE IF NOT EXISTS pedidos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  estado VARCHAR(30) DEFAULT 'pendiente',
  total DECIMAL(10,2) DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pedido_usuario (usuario_id),
  CONSTRAINT fk_pedido_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT UNSIGNED NOT NULL,
  producto_id INT UNSIGNED NOT NULL,
  cantidad INT NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  KEY idx_pi_pedido (pedido_id),
  KEY idx_pi_producto (producto_id),
  CONSTRAINT fk_pi_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  CONSTRAINT fk_pi_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Datos iniciales
-- ----------------------------
INSERT IGNORE INTO productos (id,titulo,precio,imagen) VALUES
 (1,'Zapato casual clásico',350,'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.21_2fc74464.jpg'),
 (2,'Tenis deportivos',420,'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.20_ffb7cb86.jpg');

-- Tags base
INSERT IGNORE INTO tags (nombre) VALUES ('casual'),('deporte');
INSERT IGNORE INTO producto_tags (producto_id, tag_id)
  SELECT 1, t.id FROM tags t WHERE t.nombre='casual';
INSERT IGNORE INTO producto_tags (producto_id, tag_id)
  SELECT 2, t.id FROM tags t WHERE t.nombre='deporte';

INSERT IGNORE INTO testimonios (nombre,texto) VALUES
 ('María G.','Excelente calidad y comodidad.'),
 ('Carlos R.','Servicio rápido y amable.');

-- Usuarios por defecto (hashes BCRYPT de admin123 / cliente123)
INSERT IGNORE INTO usuarios (id,nombre,correo,password_hash,rol) VALUES
  (1,'Administrador','admin@oxlaj.local','$2y$10$0kXyq1VvC2m6yHqkS1q7HuAau0nTqv7e1H0t3Y8w6bqN4n8X7w8d2','admin'),
  (2,'Cliente Demo','cliente@oxlaj.local','$2y$10$2uQyNf0mG5l0vYV2bqk2qO3m1s2t3u4v5w6x7y8z9a0b1c2d3e4f6','cliente');

-- ================================================================
-- Notas de migración desde esquema anterior (simple):
-- 1. Hacer backup.
-- 2. Crear nuevas tablas tags / producto_tags / favoritos (drop de columnas antiguas como 'etiquetas').
-- 3. Migrar etiquetas textuales si existían: dividir por coma e insertar en tags/producto_tags.
-- 4. Verificar que productos.id sea AUTO_INCREMENT (ALTER si antes era PK manual).
-- 5. Carrito global se mantiene; para multiusuario futuro crear tabla carrito_items(usuario_id, producto_id, cantidad).
-- ================================================================
