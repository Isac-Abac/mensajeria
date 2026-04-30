-- ============================================================
-- Creacion de base de datos y seleccion de esquema
-- ============================================================
CREATE DATABASE IF NOT EXISTS usuarios CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE usuarios;

-- ============================================================
-- Tabla de usuarios
-- Guarda informacion de cuenta y foto de perfil
-- ============================================================
CREATE TABLE IF NOT EXISTS usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    correo VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255) NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Si tu tabla ya existia sin foto_perfil, ejecuta:
-- ALTER TABLE usuario ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER password_hash;

-- ============================================================
-- Tabla de mensajes
-- Incluye estado de visto para control de checks y bloqueos
-- ============================================================
CREATE TABLE IF NOT EXISTS mensaje (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remitente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    visto TINYINT(1) NOT NULL DEFAULT 0,
    visto_en TIMESTAMP NULL DEFAULT NULL,
    enviado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mensaje_remitente FOREIGN KEY (remitente_id) REFERENCES usuario(id) ON DELETE CASCADE,
    CONSTRAINT fk_mensaje_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuario(id) ON DELETE CASCADE
);

-- Si tu tabla mensaje ya existia sin estas columnas, ejecuta:
-- ALTER TABLE mensaje ADD COLUMN visto TINYINT(1) NOT NULL DEFAULT 0 AFTER contenido;
-- ALTER TABLE mensaje ADD COLUMN visto_en TIMESTAMP NULL DEFAULT NULL AFTER visto;

-- ============================================================
-- Indice de apoyo para consultas de conversacion
-- ============================================================
DROP INDEX IF EXISTS idx_mensaje_chat ON mensaje;
CREATE INDEX idx_mensaje_chat ON mensaje(remitente_id, destinatario_id, enviado_en);

-- ============================================================
-- Tabla de amistades
-- Cada registro representa una amistad aprobada
-- ============================================================
CREATE TABLE IF NOT EXISTS amistad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    amigo_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_amistad_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE,
    CONSTRAINT fk_amistad_amigo FOREIGN KEY (amigo_id) REFERENCES usuario(id) ON DELETE CASCADE,
    UNIQUE KEY uq_amistad (usuario_id, amigo_id)
);

-- ============================================================
-- Tabla de solicitudes de amistad
-- Guarda solicitudes pendientes antes de aceptar
-- ============================================================
CREATE TABLE IF NOT EXISTS solicitud_amistad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitante_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    estado ENUM('pendiente', 'aceptada', 'rechazada') NOT NULL DEFAULT 'pendiente',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitud_solicitante FOREIGN KEY (solicitante_id) REFERENCES usuario(id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitud_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuario(id) ON DELETE CASCADE,
    UNIQUE KEY uq_solicitud (solicitante_id, destinatario_id)
);

-- ============================================================
-- Tabla de publicaciones
-- Guarda publicaciones estilo Instagram
-- ============================================================
CREATE TABLE IF NOT EXISTS publicacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    texto TEXT NULL,
    medio_tipo ENUM('imagen', 'video') NULL,
    medio_ruta VARCHAR(255) NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_publicacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
);
