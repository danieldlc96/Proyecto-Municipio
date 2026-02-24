<?php
session_start();
require_once __DIR__ . '/../db.php';

// Validar sesión
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'user') {
    header("Location: login.php");
    exit();
}

$idUser = $_SESSION['user']['idUser'];
$hoy = date('Y-m-d');

// ==================== INSERTAR CITA ====================
if (isset($_POST['nueva_cita'])) {
    $fecha = $_POST['fecha'];
    $motivo = trim($_POST['motivo']);

    if ($fecha >= $hoy) {
        $stmt = $pdo->prepare("INSERT INTO citas (idUser, fecha_cita, motivo_cita) VALUES (:idUser, :fecha, :motivo)");
        $stmt->execute([
            ':idUser' => $idUser,
            ':fecha' => $fecha,
            ':motivo' => $motivo
        ]);
        header("Location: citaciones.php");
        exit();
    } else {
        $error = "No puedes seleccionar una fecha pasada.";
    }
}

// ==================== BORRAR CITA ====================
if (isset($_GET['borrar'])) {
    $idCita = $_GET['borrar'];

    // Verificar que no sea pasada
    $check = $pdo->prepare("SELECT fecha_cita FROM citas WHERE idCita = :idCita AND idUser = :idUser");
    $check->execute([':idCita' => $idCita, ':idUser' => $idUser]);
    $res = $check->fetch();

    if ($res && $res['fecha_cita'] >= $hoy) {
        $del = $pdo->prepare("DELETE FROM citas WHERE idCita = :idCita AND idUser = :idUser");
        $del->execute([':idCita' => $idCita, ':idUser' => $idUser]);
    }
    header("Location: citaciones.php");
    exit();
}

// ==================== EDITAR CITA ====================
if (isset($_POST['editar_cita'])) {
    $idCita = $_POST['idCita'];
    $fecha = $_POST['fecha'];
    $motivo = trim($_POST['motivo']);

    $check = $pdo->prepare("SELECT fecha_cita FROM citas WHERE idCita = :idCita AND idUser = :idUser");
    $check->execute([':idCita' => $idCita, ':idUser' => $idUser]);
    $res = $check->fetch();

    if ($res && $res['fecha_cita'] >= $hoy && $fecha >= $hoy) {
        $upd = $pdo->prepare("UPDATE citas SET fecha_cita = :fecha, motivo_cita = :motivo WHERE idCita = :idCita AND idUser = :idUser");
        $upd->execute([
            ':fecha' => $fecha,
            ':motivo' => $motivo,
            ':idCita' => $idCita,
            ':idUser' => $idUser
        ]);
    }
    header("Location: citaciones.php");
    exit();
}

// ==================== OBTENER CITAS ====================
$stmt = $pdo->prepare("SELECT * FROM citas WHERE idUser = :idUser ORDER BY fecha_cita ASC");
$stmt->execute([':idUser' => $idUser]);
$citas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Citaciones</title>
    <link rel="stylesheet" href="./../CSS/styles.css">
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
                <li><a href="./citaciones.php" class="active">Citaciones</a></li>
                <li><a href="./perfil.php">Perfil</a></li>
                <li><a href="./cerrarSesion.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </div>
</header>

<h1>Mis Citas</h1>

<!-- Formulario nueva cita -->
<form method="POST" class="form-registro">
    <fieldset>
        <legend>Solicitar nueva cita</legend>

        <label>Fecha:</label>
        <input type="date" name="fecha" required min="<?= $hoy ?>">

        <label>Motivo:</label>
        <input type="text" name="motivo" required>

        <div class="form-actions">
            <button type="submit" name="nueva_cita" class="btn-primary">Solicitar cita</button>
        </div>
    </fieldset>
</form>

<?php if (isset($error)) echo "<div class='alert alert-error'>$error</div>"; ?>

<h2>Próximas Citas</h2>

<table class="form-registro">
    <tr>
        <th>Fecha</th>
        <th>Motivo</th>
        <th>Acciones</th>
    </tr>

    <?php foreach ($citas as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['fecha_cita']) ?></td>
            <td><?= htmlspecialchars($row['motivo_cita']) ?></td>
            <td>
                <?php if ($row['fecha_cita'] >= $hoy): ?>
                    <form method="POST">
                        <input type="hidden" name="idCita" value="<?= $row['idCita'] ?>">
                        <input type="date" name="fecha" value="<?= $row['fecha_cita'] ?>" min="<?= $hoy ?>" required>
                        <input type="text" name="motivo" value="<?= htmlspecialchars($row['motivo_cita']) ?>" required>
                        <button name="editar_cita" class="btn-primary">Guardar</button>
                    </form>

                    <a href="citaciones.php?borrar=<?= $row['idCita'] ?>" 
                       onclick="return confirm('¿Borrar cita?');" 
                       class="btn-primary">Borrar</a>
                <?php else: ?>
                    <span>No editable</span>
                <?php endif; ?>
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


