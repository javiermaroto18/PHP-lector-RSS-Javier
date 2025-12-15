<?php

require_once "conexionRSS.php";
require_once "conexionBBDD.php";

// Intentar descargar el feed (HTTPS primero, luego HTTP)
$sXML = download("https://e00-elmundo.uecdn.es/elmundo/rss/espana.xml");
if (!$sXML) {
    $sXML = download("http://e00-elmundo.uecdn.es/elmundo/rss/espana.xml");
}

if (!$sXML || trim($sXML) === '') {
    error_log("RSSElMundo: respuesta vacía al descargar el feed");
    echo "Error: no se pudo descargar el feed de El Mundo (respuesta vacía).";
    exit;
}

$sTrim = ltrim($sXML);
if (stripos($sTrim, '<') !== 0 || (stripos($sTrim, '<rss') === false && stripos($sTrim, '<feed') === false && stripos($sTrim, '<?xml') === false)) {
    error_log('RSSElMundo: el contenido descargado no parece ser un feed XML válido');
    error_log('RSSElMundo: inicio contenido: ' . substr($sTrim, 0, 200));
    echo "Error: feed descargado no es XML válido.";
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
    error_log('RSSElMundo: SimpleXMLElement exception: ' . $e->getMessage());
    echo "Error: feed XML inválido.";
    exit;
}

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

        // Insertar si no existe aún; guardar categoría si existe
        if ($count == 0) {
            $categoriaInsert = $categoriaFiltro !== "" ? $categoriaFiltro : null;
            $sqlInsert = "INSERT INTO elmundo (titulo, link, descripcion, categoria, fpubli, contenido)
                          VALUES (:titulo, :link, :descripcion, :categoria, :fpubli, :contenido)";
            $stmtIns = $link->prepare($sqlInsert);
            $stmtIns->execute([
                ':titulo' => $titulo,
                ':link' => $linkNoticia,
                ':descripcion' => $description,
                ':categoria' => $categoriaInsert,
                ':fpubli' => $fPubli,
                ':contenido' => $contenido
            ]);
            error_log('RSSElMundo: noticia insertada - ' . $linkNoticia);
        } else {
            error_log('RSSElMundo: noticia ya existe, omitida - ' . $linkNoticia);
        }
    } catch (PDOException $e) {
        error_log("RSSElMundo DB error: " . $e->getMessage());
    }
}