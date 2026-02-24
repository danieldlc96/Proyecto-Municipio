<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// ----------------------
// CREAR NUEVO USUARIO
// ----------------------
$mensaje = '';
if (isset($_POST['crear'])) {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $nacimiento = $_POST['fecha_nacimiento'];
    $direccion = $_POST['direccion'];
    $sexo = $_POST['sexo'];
    $usuario = $_POST['usuario'];
    $pass = $_POST['pass'];
    $rol = $_POST['rol'];

    // Hashear la contraseña
    $passHash = password_hash($pass, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // Insertar datos personales
        $stmt = $pdo->prepare("
            INSERT INTO users_data 
            (nombre, apellidos, email, telefono, fecha_nacimiento, direccion, sexo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nombre, $apellidos, $email, $telefono, $nacimiento, $direccion, $sexo]);
        $idUser = $pdo->lastInsertId();

        // Insertar datos de login
        $stmt = $pdo->prepare("
            INSERT INTO users_login (idUser, usuario, password, rol)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$idUser, $usuario, $passHash, $rol]);

        $pdo->commit();
        $mensaje = "Usuario creado correctamente.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje = "Error creando usuario: " . $e->getMessage();
    }
}


// ----------------------
// BORRAR USUARIO
// ----------------------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM users_login WHERE idUser = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM users_data WHERE id_user = ?")->execute([$id]);
        $pdo->commit();
        $mensaje = "Usuario eliminado.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje = "Error eliminando usuario: " . $e->getMessage();
    }
}

// ----------------------
// EDITAR USUARIO
// ----------------------
if (isset($_POST['editar'])) {
    $id = intval($_POST['id']);
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $nacimiento = $_POST['fecha_nacimiento'];
    $direccion = $_POST['direccion'];
    $sexo = $_POST['sexo'];
    $usuario = $_POST['usuario'];
    $rol = $_POST['rol'];

    try {
        $pdo->beginTransaction();
        $pdo->prepare("
            UPDATE users_data 
            SET nombre=?, apellidos=?, email=?, telefono=?, fecha_nacimiento=?, direccion=?, sexo=?
            WHERE id_user=?
        ")->execute([$nombre, $apellidos, $email, $telefono, $nacimiento, $direccion, $sexo, $id]);

        $pdo->prepare("UPDATE users_login SET usuario=?, rol=? WHERE idUser=?")
            ->execute([$usuario, $rol, $id]);

        $pdo->commit();
        $mensaje = "Usuario modificado correctamente.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje = "Error editando usuario: " . $e->getMessage();
    }
}

// ----------------------
// OBTENER TODOS LOS USUARIOS
// ----------------------
try {
    $usuarios = $pdo->query("
        SELECT d.id_user, d.nombre, d.apellidos, d.email,
               l.usuario, l.rol
        FROM users_data d
        JOIN users_login l ON d.id_user = l.idUser
        ORDER BY d.id_user ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al cargar usuarios: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Administración de Usuarios</title>
<link rel="stylesheet" href="../CSS/styles.css">
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
                <li><a href="./usuario-administracion.php" class="active">Usuarios-administracion</a></li>
                <li><a href="./citaciones-administracion.php">Citaciones-administracion</a></li>
                <li><a href="./noticias-administracion.php">Noticias-administracion</a></li>
                <li><a href="./cerrarSesion.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </div>
</header>

<h1>Administración de Usuarios</h1>

<?php if($mensaje): ?>
<p><?= htmlspecialchars($mensaje) ?></p>
<?php endif; ?>

<hr>

<h2>Crear nuevo usuario</h2>
<form method="post">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="text" name="apellidos" placeholder="Apellidos" required>
    <input type="email" name="email" placeholder="Email" required><br><br>

    <input type="text" name="telefono" placeholder="Teléfono">
    <input type="date" name="fecha_nacimiento">
    <input type="text" name="direccion" placeholder="Dirección"><br><br>

    <select name="sexo">
        <option value="H">Hombre</option>
        <option value="M">Mujer</option>
        <option value="O">Otro</option>
    </select><br><br>

    <input type="text" name="usuario" placeholder="Usuario" required>
    <input type="password" name="pass" placeholder="Contraseña" required>

    <select name="rol">
        <option value="user">Usuario</option>
        <option value="admin">Administrador</option>
    </select>

    <button type="submit" name="crear">Crear Usuario</button>
</form>

<hr>

<h2>Usuarios existentes</h2>
<table>
<tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Usuario</th>
    <th>Email</th>
    <th>Rol</th>
    <th>Acciones</th>
</tr>
<?php foreach($usuarios as $u): ?>
<tr>
    <td><?= $u['id_user'] ?></td>
    <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellidos']) ?></td>
    <td><?= htmlspecialchars($u['usuario']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= htmlspecialchars($u['rol']) ?></td>
    <td>
        <a href="usuario-administracion.php?id=<?= $u['id_user'] ?>">Editar</a> |
        <a href="?delete=<?= $u['id_user'] ?>" onclick="return confirm('¿Seguro?')">Eliminar</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

 <footer>
    <p>&copy; <span id="year"></span> Mi Pueblo. Todos los derechos reservados.</p>
  </footer>
  <script src="../JS/script.js"></script>
</body>
</html>

