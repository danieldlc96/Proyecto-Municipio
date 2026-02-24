<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// Verificar sesión
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$loginIdUser = (int) ($_SESSION['user']['idUser'] ?? 0);
if ($loginIdUser <= 0) {
    header('Location: registro.php');
    exit;
}

// Obtener datos del usuario
try {
    $stmt = $pdo->prepare("
        SELECT d.*
        FROM users_data d
        JOIN users_login l ON l.idUser = d.id_user
        WHERE l.idUser = :idUser
        LIMIT 1
    ");
    $stmt->execute([':idUser' => $loginIdUser]);
    $data = $stmt->fetch();

    if (!$data) {
        // fallback
        $stmt2 = $pdo->prepare("SELECT * FROM users_data WHERE id_user = :id LIMIT 1");
        $stmt2->execute([':id' => $loginIdUser]);
        $data = $stmt2->fetch();
    }
} catch (Throwable $e) {
    $data = false;
    $errors[] = 'Error al cargar datos: ' . e($e->getMessage());
}

// Mensajes flash
$success = $_SESSION['profile_ok'] ?? $_SESSION['pw_ok'] ?? '';
if (!empty($_SESSION['profile_ok'])) unset($_SESSION['profile_ok']);
if (!empty($_SESSION['pw_ok'])) unset($_SESSION['pw_ok']);
$errors = [];
if (!empty($_SESSION['profile_err'])) { $errors[] = $_SESSION['profile_err']; unset($_SESSION['profile_err']); }
if (!empty($_SESSION['pw_err'])) { $errors[] = $_SESSION['pw_err']; unset($_SESSION['pw_err']); }
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Mi Perfil — Mi Pueblo</title>
    <link rel="stylesheet" href="./../CSS/styles.css">   <!-- global -->
    <link rel="stylesheet" href="./../CSS/perfil.css">
    
</head>
<body>
<header>
    <div class="container nav">
        <a href="../index.php"><span>Mi Pueblo</span></a>
        <nav>
            <ul>
                <li><a href="../index.php">Inicio</a></li>
                <li><a href="./noticias.php">Noticias</a></li>
                <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
                <li><a href="./usuario-administracion.php">Usuarios-administracion</a></li>
                <li><a href="./citaciones-administracion.php">Citaciones-administracion</a></li>
                <li><a href="./noticias-administracion.php">Noticias-administracion</a></li>
                <?php endif; ?>
                <li><a href="./citaciones.php">Citaciones</a></li>
                <li><a href="./perfil.php" class="active">Perfil</a></li>
                <li><a href="./cerrarSesion.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <h1>Mi perfil</h1>

    <?php if ($success): ?>
        <div class="alert success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($data): ?>
        <section>
            <h2>Datos actuales</h2>
            <ul>
                <li><strong>Usuario:</strong> <?= e($_SESSION['user']['usuario'] ?? '') ?></li>
                <li><strong>Nombre:</strong> <?= e($data['nombre']) ?></li>
                <li><strong>Apellidos:</strong> <?= e($data['apellidos']) ?></li>
                <li><strong>Email:</strong> <?= e($data['email']) ?></li>
                <li><strong>Teléfono:</strong> <?= e($data['telefono']) ?></li>
                <li><strong>Fecha de nacimiento:</strong> <?= e($data['fecha_nacimiento']) ?></li>
                <li><strong>Dirección:</strong> <?= e($data['direccion']) ?></li>
                <li><strong>Sexo:</strong> <?= e($data['sexo']) ?></li>
            </ul>
        </section>

        <section>
            <h2>Editar información</h2>
            <form class="form-registro" action="editarDatos.php" method="POST">
                <input type="hidden" name="id_user" value="<?= e($data['id_user']) ?>">

                <label>Nombre *</label>
                <input type="text" name="nombre" value="<?= e($data['nombre']) ?>" required>

                <label>Apellidos *</label>
                <input type="text" name="apellidos" value="<?= e($data['apellidos']) ?>" required>

                <label>Email *</label>
                <input type="email" name="email" value="<?= e($data['email']) ?>" required>

                <label>Teléfono</label>
                <input type="text" name="telefono" value="<?= e($data['telefono']) ?>">

                <label>Fecha de nacimiento</label>
                <input type="date" name="fecha_nacimiento" value="<?= e($data['fecha_nacimiento']) ?>">

                <label>Dirección</label>
                <input type="text" name="direccion" value="<?= e($data['direccion']) ?>">

                <label>Sexo</label>
                <select name="sexo">
                    <option value="masculino" <?= ($data['sexo'] === 'masculino') ? 'selected' : '' ?>>Masculino</option>
                    <option value="femenino" <?= ($data['sexo'] === 'femenino') ? 'selected' : '' ?>>Femenino</option>
                    <option value="otro" <?= ($data['sexo'] === 'otro') ? 'selected' : '' ?>>Otro</option>
                </select>

                <button class=" btn btn-primary" type="submit">Guardar cambios</button>
            </form>
        </section>

        <section>
            <h2>Cambiar contraseña</h2>
            <form class="form-registro" action="editarDatos.php" method="POST">
                <input type="hidden" name="idUser" value="<?= e($loginIdUser) ?>">

                <label>Contraseña actual *</label>
                <input type="password" name="current_password" required>

                <label>Nueva contraseña *</label>
                <input type="password" name="new_password" required>

                <label>Repetir nueva contraseña *</label>
                <input type="password" name="new_password2" required>

                <button class=" btn btn-primary" type="submit">Cambiar contraseña</button>
            </form>
        </section>
    <?php else: ?>
        <p>No se han podido cargar tus datos. Si el problema persiste, cierra sesión y vuelve a iniciar sesión o contacta con el administrador.</p>
    <?php endif; ?>
</main>

 <footer>
    <p>&copy; <span id="year"></span> Mi Pueblo. Todos los derechos reservados.</p>
  </footer>
  <script src="../JS/script.js"></script>
</body>
</html>
