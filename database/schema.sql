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
    no_orden VARCHAR(50),
    codigo VARCHAR(50),
    titulo VARCHAR(255) NOT NULL,
    fecha_inicial DATE,
    fecha_final DATE,
    caja VARCHAR(50),
    carpeta VARCHAR(50),
    libro VARCHAR(50),
    otro_anexo VARCHAR(100),
    folios INT DEFAULT 0,
    tomos INT DEFAULT 1,
    soporte VARCHAR(100) DEFAULT 'Papel',
    frecuencia_consulta VARCHAR(50) DEFAULT 'Media',
    estado ENUM('disponible', 'prestado', 'archivado', 'en_revision') DEFAULT 'disponible',
    ubicacion_fisica VARCHAR(255),
    expediente_cita VARCHAR(100),
    numero_expediente VARCHAR(50) NOT NULL UNIQUE,
    interesado VARCHAR(255),
    municipio VARCHAR(150),
    descripcion TEXT,
    tramite_id INT,
    fecha_apertura DATE,
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

-- Asignaciones Table (Expediente access control by Jefe de Línea)
CREATE TABLE asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expediente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    asignado_por VARCHAR(150),
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expediente_id) REFERENCES expedientes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
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
    usuario_archivo_id INT, -- Who authorized
    admin_aprueba VARCHAR(150),
    fecha_solicitud DATE,
    fecha_prestamo TIMESTAMP,
    fecha_devolucion_prevista DATETIME,
    tipo_vinculacion VARCHAR(100),
    linea_expediente VARCHAR(100),
    motivo_id INT,
    motivo_consulta VARCHAR(150),
    observaciones TEXT,
    estado ENUM('pendiente_prestamo', 'entregado', 'pendiente_devolucion', 'devuelto', 'vencido') DEFAULT 'pendiente_prestamo',
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
    numero_expediente VARCHAR(50) NOT NULL,
    fecha_devolucion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    nombre_devuelve VARCHAR(150) NOT NULL,
    tipo_vinculacion VARCHAR(100),
    tramite_realizado VARCHAR(100),
    numero_acto VARCHAR(150),
    tomos_entregados INT DEFAULT 1,
    folios_recibidos INT DEFAULT 0,
    folios_anexos INT DEFAULT 0,
    estado_fisico VARCHAR(50) DEFAULT 'bueno',
    usuario_recibe_id INT,
    usuario_recibe_archivo VARCHAR(150),
    estado_expediente TEXT, -- physical condition
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id),
    FOREIGN KEY (usuario_recibe_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Auditoria Table (Action logs)
CREATE TABLE auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    usuario VARCHAR(150),
    accion VARCHAR(100) NOT NULL,
    tabla VARCHAR(100),
    tabla_afectada VARCHAR(100),
    registro_id INT,
    valor_anterior JSON,
    valor_nuevo JSON,
    detalles TEXT,
    fecha DATETIME,
    ip VARCHAR(45),
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
('Jefe de Línea', 'Asignación de expedientes y supervisión'),
('Usuario', 'Funcionario o contratista que solicita préstamos de expedientes');

-- Admin and Demo users
-- Admin (password: admin123)
INSERT INTO usuarios (rol_id, usuario, password, nombre_completo, email) 
VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Sistema', 'admin@cas.gov.co');

-- Juan Pérez (password: 123)
INSERT INTO usuarios (rol_id, usuario, password, nombre_completo, email) 
VALUES (3, 'jpererz', '123', 'Juan Pérez', 'jperez@cas.gov.co');

-- Jefe de Línea (password: 123)
INSERT INTO usuarios (rol_id, usuario, password, nombre_completo, email) 
VALUES (2, 'jefe', '123', 'Jefe de Línea', 'jefe@cas.gov.co');

-- María Rodríguez (password: 123)
INSERT INTO usuarios (rol_id, usuario, password, nombre_completo, email) 
VALUES (3, 'mrodriguez', '123', 'María Rodríguez', 'mrodriguez@cas.gov.co');
