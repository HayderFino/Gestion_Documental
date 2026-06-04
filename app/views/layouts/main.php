<?php
/**
 * Layout principal. Estas variables se pasan desde el controlador.
 * Se inicializan por si la plantilla se carga sin todos los datos.
 */
$active = $active ?? '';
$content = $content ?? '';
$title = $title ?? 'Gestión de Expedientes';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - CAS</title>
    <link rel="stylesheet" href="<?= $_ENV['BASE_URL'] ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Overlay para cerrar sidebar en móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="fas fa-archive sidebar-icon"></i>
                    <h2>GESTIÓN <br><span>DOCUMENTAL</span></h2>
                </div>
                <!-- Botón cerrar en móvil -->
                <button class="sidebar-close" id="sidebarClose" aria-label="Cerrar menú">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nav class="nav-menu" role="navigation" aria-label="Menú principal">
                <li class="nav-item">
                    <a href="<?= $_ENV['BASE_URL'] ?>/dashboard" class="nav-link <?= ($active == 'dashboard') ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $_ENV['BASE_URL'] ?>/expedientes" class="nav-link <?= ($active == 'expedientes') ? 'active' : '' ?>">
                        <i class="fas fa-file-archive"></i> <span>Expedientes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $_ENV['BASE_URL'] ?>/prestamos" class="nav-link <?= ($active == 'prestamos') ? 'active' : '' ?>">
                        <i class="fas fa-hand-holding"></i> <span>Préstamos</span>
                    </a>
                </li>
                <?php if ($_SESSION['user_role'] === 'Administrador'): ?>
                <li class="nav-item">
                    <a href="<?= $_ENV['BASE_URL'] ?>/usuarios" class="nav-link <?= ($active == 'usuarios') ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> <span>Usuarios</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $_ENV['BASE_URL'] ?>/auditoria" class="nav-link <?= ($active == 'auditoria') ? 'active' : '' ?>">
                        <i class="fas fa-history"></i> <span>Auditoría</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item nav-item-logout">
                    <a href="<?= $_ENV['BASE_URL'] ?>/logout" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span>
                    </a>
                </li>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content" id="mainContent">
            <header class="topbar">
                <!-- Botón hamburger para móvil -->
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebar">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
                <div class="page-title">
                    <h1><?= $title ?? 'Dashboard' ?></h1>
                </div>
                <div class="user-profile">
                    <i class="fas fa-user-circle user-avatar"></i>
                    <span class="user-name">Bienvenido, <strong><?= $_SESSION['user_name'] ?? 'Usuario' ?></strong></span>
                </div>
            </header>

            <section class="content">
                <?= $content ?>
            </section>
        </main>
    </div>

    <script src="<?= $_ENV['BASE_URL'] ?>/assets/js/main.js"></script>
</body>
</html>
