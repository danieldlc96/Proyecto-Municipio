<?php
session_start();
require_once __DIR__ . '/../db.php';

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos
    $old = array_map(fn($v) => trim($v), $_POST);

    $nombre   = $old['nombre'] ?? '';
    $apellidos = $old['apellidos'] ?? '';
    $email    = $old['email'] ?? '';
    $telefono = $old['telefono'] ?? '';
    $fecha_nacimiento = $old['fecha_nacimiento'] ?? '';
    $direccion = $old['direccion'] ?? '';
    $sexo = $old['sexo'] ?? '';
    $usuario  = $old['usuario'] ?? '';
    $password = $old['password'] ?? '';
    $password2 = $old['password2'] ?? '';

    // Validaciones básicas
    if ($nombre === '' || $apellidos === '' || $email === '' || $usuario === '' || $password === '' || $password2 === '') {
        $errors[] = "Rellena todos los campos obligatorios (*).";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del correo electrónico no es válido.";
    }

    if ($password !== $password2) {
        $errors[] = "Las contraseñas no coinciden.";
    }

    if (strlen($password) < 5) {
        $errors[] = "La contraseña debe tener al menos 5 caracteres.";
    }

    // Verificar duplicados
    if (empty($errors)) {
        $checkEmail = $pdo->prepare("SELECT id_user FROM users_data WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
            $errors[] = "Ya existe un usuario con ese correo electrónico.";
        }

        $checkUser = $pdo->prepare("SELECT idLogin FROM users_login WHERE usuario = ?");
        $checkUser->execute([$usuario]);
        if ($checkUser->fetch()) {
            $errors[] = "El nombre de usuario ya está en uso.";
        }
    }

    // Insertar si todo está bien
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $insertData = $pdo->prepare("
                INSERT INTO users_data (nombre, apellidos, email, telefono, fecha_nacimiento, direccion, sexo)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insertData->execute([$nombre, $apellidos, $email, $telefono, $fecha_nacimiento ?: null, $direccion, $sexo]);

            $idUser = $pdo->lastInsertId();
            $passHash = password_hash($password, PASSWORD_DEFAULT);

            $insertLogin = $pdo->prepare("
                INSERT INTO users_login (idUser, usuario, password, rol)
                VALUES (?, ?, ?, 'user')
            ");
            $insertLogin->execute([$idUser, $usuario, $passHash]);

            $pdo->commit();

            header('Location: login.php?registered=1');
            exit;

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = "Error al registrar: " . e($e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Registro — Mi Pueblo</title>
  <link rel="stylesheet" href="./../CSS/styles.css">   <!-- global -->
  <link rel="stylesheet" href="./../CSS/registro.css">
</head>
<body>
  <header>
    <div class="container nav">
      <a href="../index.php">
        <span>Mi Pueblo</span>
      </a>
 <nav>
    <ul>
        <li><a href="../index.php">Inicio</a></li>
        <li><a href="noticias.php">Noticias</a></li>

        <?php if (!isset($_SESSION['user'])): ?>
            <li><a href="registro.php" class="active">Registro</a></li>
            <li><a href="login.php">Login</a></li>
        <?php else: ?>
            <li><a href="cerrarSesion.php">Cerrar sesión</a></li>
        <?php endif; ?>
    </ul>
</nav>

    </div>
  </header>

  <main class="container">
    <h1>Registro de usuario</h1>
    <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>.</p>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= e($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="registro.php" method="post" class="form-registro">
      <fieldset>
        <legend>Datos personales</legend>

        <div class="form-row">
          <label>Nombre *</label>
          <input type="text" name="nombre" value="<?= e($old['nombre'] ?? '') ?>" required>

          <label>Apellidos *</label>
          <input type="text" name="apellidos" value="<?= e($old['apellidos'] ?? '') ?>" required>

          <label>Email *</label>
          <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>

          <label>Teléfono</label>
          <input type="text" name="telefono" value="<?= e($old['telefono'] ?? '') ?>">

          <label>Fecha de nacimiento</label>
          <input type="date" name="fecha_nacimiento" value="<?= e($old['fecha_nacimiento'] ?? '') ?>">

          <label>Sexo</label>
          <select name="sexo">
            <option value="masculino" <?= (($old['sexo'] ?? '') === 'masculino') ? 'selected' : '' ?>>Masculino</option>
            <option value="femenino" <?= (($old['sexo'] ?? '') === 'femenino') ? 'selected' : '' ?>>Femenino</option>
          </select>
        </div>

        <label>Dirección</label>
        <input type="text" name="direccion" value="<?= e($old['direccion'] ?? '') ?>">
      </fieldset>

      <fieldset>
        <legend>Datos de acceso</legend>

        <label>Nombre de usuario *</label>
        <input type="text" name="usuario" value="<?= e($old['usuario'] ?? '') ?>" required>

        <label>Contraseña *</label>
        <input type="password" name="password" required>

        <label>Repetir contraseña *</label>
        <input type="password" name="password2" required>
      </fieldset>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Registrarme</button>
      </div>
    </form>
  </main>

  <footer>
    <p>&copy; <span id="year"></span> Mi Pueblo. Todos los derechos reservados.</p>
  </footer>
  <script src="../JS/script.js"></script>

</body>
</html>



