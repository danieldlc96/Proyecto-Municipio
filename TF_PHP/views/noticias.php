<?php
session_start();
?>


<?php
// Mostrar errores mientras depuras
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Noticias — Mi Pueblo</title>
  <link rel="stylesheet" href="./../CSS/styles.css">   <!-- global -->
  <link rel="stylesheet" href="./../CSS/noticias.css">     

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
          <li><a href="./noticias.php"  class="active">Noticias</a></li>
 <?php if (!isset($_SESSION['user'])): ?>
    <!-- VISITANTE -->
    <li><a href="./registro.php">Registro</a></li>
    <li><a href="./login.php">Login</a></li>

<?php else: ?>

    <?php if ($_SESSION['user']['rol'] === 'admin'): ?>
        <li><a href="./usuario-administracion.php">Usuarios-administracion</a></li>
        <li><a href="./citaciones-administracion.php">Citaciones-administracion</a></li>
        <li><a href="./noticias-administracion.php">Noticias-administracion</a></li>
    <?php endif; ?>
    <li><a href="./citaciones.php">Citaciones</a></li>
    <li><a href="./perfil.php">Perfil</a></li>
    <li><a href="./cerrarSesion.php">Cerrar sesión</a></li>
    <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>

  <main id="contenido" class="container">
  <section class="section">
    <h1>Noticias</h1>

    <?php
    $sql = "SELECT n.idNoticia, n.titulo, n.imagen, n.texto, n.fecha,
                   u.nombre, u.apellidos
            FROM noticias n
            JOIN users_data u ON u.id_user = n.idUser
            ORDER BY n.fecha DESC, n.idNoticia DESC";

    try {
      $noticias = $pdo->query($sql)->fetchAll();
    } catch (Throwable $t) {
      $noticias = [];
      echo '<p class="error">Fallo al cargar noticias: ' . e($t->getMessage()) . '</p>';
    }
    ?>

    <?php if (!$noticias): ?>
      <p>No hay noticias publicadas todavía.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($noticias as $n):
          $fecha = !empty($n['fecha']) ? (new DateTime($n['fecha']))->format('d/m/Y') : '';
          $autor = trim(($n['nombre'] ?? '') . ' ' . ($n['apellidos'] ?? ''));
        ?>
        <article class="news-card">
          <?php if (!empty($n['imagen'])): ?>
            <div class="news-thumb">
              <img src="<?= e($n['imagen']) ?>" alt="Imagen de la noticia: <?= e($n['titulo']) ?>">
            </div>
          <?php endif; ?>

          <div class="news-body">
            <h2 class="news-title"><?= e($n['titulo']) ?></h2>
            <p class="news-meta"><?= $fecha ? e($fecha) : 'Sin fecha' ?> · por <?= e($autor ?: 'Desconocido') ?></p>
            <p class="news-text"><?= nl2br(e($n['texto'])) ?></p>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>


  <footer>
    <p>&copy; <span id="year"></span> Mi Pueblo. Todos los derechos reservados.</p>
  </footer>

  <script src="../JS/script.js"></script>
</body>
</html>
