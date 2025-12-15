<?php
require_once "conexionRSS.php";

// Descargamos el XML
$sXML = download("https://e00-elmundo.uecdn.es/elmundo/rss/espana.xml");
$oXML = new SimpleXMLElement($sXML);

require_once "conexionBBDD.php";

// Verificamos si la conexión PDO ($link) existe
if (!isset($link)) {
    printf("Conexión a la base de datos ha fallado");
} else {

    // --- AUTO-CONFIGURACIÓN DE TABLA (Solo para Vercel) ---
    // Creamos la tabla 'elmundo' si no existe, adaptada a Postgres
    try {
        $sqlCreateTable = "CREATE TABLE IF NOT EXISTS elmundo (
            id SERIAL PRIMARY KEY,
            titulo TEXT,
            link TEXT UNIQUE,
            descripcion TEXT,
            categoria TEXT,
            fPubli DATE,
            guid TEXT
        )";
        $link->exec($sqlCreateTable);
    } catch (PDOException $e) {
        // Si falla la creación, continuamos (puede que ya exista)
    }
    // -----------------------------------------------------

    $contador = 0;
    $categoriaLista = ["Política", "Deportes", "Ciencia", "España", "Economía", "Música", "Cine", "Europa", "Justicia"];
    $categoriaFiltro = "";

    foreach ($oXML->channel->item as $item) {

        // Extraer namespaces para media
        $media = $item->children("media", true);
        $description = (string)$media->description;
        // Si no hay descripción en media, intentamos coger la normal
        if (empty($description)) {
            $description = (string)$item->description;
        }

        // Lógica de Categorías
        for ($i = 0; $i < count($item->category); $i++) {
            for ($j = 0; $j < count($categoriaLista); $j++) {
                if ((string)$item->category[$i] == $categoriaLista[$j]) {
                    $categoriaFiltro = "[" . $categoriaLista[$j] . "]" . $categoriaFiltro;
                }
            }
        }

        $fPubli = strtotime($item->pubDate);
        $new_fPubli = date('Y-m-d', $fPubli);
        $linkNoticia = (string)$item->link;

        // --- CAMBIO A PDO Y OPTIMIZACIÓN ---
        // En lugar de traer TODOS los links y comparar con PHP (lento), 
        // preguntamos a la BBDD si este link específico ya existe.
        
        $sqlCheck = "SELECT COUNT(*) FROM elmundo WHERE link = :link";
        $stmt = $link->prepare($sqlCheck);
        $stmt->execute([':link' => $linkNoticia]);
        $existe = $stmt->fetchColumn();

        if ($existe == 0 && $categoriaFiltro != "") {
            // INSERT usando Prepared Statements (Seguro contra comillas ' )
            try {
                $sqlInsert = "INSERT INTO elmundo (titulo, link, descripcion, categoria, fPubli, guid) 
                              VALUES (:titulo, :link, :descripcion, :categoria, :fPubli, :guid)";
                
                $stmtInsert = $link->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':titulo' => (string)$item->title,
                    ':link' => $linkNoticia,
                    ':descripcion' => $description,
                    ':categoria' => $categoriaFiltro,
                    ':fPubli' => $new_fPubli,
                    ':guid' => (string)$item->guid
                ]);
            } catch (PDOException $e) {
                // Ignoramos errores de duplicados si ocurren
            }
        }
        
        $categoriaFiltro = "";
    }
}
?>