CREATE DATABASE IF NOT EXISTS recetario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE recetario;

-- Usuarios que pueden iniciar sesión. La contraseña se guarda con password_hash().
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- Cada receta pertenece a una categoría y a un usuario (su autor).
CREATE TABLE IF NOT EXISTS recetas (
    id_receta INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    instrucciones TEXT NOT NULL,
    tiempo_minutos INT NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    id_categoria INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT recetas_tiempo_ck CHECK (tiempo_minutos > 0),
    CONSTRAINT recetas_categorias_fk FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE RESTRICT,
    CONSTRAINT recetas_usuarios_fk FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
);

-- Catálogo de ingredientes: cada ingrediente aparece una sola vez.
CREATE TABLE IF NOT EXISTS ingredientes (
    id_ingrediente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

-- Tabla intermedia receta-ingrediente con la cantidad y la unidad.
-- Al borrar una receta se borran sus filas; un ingrediente usado en recetas no se puede borrar.
CREATE TABLE IF NOT EXISTS receta_ingrediente (
    id_receta INT NOT NULL,
    id_ingrediente INT NOT NULL,
    cantidad DECIMAL(8,2) NOT NULL,
    unidad ENUM('g', 'kg', 'ml', 'l', 'ud', 'cucharada', 'cucharadita') NOT NULL DEFAULT 'g',
    PRIMARY KEY (id_receta, id_ingrediente),
    CONSTRAINT receta_ingrediente_cantidad_ck CHECK (cantidad > 0),
    CONSTRAINT receta_ingrediente_recetas_fk FOREIGN KEY (id_receta) REFERENCES recetas(id_receta) ON DELETE CASCADE,
    CONSTRAINT receta_ingrediente_ingredientes_fk FOREIGN KEY (id_ingrediente) REFERENCES ingredientes(id_ingrediente) ON DELETE RESTRICT
);

-- Datos de prueba (ficticios)
-- Contraseña de los dos usuarios de prueba: Recetario1234
INSERT INTO usuarios (nombre_usuario, email, contrasena) VALUES
('ana', 'ana@example.com', '$2y$10$MR/3ES0Kx14ieSuAOJs/aOqb3xKi7M02XsZkA2.Vpym9CG0pcLM3m'),
('luis', 'luis@example.com', '$2y$10$sFCZGh3B58Lxh/2Q6b/mTONdjjW2W3n9hen5tGuEPviP.mZwS9Iim');

INSERT INTO categorias (nombre) VALUES
('Principales'),
('Postres'),
('Entrantes');

INSERT INTO recetas (titulo, descripcion, instrucciones, tiempo_minutos, imagen, id_categoria, id_usuario) VALUES
('Tortilla de patatas', 'La clásica tortilla española, jugosa por dentro.', 'Freír las patatas, batir los huevos, mezclar y cuajar en la sartén.', 40, NULL, 1, 1),
('Bizcocho', 'Bizcocho esponjoso para el desayuno o la merienda.', 'Batir los huevos con el azúcar, añadir la harina y hornear 35 min a 180 ºC.', 50, NULL, 2, 2),
('Gazpacho', 'Sopa fría de tomate, ideal para el verano.', 'Triturar el tomate, el pepino y el pimiento con el aceite, la sal y el vinagre. Enfriar antes de servir.', 15, NULL, 3, 1);

INSERT INTO ingredientes (nombre) VALUES
('Patata'), ('Huevo'), ('Aceite de oliva'), ('Harina'), ('Azúcar'),
('Tomate'), ('Pepino'), ('Pimiento verde'), ('Sal'), ('Vinagre');

INSERT INTO receta_ingrediente (id_receta, id_ingrediente, cantidad, unidad) VALUES
(1, 1, 600, 'g'), (1, 2, 6, 'ud'), (1, 3, 100, 'ml'),
(2, 4, 250, 'g'), (2, 2, 3, 'ud'), (2, 5, 200, 'g'),
(3, 6, 1, 'kg'), (3, 7, 1, 'ud'), (3, 8, 1, 'ud'), (3, 3, 50, 'ml'), (3, 9, 1, 'cucharadita'), (3, 10, 2, 'cucharada');
