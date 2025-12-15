<?php
// conexionBBDD.php adaptado para Vercel Postgres

$Repit = false;

// 1. Intentamos obtener la URL de la base de datos de Vercel
$db_url = getenv('POSTGRES_URL');

if (!$db_url) {
    // Si no encuentra la variable, mostramos error (útil para depurar)
    die("Error crítico: No se ha encontrado la variable de entorno POSTGRES_URL. Asegúrate de haber conectado la base de datos en Vercel.");
}

// 2. Desglosamos la URL que nos da Vercel para sacar usuario, contraseña, host...
$db_opts = parse_url($db_url);
$host = $db_opts["host"];
$port = $db_opts["port"];
$user = $db_opts["user"];
$pass = $db_opts["pass"];
$dbname = ltrim($db_opts["path"], '/');

try {
    // 3. Creamos la conexión usando PDO (el estándar para Postgres)
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    // Usamos la variable $link para intentar mantener compatibilidad con tu código
    $link = new PDO($dsn, $user, $pass);
    
    // Configuración de errores y codificación
    $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $link->exec("SET NAMES 'UTF8'");

} catch (PDOException $e) {
    echo "Error conectando a la base de datos: " . $e->getMessage();
    die();
}
?>