<?php
session_start();
require_once __DIR__ . '/../db.php';

$hoy = date('Y-m-d');

// ==================== OBTENER LISTA DE USUARIOS ====================
$usuarios_stmt = $pdo->query("
    SELECT id_user, nombre, apellidos
    FROM users_data
    ORDER BY nombre, apellidos
");
$usuarios = $usuarios_stmt->fetchAll(PDO::FETCH_ASSOC);

// id seleccionado (viene por GET 'user' o vacío)
$selected_user_id = isset($_GET['user']) ? (int)$_GET['user'] : 0;

// ==================== SI HAY USUARIO SELECCIONADO, CARGAR SUS CITAS ====================
$citas = [];
if ($selected_user_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM citas WHERE idUser = :idUser ORDER BY fecha_cita ASC");
    $stmt->execute([':idUser' => $selected_user_id]);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==================== CREAR CITA ====================
if (isset($_POST['nueva_cita'])) {
    $idUser_post = isset($_POST['idUser']) ? (int)$_POST['idUser'] : 0;
    $fecha = $_POST['fecha'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');

    if ($idUser_post > 0 && $fecha >= $hoy) {
        $stmt = $pdo->prepare("INSERT INTO citas (idUser, fecha_cita, motivo_cita) 
                               VALUES (:idUser, :fecha, :motivo)");
        $stmt->execute([':idUser' => $idUser_post, ':fecha' => $fecha, ':motivo' => $motivo]);
    }
    header("Location: citaciones-administracion.php?user=" . $idUser_post);
    exit();
}

// ==================== BORRAR CITA ====================
if (isset($_GET['borrar']) && isset($_GET['user'])) {
    $idCita = (int)$_GET['borrar'];
    $idUser_get = (int)$_GET['user'];

    if ($idCita > 0) {
        $del = $pdo->prepare("DELETE FROM citas WHERE idCita = :idCita");
        $del->execute([':idCita' => $idCita]);
    }

    header("Location: citaciones-administracion.php?user=" . $idUser_get);
    exit();
}

// ==================== EDITAR CITA ====================
if (isset($_POST['editar_cita'])) {
    $idCita = isset($_POST['idCita']) ? (int)$_POST['idCita'] : 0;
    $idUser_post = isset($_POST['idUser']) ? (int)$_POST['idUser'] : 0;
    $fecha = $_POST['fecha'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');

    if ($idCita > 0) {
        $upd = $pdo->prepare("UPDATE citas 
                              SET fecha_cita = :fecha, motivo_cita = :motivo 
                              WHERE idCita = :idCita");
        $upd->execute([
            ':fecha' => $fecha,
            ':motivo' => $motivo,
            ':idCita' => $idCita
        ]);
    }

    header("Location: citaciones-administracion.php?user=" . $idUser_post);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración de Citas</title>
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="./../CSS/registro.css">
</head>
<body>

<header>
    <div class="container nav">
        <a href="../index.php"><span>Mi Pueblo</span></a>
        <nav>
            <ul>
                <li><a href="../index.php">Inicio</a></li>
                <li><a href="./noticias.php">Noticias</a></li>
                <li><a href="./usuario-administracion.php">Usuarios-administracion</a></li>
                <li><a href="./citaciones-administracion.php" class="active">Citaciones-administracion</a></li>
                <li><a href="./noticias-administracion.php">Noticias-administracion</a></li>
                <li><a href="./cerrarSesion.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </div>
</header>

<h1>Administración de Citas</h1>

<!-- Seleccionar usuario -->
<form method="GET" class="form-registro">
    <label>Seleccionar usuario:</label>
    <select name="user" required>
        <option value="">-- Selecciona --</option>

        <?php if (!empty($usuarios)): ?>
            <?php foreach ($usuarios as $u): ?>
                <option value="<?= (int)$u['id_user'] ?>"
                    <?= $selected_user_id === (int)$u['id_user'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['nombre'] . ' ' . ($u['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        <?php else: ?>
            <option value="">No hay usuarios</option>
        <?php endif; ?>
    </select>

    <button class="btn-primary">Ver citas</button>
</form>

<?php if ($selected_user_id > 0): ?>

<!-- Crear nueva cita -->
<form method="POST" class="form-registro">
    <fieldset>
        <legend>Crear nueva cita</legend>

        <input type="hidden" name="idUser" value="<?= $selected_user_id ?>">

        <label>Fecha:</label>
        <input type="date" name="fecha" required min="<?= $hoy ?>">

        <label>Motivo:</label>
        <input type="text" name="motivo" required>

        <button type="submit" name="nueva_cita" class="btn-primary">Crear cita</button>
    </fieldset>
</form>

<h2>Citas del usuario</h2>

<table class="form-registro">
    <tr>
        <th>Fecha</th>
        <th>Motivo</th>
        <th>Acciones</th>
    </tr>

    <?php if (!empty($citas)): ?>
        <?php foreach ($citas as $row): ?>
            <tr>
                <form method="POST">
                    <td>
                        <input type="date" name="fecha" value="<?= htmlspecialchars($row['fecha_cita']) ?>" required>
                    </td>

                    <td>
                        <input type="text" name="motivo" value="<?= htmlspecialchars($row['motivo_cita']) ?>" required>
                    </td>

                    <td>
                        <input type="hidden" name="idCita" value="<?= (int)$row['idCita'] ?>">
                        <input type="hidden" name="idUser" value="<?= $selected_user_id ?>">

                        <button name="editar_cita" class="btn-primary">Guardar</button>

                        <a href="citaciones-administracion.php?borrar=<?= (int)$row['idCita'] ?>&user=<?= $selected_user_id ?>"
                           onclick="return confirm('¿Borrar cita?');"
                           class="btn-primary">
                           Borrar
                        </a>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="3">No hay citas para este usuario.</td></tr>
    <?php endif; ?>
</table>

<?php endif; ?>

 <footer>
    <p>&copy; <span id="year"></span> Mi Pueblo. Todos los derechos reservados.</p>
  </footer>
  <script src="../JS/script.js"></script>
</body>
</html>
