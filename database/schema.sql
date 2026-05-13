CREATE DATABASE IF NOT EXISTS gestion_expedientes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_expedientes;

-- Roles Table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Permisos Table
CREATE TABLE permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rol_Permiso Relationship
CREATE TABLE rol_permiso (
    rol_id INT NOT NULL,
    permiso_id INT NOT NULL,
    PRIMARY KEY (rol_id, permiso_id),
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE
);

-- Usuarios Table
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    ultimo_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- Tramites Table (Types of procedures)
CREATE TABLE tramites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Expedientes Table
CREATE TABLE expedientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_expediente VARCHAR(50) NOT NULL UNIQUE,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    tramite_id INT,
    fecha_apertura DATE,
    estado ENUM('disponible', 'prestado', 'archivado', 'en_revision') DEFAULT 'disponible',
    numero_tomos INT DEFAULT 1,
    foliacion_total INT DEFAULT 0,
    ubicacion_fisica VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tramite_id) REFERENCES tramites(id)
);

-- Lineas de Expediente (Sub-files or components)
CREATE TABLE lineas_expediente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expediente_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    tipo VARCHAR(100),
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expediente_id) REFERENCES expedientes(id) ON DELETE CASCADE
);

-- Motivos de Consulta
CREATE TABLE motivos_consulta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Prestamos Table
CREATE TABLE prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expediente_id INT NOT NULL,
    usuario_solicitante_id INT NOT NULL,
    usuario_archivo_id INT NOT NULL, -- Who authorized
    motivo_id INT NOT NULL,
    fecha_prestamo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion_prevista DATETIME,
    observaciones TEXT,
    estado ENUM('pendiente', 'entregado', 'vencido', 'devuelto') DEFAULT 'entregado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expediente_id) REFERENCES expedientes(id),
    FOREIGN KEY (usuario_solicitante_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_archivo_id) REFERENCES usuarios(id),
    FOREIGN KEY (motivo_id) REFERENCES motivos_consulta(id)
);

-- Devoluciones Table
CREATE TABLE devoluciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestamo_id INT NOT NULL UNIQUE,
    usuario_recibe_id INT NOT NULL,
    fecha_devolucion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado_expediente TEXT, -- physical condition
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id),
    FOREIGN KEY (usuario_recibe_id) REFERENCES usuarios(id)
);

-- Auditoria Table (Action logs)
CREATE TABLE auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(100),
    registro_id INT,
    valor_anterior JSON,
    valor_nuevo JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Logs Table (System/Error logs)
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nivel ENUM('INFO', 'WARNING', 'ERROR', 'CRITICAL') DEFAULT 'INFO',
    mensaje TEXT NOT NULL,
    contexto JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed basic data
INSERT INTO roles (nombre, descripcion) VALUES 
('Administrador', 'Acceso total al sistema'),
('Archivo', 'Gestión de préstamos y expedientes'),
('Funcionario', 'Solicitud de expedientes'),
('Contratista', 'Consulta de expedientes'),
('Supervisor', 'Revisión y reportes');

-- Admin user (password: admin123)
INSERT INTO usuarios (rol_id, usuario, password, nombre_completo, email) 
VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Sistema', 'admin@example.com');
