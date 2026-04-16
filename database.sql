CREATE DATABASE IF NOT EXISTS usuarios CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE usuarios;

-- Tabla de usuarios
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

-- Tabla de mensajes
CREATE TABLE IF NOT EXISTS mensaje(
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

-- Indice util para conversacion (permite reimportar sin error)
DROP INDEX IF EXISTS idx_mensaje_chat ON mensaje;
CREATE INDEX idx_mensaje_chat ON mensaje(remitente_id, destinatario_id, enviado_en);
