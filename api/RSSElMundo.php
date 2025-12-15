<?php

require_once "conexionRSS.php";

// Descargar feed (intenta HTTP/HTTPS)
$sXML = download("https://e00-elmundo.uecdn.es/elmundo/rss/espana.xml");
if (!$sXML) {
    $sXML = download("http://e00-elmundo.uecdn.es/elmundo/rss/espana.xml");
}

if (!$sXML) {
    error_log("RSSElMundo: no se pudo descargar el feed de El Mundo");
    echo "Error: no se pudo descargar el feed de El Mundo.";
    exit;
}

libxml_use_internal_errors(true);
try {
    $oXML = new SimpleXMLElement($sXML);
} catch (Exception $e) {
    $errs = libxml_get_errors();
    foreach ($errs as $err) {
        error_log("RSSElMundo XML: " . trim($err->message));
    }
    libxml_clear_errors();
    echo "Error: feed XML inválido.";
    exit;
}

require_once "conexionBBDD.php";

if (!isset($link) || !($link instanceof PDO)) {
    error_log("RSSElMundo: conexión PDO no disponible");
    echo "Error: conexión a la base de datos no disponible.";
    exit;
}

// Crear tabla si no existe
try {
    $sqlCreate = "CREATE TABLE IF NOT EXISTS elmundo (
        id SERIAL PRIMARY KEY,
        titulo TEXT,
        link TEXT UNIQUE,
        descripcion TEXT,
        categoria TEXT,
        fpubli DATE,
        contenido TEXT
    )";
    $link->exec($sqlCreate);
} catch (PDOException $e) {
    error_log("RSSElMundo crear tabla: " . $e->getMessage());
}

$categoriaLista = ["Política","Deportes","Ciencia","España","Economía","Música","Cine","Europa","Justicia"];

foreach ($oXML->channel->item as $item) {
    $categoriaFiltro = "";
    if (isset($item->category)) {
        for ($i = 0; $i < count($item->category); $i++) {
            $cat = (string)$item->category[$i];
            foreach ($categoriaLista as $c) {
                if ($cat === $c) {
                    $categoriaFiltro = "[" . $c . "]" . $categoriaFiltro;
                }
            }
        }
    }

    $titulo = (string)$item->title;
    $linkNoticia = (string)$item->link;
    // media description fallback
    $media = $item->children("media", true);
    $description = $media ? (string)$media->description : (string)$item->description;
    $fPubli = isset($item->pubDate) ? date('Y-m-d', strtotime((string)$item->pubDate)) : null;
    $contenido = isset($item->guid) ? (string)$item->guid : '';

    try {
        $sqlCheck = "SELECT COUNT(*) FROM elmundo WHERE link = :link";
        $stmt = $link->prepare($sqlCheck);
        $stmt->execute([':link' => $linkNoticia]);
        $count = $stmt->fetchColumn();

        if ($count == 0 && $categoriaFiltro !== "") {
            $sqlInsert = "INSERT INTO elmundo (titulo, link, descripcion, categoria, fpubli, contenido)
                          VALUES (:titulo, :link, :descripcion, :categoria, :fpubli, :contenido)";
            $stmtIns = $link->prepare($sqlInsert);
            $stmtIns->execute([
                ':titulo' => $titulo,
                ':link' => $linkNoticia,
                ':descripcion' => $description,
                ':categoria' => $categoriaFiltro,
                ':fpubli' => $fPubli,
                ':contenido' => $contenido
            ]);
        }
    } catch (PDOException $e) {
        error_log("RSSElMundo DB error: " . $e->getMessage());
    }
}