<?php

require_once "conexionRSS.php";

$sXML = download("https://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/portada");

$oXML = new SimpleXMLElement($sXML);

require_once "conexionBBDD.php";

if (pg_connection_status($link) !== PGSQL_CONNECTION_OK) {
    printf("Conexión a el periódico El País ha fallado");
} else {

    $contador = 0;
    $categoria = ["Política", "Deportes", "Ciencia", "España", "Economía", "Música", "Cine", "Europa", "Justicia"];
    $categoriaFiltro = "";

    foreach ($oXML->channel->item as $item) {

        for ($i = 0; $i < count($item->category); $i++) {

            for ($j = 0; $j < count($categoria); $j++) {

                if ($item->category[$i] == $categoria[$j]) {
                    $categoriaFiltro = "[" . $categoria[$j] . "]" . $categoriaFiltro;
                }
            }
        }



        $fPubli = strtotime($item->pubDate);
        $new_fPubli = date('Y-m-d', $fPubli);


        $content = $item->children("content", true);
        $encoded = $content->encoded;

        // Extraer imagen del feed
        $imagen = '';
        $media = $item->children('media', true);
        if (isset($media->content)) {
            $imagen = (string)$media->content->attributes()->url;
        }


        $sql = "SELECT link FROM elpais";
        $result = pg_query($link, $sql);

        while ($sqlCompara = pg_fetch_array($result)) {


            if ($sqlCompara['link'] == $item->link) {

                $Repit = true;
                $contador = $contador + 1;
                $contadorTotal = $contador;
                break;
            } else {
                $Repit = false;
            }
        }
        if ($Repit == false && $categoriaFiltro != "") {

            // Escapamos cada valor para evitar errores SQL
            $titulo = pg_escape_string($link, $item->title);
            $linkNoticia = pg_escape_string($link, $item->link);
            $descripcion = pg_escape_string($link, strip_tags((string)$encoded)); // opcional: quitar etiquetas HTML
            $categoriaFinal = pg_escape_string($link, $categoriaFiltro);
            $fecha = pg_escape_string($link, $new_fPubli);
            $contenido = pg_escape_string($link, (string)$encoded);
            $imagenFinal = pg_escape_string($link, $imagen);

            // Insertamos especificando las columnas (sin cod, se autogenera)
            $sql = "INSERT INTO elpais (titulo, link, descripcion, categoria, fpubli, contenido) 
            VALUES ('$titulo', '$linkNoticia', '$descripcion', '$categoriaFinal', '$fecha', '$contenido')";

            $result = pg_query($link, $sql);

            if (!$result) {
                echo "Error al insertar: " . pg_last_error($link);
            }
        }


        $categoriaFiltro = "";
    }
}
