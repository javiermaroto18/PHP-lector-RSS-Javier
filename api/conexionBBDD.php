<?php
// conexionBBDD.php - Versión Universal para Vercel/Neon Postgres

$Repit = false;
$link = null;

// 1. Buscamos la URL de conexión en las variables de entorno (Prioridad: POSTGRES_URL -> DATABASE_URL)
$db_url = getenv('POSTGRES_URL');

if (!$db_url) {
    $db_url = getenv('DATABASE_URL');
}

// Si no hay variables de entorno (por ejemplo en pruebas locales), usar valores concretos de Neon.
// Nota: es preferible configurar estas variables en Vercel en lugar de dejarlas en el código.
if (!$db_url) {
    $db_url = 'postgresql://neondb_owner:npg_l4K9jtfmSGxz@ep-cold-fog-adlo3tve-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require';
}

// Variables para la conexión
$host = "";
$port = "";
$user = "";
$pass = "";
$dbname = "";
$sslmode = "require"; // Neon/Vercel requiere SSL

if ($db_url) {
    // A) Si tenemos una URL completa (Lo más habitual en Vercel)
    $db_opts = parse_url($db_url);
    
    $host = $db_opts["host"];
    $port = isset($db_opts["port"]) ? $db_opts["port"] : "5432";
    $user = $db_opts["user"];
    $pass = $db_opts["pass"];
    $dbname = ltrim($db_opts["path"], '/'); // Quitamos la barra inicial '/'
    
} else {
    // B) Si no hay URL, intentamos usar las variables sueltas (PGHOST, PGUSER...)
    $host = getenv('PGHOST');
    $user = getenv('PGUSER');
    $pass = getenv('PGPASSWORD');
    $dbname = getenv('PGDATABASE');
    $port = "5432";
    
    if (!$host || !$user) {
        die("Error Crítico: No se encontraron variables de entorno para la base de datos (POSTGRES_URL, DATABASE_URL o PGHOST).");
    }
}

try {
    // 2. Construimos el DSN (Data Source Name) para PostgreSQL
    // Añadimos sslmode=require porque Neon/Vercel lo obligan
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";
    
    // 3. Crear la conexión PDO
    $link = new PDO($dsn, $user, $pass);
    
    // 4. Configuración de opciones
    $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $link->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Por defecto fetch asociativo
    $link->exec("SET NAMES 'UTF8'");

} catch (PDOException $e) {
    // En producción, no es recomendable mostrar la contraseña en el error, pero sí el mensaje
    echo "Error de conexión a la Base de Datos: " . $e->getMessage();
    die();
}
?>