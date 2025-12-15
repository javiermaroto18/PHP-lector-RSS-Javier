<?php
$Repit = false;

// Conectar a PostgreSQL usando la URL completa
$link = pg_connect('postgresql://neondb_owner:npg_n7V4BuqFEJZM@ep-lively-mouse-a4jorg4h-pooler.us-east-1.aws.neon.tech/neondb?sslmode=require');

// Verificar conexión
if (!$link) {
    die("Error de conexión: " . pg_last_error());
}

// Configurar la conexión para UTF-8
pg_set_client_encoding($link, "UTF8");
