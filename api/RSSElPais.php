<?php
require_once "conexionRSS.php";

$sXML = download("http://ep00.epimg.net/rss/elpais/portada.xml");
$oXML = new SimpleXMLElement($sXML);

require_once "conexionBBDD.php";

if (!isset($link)) {
    printf("Conexión a la base de datos ha fallado");
} else {

    // --- AUTO-CONFIGURACIÓN DE TABLA ---
    try {
        $sqlCreateTable = "CREATE TABLE IF NOT EXISTS elpais (
            id SERIAL PRIMARY KEY,
            titulo TEXT,
            link TEXT UNIQUE,
            descripcion TEXT,
            categoria TEXT,
            fPubli DATE,
            encoded TEXT
        )";
        $link->exec($sqlCreateTable);
    } catch (PDOException $e) {
        // Continuar
    }
    // -----------------------------------

    $contador = 0;
    $categoriaLista = ["Política", "Deportes", "Ciencia", "España", "Economía", "Música", "Cine", "Europa", "Justicia"];
    $categoriaFiltro = "";

    foreach ($oXML->channel->item as $item) {

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

        // Extraer content encoded
        $content = $item->children("content", true);
        $encoded = (string)$content->encoded;

        // --- CAMBIO A PDO ---
        // Verificar duplicados eficientemente
        $sqlCheck = "SELECT COUNT(*) FROM elpais WHERE link = :link";
        $stmt = $link->prepare($sqlCheck);
        $stmt->execute([':link' => $linkNoticia]);
        $existe = $stmt->fetchColumn();

        if ($existe == 0 && $categoriaFiltro != "") {
            try {
                // Insertar
                $sqlInsert = "INSERT INTO elpais (titulo, link, descripcion, categoria, fPubli, encoded) 
                              VALUES (:titulo, :link, :descripcion, :categoria, :fPubli, :encoded)";
                
                $stmtInsert = $link->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':titulo' => (string)$item->title,
                    ':link' => $linkNoticia,
                    ':descripcion' => (string)$item->description,
                    ':categoria' => $categoriaFiltro,
                    ':fPubli' => $new_fPubli,
                    ':encoded' => $encoded
                ]);
            } catch (PDOException $e) {
                // Ignorar error
            }
        }
        
        $categoriaFiltro = "";
    }
}
?>