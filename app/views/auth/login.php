<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Gestión de Expedientes</title>
    <link rel="stylesheet" href="<?= $_ENV['BASE_URL'] ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 3rem;
            border-radius: var(--radius-lg);
            text-align: center;
            box-shadow: var(--shadow-lg);
        }
        .login-card h2 { margin-bottom: 2rem; color: var(--primary-dark); }
        .form-group { margin-bottom: 1.5rem; text-align: left; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-muted); }
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: var(--transition);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 79, 139, 0.1);
        }
        .login-btn { width: 100%; margin-top: 1rem; padding: 1rem; }
    </style>
</head>
<body>
    <div class="login-card glass">
        <i class="fas fa-file-invoice-dollar fa-3x" style="color: var(--primary-color); margin-bottom: 1.5rem;"></i>
        <h2>Acceso al Sistema</h2>
        
        <?php if (isset($error)): ?>
            <div class="badge badge-danger" style="margin-bottom: 1rem; display: block; padding: 0.8rem;"><?= $error ?></div>
        <?php endif; ?>

        <form action="<?= $_ENV['BASE_URL'] ?>/login" method="POST">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="usuario" class="form-control" placeholder="Ingrese su usuario" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary login-btn">
                <i class="fas fa-sign-in-alt"></i> Ingresar
            </button>
        </form>
    </div>
</body>
</html>
