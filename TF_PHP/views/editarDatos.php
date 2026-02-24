<?php
session_start();
require_once __DIR__ . '/../db.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit;
}

// Función de escape
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// Determinar si hay sesión activa
$isLoggedIn = !empty($_SESSION['user']);

// ===========================
// --- REGISTRO DE USUARIO ---
// ===========================
if (!$isLoggedIn) {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Validaciones básicas
    if ($usuario === '' || $password === '' || $password2 === '' || $nombre === '' || $apellidos === '' || $email === '') {
        $_SESSION['flash'] = 'Rellena todos los campos obligatorios.';
        header('Location: registro.php');
        exit;
    }
    if ($password !== $password2) {
        $_SESSION['flash'] = 'Las contraseñas no coinciden.';
        header('Location: registro.php');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash'] = 'Email inválido.';
        header('Location: registro.php');
        exit;
    }

    try {
        // Verificar usuario o email duplicado
        $stmt = $pdo->prepare("SELECT 1 FROM users_login WHERE usuario = :usuario LIMIT 1");
        $stmt->execute([':usuario' => $usuario]);
        if ($stmt->fetch()) {
            $_SESSION['flash'] = 'El usuario ya existe.';
            header('Location: registro.php');
            exit;
        }
        $stmt = $pdo->prepare("SELECT 1 FROM users_data WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $_SESSION['flash'] = 'El email ya está registrado.';
            header('Location: registro.php');
            exit;
        }

        // Insertar en users_data
        $stmt = $pdo->prepare("INSERT INTO users_data (nombre, apellidos, email) VALUES (:nombre, :apellidos, :email)");
        $stmt->execute([
            ':nombre' => $nombre,
            ':apellidos' => $apellidos,
            ':email' => $email
        ]);
        $id_user = $pdo->lastInsertId();

        // Insertar en users_login
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users_login (idUser, usuario, password, rol) VALUES (:idUser, :usuario, :password, 'user')");
        $stmt->execute([
            ':idUser' => $id_user,
            ':usuario' => $usuario,
            ':password' => $hash
        ]);

        $_SESSION['flash'] = 'Registro completado correctamente. Ya puedes iniciar sesión.';
        header('Location: login.php?registered=1');
        exit;

    } catch (Throwable $e) {
        $_SESSION['flash'] = 'Error al registrar: ' . e($e->getMessage());
        header('Location: registro.php');
        exit;
    }
}

// ================================
// --- EDICIÓN DE DATOS DE PERFIL ---
// ================================
if (!empty($_POST['id_user'])) {
    $id_user = (int) ($_POST['id_user'] ?? 0);
    $idUser_session = (int) ($_SESSION['user']['idUser'] ?? 0);

    if ($id_user <= 0 || $id_user !== $idUser_session) {
        $_SESSION['profile_err'] = 'Operación no permitida.';
        header('Location: perfil.php');
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $direccion = trim($_POST['direccion'] ?? '');
    $sexo = $_POST['sexo'] ?? 'otro';

    if ($nombre === '' || $apellidos === '' || $email === '') {
        $_SESSION['profile_err'] = 'Rellena los campos obligatorios.';
        header('Location: perfil.php');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['profile_err'] = 'Email inválido.';
        header('Location: perfil.php');
        exit;
    }

    try {
        // Comprobar email duplicado
        $stmt = $pdo->prepare("SELECT id_user FROM users_data WHERE email = :email AND id_user <> :id LIMIT 1");
        $stmt->execute([':email' => $email, ':id' => $id_user]);
        if ($stmt->fetch()) {
            $_SESSION['profile_err'] = 'El email ya está registrado por otro usuario.';
            header('Location: perfil.php');
            exit;
        }

        // Actualizar users_data
        $stmt = $pdo->prepare("UPDATE users_data SET
            nombre = :nombre,
            apellidos = :apellidos,
            email = :email,
            telefono = :telefono,
            fecha_nacimiento = :fecha_nacimiento,
            direccion = :direccion,
            sexo = :sexo
            WHERE id_user = :id
        ");
        $stmt->execute([
            ':nombre' => $nombre,
            ':apellidos' => $apellidos,
            ':email' => $email,
            ':telefono' => $telefono ?: null,
            ':fecha_nacimiento' => $fecha_nacimiento ?: null,
            ':direccion' => $direccion ?: null,
            ':sexo' => $sexo,
            ':id' => $id_user
        ]);

        $_SESSION['user']['nombre'] = $nombre;
        $_SESSION['user']['apellidos'] = $apellidos;
        $_SESSION['user']['email'] = $email;

        $_SESSION['profile_ok'] = 'Datos actualizados correctamente.';

    } catch (Throwable $e) {
        $_SESSION['profile_err'] = 'Error al actualizar: ' . e($e->getMessage());
        header('Location: perfil.php');
        exit;
    }
}

// ============================
// --- CAMBIO DE CONTRASEÑA ---
// ============================
if (!empty($_POST['current_password'])) {
    $idUser_session = (int) ($_SESSION['user']['idUser'] ?? 0);
    $idUser_form = (int) ($_POST['idUser'] ?? 0);

    if ($idUser_form <= 0 || $idUser_form !== $idUser_session) {
        $_SESSION['pw_err'] = 'Operación no permitida.';
        header('Location: perfil.php');
        exit;
    }

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $new2 = $_POST['new_password2'] ?? '';

    if ($current === '' || $new === '' || $new2 === '') {
        $_SESSION['pw_err'] = 'Rellena todos los campos de contraseña.';
        header('Location: perfil.php');
        exit;
    }
    if ($new !== $new2) {
        $_SESSION['pw_err'] = 'Las nuevas contraseñas no coinciden.';
        header('Location: perfil.php');
        exit;
    }
    if (strlen($new) < 6) {
        $_SESSION['pw_err'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        header('Location: perfil.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT password FROM users_login WHERE idUser = :idUser LIMIT 1");
        $stmt->execute([':idUser' => $idUser_session]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password'])) {
            $_SESSION['pw_err'] = 'La contraseña actual es incorrecta.';
            header('Location: perfil.php');
            exit;
        }

        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users_login SET password = :hash WHERE idUser = :idUser");
        $stmt->execute([':hash' => $newHash, ':idUser' => $idUser_session]);

        $_SESSION['pw_ok'] = 'Contraseña actualizada correctamente.';
    } catch (Throwable $e) {
        $_SESSION['pw_err'] = 'Error al cambiar contraseña: ' . e($e->getMessage());
        header('Location: perfil.php');
        exit;
    }
}

// Redirigir siempre al perfil
header('Location: perfil.php');
exit;

