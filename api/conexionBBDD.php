<?php
$Repit = false;

// Conectar a PostgreSQL usando la URL completa
$link = pg_connect('postgresql://neondb_owner:npg_l4K9jtfmSGxz@ep-cold-fog-adlo3tve-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require');

// Verificar conexión
if (!$link) {
    die("Error de conexión: " . pg_last_error());
}

// Configurar la conexión para UTF-8
pg_set_client_encoding($link, "UTF8");
