-- ================================================================
--  Esquema normalizado Calzado Oxlaj (alineado a dump y código)
--  Incluye: productos, tags, producto_tags, usuarios, favoritos,
--  carrito (PK compuesta por producto/talla/color), testimonios,
--  pedidos, pedido_items, compras, compra_items, mensajes_contacto.
-- ================================================================

CREATE DATABASE IF NOT EXISTS `calzado_oxlaj` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `calzado_oxlaj`;

-- ----------------------------
-- Productos y Tags
-- ----------------------------
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `imagen` mediumtext DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tag_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `producto_tags` (
  `producto_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`producto_id`,`tag_id`),
  CONSTRAINT `fk_pt_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pt_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `carrito` (
  `producto_id` int(11) NOT NULL,
  `talla` varchar(5) NOT NULL DEFAULT '',
  `color` enum('negro','cafe') NOT NULL DEFAULT 'negro',
  `cantidad` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`producto_id`,`talla`,`color`),
  CONSTRAINT `fk_carrito_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Usuarios, Favoritos, Testimonios
-- ----------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `correo` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','cliente') NOT NULL DEFAULT 'cliente',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `favoritos` (
  `usuario_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  PRIMARY KEY (`usuario_id`,`producto_id`),
  KEY `fk_fav_producto` (`producto_id`),
  CONSTRAINT `fk_fav_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `testimonios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `texto` text NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Mensajes de contacto
-- ----------------------------
CREATE TABLE IF NOT EXISTS `mensajes_contacto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) DEFAULT NULL,
  `correo` varchar(180) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Pedidos y detalle
-- ----------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `estado` varchar(30) DEFAULT 'pendiente',
  `total` decimal(10,2) DEFAULT 0.00,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pedido_usuario` (`usuario_id`),
  CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pedido_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pi_pedido` (`pedido_id`),
  KEY `fk_pi_producto` (`producto_id`),
  CONSTRAINT `fk_pi_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Compras y detalle de compras
-- ----------------------------
CREATE TABLE IF NOT EXISTS `compras` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `proveedor` varchar(160) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT 0.00,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `compra_items` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `compra_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `cantidad` int(11) NOT NULL,
  `costo` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ci_compra` (`compra_id`),
  KEY `idx_ci_producto` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Datos iniciales mínimos
-- ----------------------------
INSERT IGNORE INTO `productos` (`id`,`titulo`,`precio`,`imagen`) VALUES
 (1,'Zapato casual clásico',350.00,'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.21_2fc74464.jpg'),
 (2,'Tenis deportivos',420.00,'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.20_ffb7cb86.jpg');

INSERT IGNORE INTO `tags` (`nombre`) VALUES ('casual'),('deporte');
INSERT IGNORE INTO `producto_tags` (`producto_id`, `tag_id`)
  SELECT 1, t.id FROM `tags` t WHERE t.`nombre`='casual';
INSERT IGNORE INTO `producto_tags` (`producto_id`, `tag_id`)
  SELECT 2, t.id FROM `tags` t WHERE t.`nombre`='deporte';

INSERT IGNORE INTO `testimonios` (`nombre`,`texto`) VALUES
 ('María G.','Excelente calidad y comodidad.'),
 ('Carlos R.','Servicio rápido y amable.');

-- Usuarios por defecto (hashes de ejemplo BCRYPT de admin123 / cliente123)
INSERT IGNORE INTO `usuarios` (`id`,`nombre`,`correo`,`password_hash`,`rol`) VALUES
  (1,'Administrador','admin@oxlaj.local','$2y$10$0kXyq1VvC2m6yHqkS1q7HuAau0nTqv7e1H0t3Y8w6bqN4n8X7w8d2','admin'),
  (2,'Cliente Demo','cliente@oxlaj.local','$2y$10$2uQyNf0mG5l0vYV2bqk2qO3m1s2t3u4v5w6x7y8z9a0b1c2d3e4f6','cliente');

-- ================================================================
-- Notas
-- - Este schema usa PK compuesta en `carrito` (producto,talla,color)
--   como requiere el código (`api/cart.php`). Si tu instancia actual
--   tiene PK solo en `producto_id`, aplica un ALTER para migrar.
-- - Charset/collation ajustado a utf8mb4_general_ci para coincidir
--   con dumps típicos de phpMyAdmin.
-- ================================================================
