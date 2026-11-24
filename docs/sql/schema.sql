CREATE DATABASE IF NOT EXISTS recetario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE recetario;

CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS recetas (
    id_receta INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    instrucciones TEXT NOT NULL,
    id_categoria INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT recetas_categorias_fk FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS ingredientes (
    id_ingrediente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    cantidad DECIMAL(8,2) NOT NULL,
    unidad ENUM('g', 'ml', 'ud', 'cucharada') DEFAULT 'g',
    id_receta INT NOT NULL,
    CONSTRAINT ingredientes_recetas_fk FOREIGN KEY (id_receta) REFERENCES recetas(id_receta) ON DELETE CASCADE
);

-- Datos de prueba
INSERT INTO categorias (nombre) VALUES
('Principales'),
('Postres');

INSERT INTO recetas (titulo, instrucciones, id_categoria) VALUES
('Tortilla de patatas', 'Freír las patatas, batir los huevos, mezclar y cuajar en la sartén.', 1),
('Bizcocho', 'Batir los huevos con el azúcar, añadir la harina y hornear 35 min a 180 ºC.', 2);

INSERT INTO ingredientes (nombre, cantidad, unidad, id_receta) VALUES
('Patata', 600, 'g', 1), ('Huevo', 6, 'ud', 1), ('Aceite de oliva', 100, 'ml', 1),
('Harina', 250, 'g', 2), ('Huevo', 3, 'ud', 2), ('Azúcar', 200, 'g', 2);