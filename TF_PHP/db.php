<?php
// TF_PHP/db.php
const DB_HOST = '127.0.0.1';
const DB_PORT = '3307';
const DB_NAME = 'tf_php';
const DB_USER = 'root';
const DB_PASS = '';

$dsn = 'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4';

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (Throwable $e) {
  die('Error de conexión: ' . $e->getMessage());
}

// <-- Asegúrate de que ESTA función exista aquí
function e(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

