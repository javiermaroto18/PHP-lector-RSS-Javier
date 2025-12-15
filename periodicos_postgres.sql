-- periodicos_postgres.sql
-- Tablas adaptadas a PostgreSQL (sin volcado completo de filas).
-- Recomendación: convertir el fichero original `periodicos.sql` con las instrucciones más abajo

-- Crear base de datos (ejecutar fuera o con permisos de superuser):
-- CREATE DATABASE periodicos;
-- Conectarse: \c periodicos

CREATE TABLE IF NOT EXISTS elmundo (
  cod integer PRIMARY KEY,
  titulo varchar(200),
  link text,
  descripcion text,
  categoria varchar(50),
  fpubli date,
  contenido text
);

CREATE TABLE IF NOT EXISTS elpais (
  cod integer PRIMARY KEY,
  titulo varchar(200),
  link text,
  descripcion text,
  categoria varchar(50),
  fpubli date,
  contenido text
);

-- IMPORT INSTRUCTIONS
-- Option A (recommended, on Linux/macOS or WSL): convert original MySQL dump to a PostgreSQL-friendly file:
-- 1) Remove backticks and ENGINE/CHARSET lines, and change `int(11)`/`longtext` types.
-- Example using sed/awk (run in the folder with the original `periodicos.sql`):
--
-- sed -e 's/`//g' periodicos.sql \
-- | sed -E 's/INT\([0-9]+\)/integer/Ig' \
-- | sed -E 's/longtext/text/Ig' \
-- | sed -E 's/ENGINE=[^;]+;//Ig' \
-- > periodicos_converted.sql
--
-- Then inspect `periodicos_converted.sql` and import with:
-- psql "<CONNECTION_STRING>" -f periodicos_converted.sql
--
-- Option B (quick for small datasets): use this file (`periodicos_postgres.sql`) to create empty tables,
-- then load the INSERT blocks manually from the original file after removing backticks and MySQL-specific syntax.

-- Example psql import using Neon/Vercel connection URL:
-- psql "postgresql://neondb_owner:npg_l4K9jtfmSGxz@ep-cold-fog-adlo3tve-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require" -f periodicos_converted.sql

-- End of file
