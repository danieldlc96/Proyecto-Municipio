<?php
session_start();
require_once __DIR__ . '/../db.php';

$mensaje = "";
$error = "";

// =======================================================
// ≡ 1) BORRAR NOTICIA
// =======================================================
if (isset($_GET['borrar'])) {
    $id = intval($_GET['borrar']);

    // borrar de BD
    $pdo->prepare("DELETE FROM noticias WHERE idNoticia=?")->execute([$id]);

    $mensaje = "Noticia eliminada correctamente.";
}


// =======================================================
// ≡ 2) CREAR NUEVA NOTICIA
// =======================================================
if (isset($_POST['crear'])) {
    $titulo   = trim($_POST['titulo']);
    $texto    = trim($_POST['texto']);
    $imagen   = trim($_POST['imagen_url']); 
    $fecha    = date("Y-m-d");
    $idUser   = $_SESSION['user']['idUser'];

    if (!$titulo || !$texto || !$imagen) {
        $error = "Todos los campos son obligatorios.";
    } else {
        // Insertar noticia en BD
        $stmt = $pdo->prepare("
            INSERT INTO noticias (titulo, imagen, texto, fecha, idUser)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$titulo, $imagen, $texto, $fecha, $idUser]);

        $mensaje = "Noticia creada correctamente.";
    }
}


// =======================================================
// ≡ 3) EDITAR NOTICIA EXISTENTE
// =======================================================
if (isset($_POST['editar'])) {
    $idN     = intval($_POST['idNoticia']);
    $titulo  = trim($_POST['titulo']);
    $texto   = trim($_POST['texto']);
    $imagen  = trim($_POST['imagen_url']); 

    if (!$titulo || !$texto || !$imagen) {
        $error = "Todos los campos son obligatorios.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE noticias
            SET titulo=?, texto=?, imagen=?
            WHERE idNoticia=?
        ");
        $stmt->execute([$titulo, $texto, $imagen, $idN]);

        $mensaje = "Noticia modificada correctamente.";
    }
}


// =======================================================
// ≡ 4) OBTENER TODAS LAS NOTICIAS
// =======================================================
 $sql = "SELECT n.idNoticia, n.titulo, n.imagen, n.texto, n.fecha,
                   u.nombre, u.apellidos
            FROM noticias n
            JOIN users_data u ON u.id_user = n.idUser
            ORDER BY n.fecha DESC, n.idNoticia DESC";
$noticias = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Administración de Noticias</title>
<link rel="stylesheet" href="./../CSS/styles.css">
<link rel="stylesheet" href="./../CSS/noticias.css">
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
                <li><a href="./citaciones-administracion.php">Citaciones-administracion</a></li>
                <li><a href="./noticias-administracion.php"  class="active">Noticias-administracion</a></li>
                <li><a href="./perfil.php">Perfil</a></li>
                <li><a href="./cerrarSesion.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </div>
</header>

<h1>Administración de Noticias</h1>

<?php if ($mensaje): ?>
    <p><?= htmlspecialchars($mensaje) ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<hr>

<!-- =======================================================
     FORMULARIO CREAR NOTICIA
======================================================= -->
<h2>Crear Nueva Noticia</h2>

<form method="POST">
    <input type="text" name="titulo" placeholder="Título" required><br><br>
    <textarea name="texto" placeholder="Texto de la noticia" required></textarea><br><br>
    Imagen (URL): <input type="text" name="imagen_url" placeholder="https://ejemplo.com/imagen.jpg" required><br><br>
    <button type="submit" name="crear">Crear Noticia</button>
</form>


<hr>

<!-- =======================================================
     LISTADO DE NOTICIAS + FORMULARIOS DE EDICIÓN/BORRADO
======================================================= -->
<h2>Noticias Existentes</h2>

<?php foreach ($noticias as $n): ?>
    <div>
        <h3><?= htmlspecialchars($n['titulo']) ?></h3>
        <p><small>Fecha: <?= $n['fecha'] ?> — Autor: <?= $n['nombre']." ".$n['apellidos'] ?></small></p>
        <img src="<?= htmlspecialchars($n['imagen']) ?>" width="180">


        <!-- FORMULARIO EDITAR -->
        <form method="POST">
            <input type="hidden" name="idNoticia" value="<?= $n['idNoticia'] ?>">
            
            <label>Título:</label><br>
            <input type="text" name="titulo" value="<?= htmlspecialchars($n['titulo']) ?>" required><br><br>

            <label>Texto:</label><br>
            <textarea name="texto" required><?= htmlspecialchars($n['texto']) ?></textarea><br><br>

            <label>Imagen (URL):</label>
            <input type="text" name="imagen_url" value="<?= htmlspecialchars($n['imagen']) ?>"><br><br>


            <button type="submit" name="editar">Guardar Cambios</button>
        </form>

        <!-- BOTÓN BORRAR -->
        <p>
            <a href="noticias-administracion.php?borrar=<?= $n['idNoticia'] ?>"
               onclick="return confirm('¿Seguro que quieres borrar esta noticia?');">
               ❌ Borrar noticia
            </a>
        </p>
    </div>
<?php endforeach; ?>

 <footer>
    <p>&copy; <span id="year"></span> Mi Pueblo. Todos los derechos reservados.</p>
  </footer>
  <script src="../JS/script.js"></script>
</body>
</html>
