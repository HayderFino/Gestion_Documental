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
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>GESTIÓN <br> DOCUMENTAL</h2>
            </div>
            <nav class="nav-menu">
                <li class="nav-item">
                    <a href="<?= $_ENV['BASE_URL'] ?>/dashboard" class="nav-link <?= ($active == 'dashboard') ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $_ENV['BASE_URL'] ?>/expedientes" class="nav-link <?= ($active == 'expedientes') ? 'active' : '' ?>">
                        <i class="fas fa-file-archive"></i> Expedientes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $_ENV['BASE_URL'] ?>/prestamos" class="nav-link <?= ($active == 'prestamos') ? 'active' : '' ?>">
                        <i class="fas fa-hand-holding"></i> Préstamos
                    </a>
                </li>
                <?php if ($_SESSION['user_role'] === 'Administrador'): ?>
                <li class="nav-item">
                    <a href="<?= $_ENV['BASE_URL'] ?>/usuarios" class="nav-link <?= ($active == 'usuarios') ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $_ENV['BASE_URL'] ?>/auditoria" class="nav-link <?= ($active == 'auditoria') ? 'active' : '' ?>">
                        <i class="fas fa-history"></i> Auditoría
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item" style="margin-top: 2rem;">
                    <a href="<?= $_ENV['BASE_URL'] ?>/logout" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="topbar">
                <div class="page-title">
                    <h1><?= $title ?? 'Dashboard' ?></h1>
                </div>
                <div class="user-profile">
                    <span>Bienvenido, <strong><?= $_SESSION['user_name'] ?? 'Usuario' ?></strong></span>
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
