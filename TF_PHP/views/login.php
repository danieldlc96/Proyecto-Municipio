<?php
session_start();
require_once __DIR__ . '/../db.php';

$errors = [];
$old = [];


// Si el usuario ya está logueado, redirigimos al index
if (!empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

// Mostrar mensaje si viene de registro exitoso
$registered = isset($_GET['registered']) && $_GET['registered'] == 1;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = array_map(fn($v) => is_string($v) ? trim($v) : $v, $_POST);


    $usuario = $old['usuario'] ?? '';
    $password = $old['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $errors[] = 'Rellena el usuario y la contraseña.';
    } else {
        try {
            // Buscamos en users_login y obtenemos también datos de users_data mediante JOIN
            $sql = "
                SELECT l.idLogin, l.idUser, l.usuario, l.password AS passhash, l.rol,
                       d.nombre, d.apellidos, d.email
                FROM users_login l
                LEFT JOIN users_data d ON l.idUser = d.id_user
                WHERE l.usuario = :usuario
                LIMIT 1
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':usuario' => $usuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);






            if (!$row) {
                $errors[] = 'Usuario o contraseña incorrectos.';
            } else {
                // Verificar contraseña
                if (password_verify($password, $row['passhash'])) {
                    // Login correcto: guardar información relevante en session
                    $_SESSION['user'] = [
                        'idLogin'  => (int)$row['idLogin'],
                        'idUser'   => (int)$row['idUser'],
                        'usuario'  => $row['usuario'],
                        'rol'      => $row['rol'],
                        'nombre'   => $row['nombre'] ?? '',
                        'apellidos'=> $row['apellidos'] ?? '',
                        'email'    => $row['email'] ?? '',
                    ];

                    // Redirigir al index
                    header('Location: ../index.php?login=1');
                    exit;
                } else {
                    $errors[] = 'Usuario o contraseña incorrectos.';
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'Error al realizar el login: ' . e($e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Iniciar sesión — Mi Pueblo</title>
  <link rel="stylesheet" href="./../CSS/styles.css">   <!-- global -->
  <link rel="stylesheet" href="../CSS/registro.css">
  
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
            <li><a href="registro.php">Registro</a></li>
            <li><a href="login.php" class="active">Login</a></li>
        <?php else: ?>
            <li><a href="cerrarSesion.php">Cerrar sesión</a></li>
        <?php endif; ?>
    </ul>
</nav>

    </div>
  </header>

  <main class="container">
    <h1>Iniciar sesión</h1>

    <?php if ($registered): ?>
      <div>
        Registro completado correctamente. Ya puedes iniciar sesión.
      </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash'])): ?>
      <div>
        <?= e($_SESSION['flash']); ?>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div>
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="login.php" method="post" class="form-registro">
      <fieldset>
        <legend>Acceso</legend>

        <label for="usuario">Usuario</label>
        <input id="usuario" name="usuario" type="text" value="<?= e($old['usuario'] ?? '') ?>" required>

        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" required>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Entrar</button>
          <span>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></span>
        </div>
      </fieldset>
    </form>
  </main>

  <footer>
    <p>&copy; <span id="year"></span> Mi Sitio. Todos los derechos reservados.</p>
  </footer>
  <script src="../JS/script.js"></script>
</body>
</html>

