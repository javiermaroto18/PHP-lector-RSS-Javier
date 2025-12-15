<?php

require_once "conexionRSS.php";

$sXML = download("https://e00-elmundo.uecdn.es/elmundo/rss/espana.xml");
$oXML = new SimpleXMLElement($sXML);

require_once "conexionBBDD.php";

if (pg_connection_status($link) !== PGSQL_CONNECTION_OK) {
    printf("Conexión a el periódico El Mundo ha fallado");
} else {

    $contador = 0;
    $categoria = ["Política", "Deportes", "Ciencia", "España", "Economía", "Música", "Cine", "Europa", "Justicia"];
    $categoriaFiltro = "";

    foreach ($oXML->channel->item as $item) {

        // Filtrar categorías
        if (isset($item->category)) {
            for ($i = 0; $i < count($item->category); $i++) {
                for ($j = 0; $j < count($categoria); $j++) {
                    if ($item->category[$i] == $categoria[$j]) {
                        $categoriaFiltro .= "[" . $categoria[$j] . "]";
                    }
                }
            }
        }

        // Fecha
        $fPubli = strtotime($item->pubDate);
        $new_fPubli = date('Y-m-d', $fPubli);

        // Descripción e imagen
        $media = $item->children("media", true);
        $description = (string)$media->description;

        // Extraer imagen del feed
        $imagen = '';
        if (isset($media->content)) {
            $imagen = (string)$media->content->attributes()->url;
        }

        // Comprobar si el link ya existe
        $sql = "SELECT link FROM elmundo";
        $result = pg_query($link, $sql);

        $Repit = false;
        while ($sqlCompara = pg_fetch_array($result)) {
            if ($sqlCompara['link'] == $item->link) {
                $Repit = true;
                $contador++;
                break;
            }
        }

        if ($Repit == false && $categoriaFiltro != "") {

            // Escapar valores sin truncar categoria
            $titulo = pg_escape_string($link, $item->title);
            $linkNoticia = pg_escape_string($link, $item->link);
            $descripcion = pg_escape_string($link, strip_tags($description));
            $categoriaFinal = pg_escape_string($link, $categoriaFiltro);
            $fecha = pg_escape_string($link, $new_fPubli);
            $contenido = pg_escape_string($link, (string)$item->guid);
            $imagenFinal = pg_escape_string($link, $imagen);

            // Insertar en la tabla especificando columnas (sin cod, se autogenera)
            $sql = "INSERT INTO elmundo (titulo, link, descripcion, categoria, fpubli, contenido, imagen) 
                    VALUES ('$titulo', '$linkNoticia', '$descripcion', '$categoriaFinal', '$fecha', '$contenido', '$imagenFinal')";

            $result = pg_query($link, $sql);

            if (!$result) {
                echo "Error al insertar: " . pg_last_error($link);
            }
        }

        // Reset filtro de categorías
        $categoriaFiltro = "";
    }
}
