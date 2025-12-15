<?php

require_once "conexionRSS.php";
require_once "conexionBBDD.php";

// Descargar feed (intenta HTTP y luego HTTPS como fallback)
// Intentar descargar el feed (HTTP primero, luego HTTPS)
$sXML = download("http://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/portada");
if (!$sXML) {
    $sXML = download("https://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/portada");
}

// Comprobaciones adicionales: contenido vacío o no XML
if (!$sXML || trim($sXML) === '') {
    error_log("RSSElPais: respuesta vacía al descargar el feed");
    echo "Error: no se pudo descargar el feed de El País (respuesta vacía).";
    exit;
}

// Simple comprobación rápida de que parece XML (evita pasar cadenas HTML o JSON)
$sTrim = ltrim($sXML);
if (stripos($sTrim, '<') !== 0 || (stripos($sTrim, '<rss') === false && stripos($sTrim, '<feed') === false && stripos($sTrim, '<?xml') === false)) {
    error_log('RSSElPais: el contenido descargado no parece ser un feed XML válido');
    // Loguear los primeros bytes para debugging (sin volcar todo)
    error_log('RSSElPais: inicio contenido: ' . substr($sTrim, 0, 200));
    echo "Error: feed descargado no es XML válido.";
    exit;
}

libxml_use_internal_errors(true);
try {
    $oXML = new SimpleXMLElement($sXML);
} catch (Exception $e) {
    $errs = libxml_get_errors();
    foreach ($errs as $err) {
        error_log("RSSElPais XML: " . trim($err->message));
    }
    libxml_clear_errors();
    error_log('RSSElPais: SimpleXMLElement exception: ' . $e->getMessage());
    echo "Error: feed XML inválido.";
    exit;
}

if (!isset($link) || !($link instanceof PDO)) {
    error_log("RSSElPais: conexión PDO no disponible");
    echo "Error: conexión a la base de datos no disponible.";
    exit;
}

// Crear tabla si no existe (id serial, link único)
try {
    $sqlCreate = "CREATE TABLE IF NOT EXISTS elpais (
        id SERIAL PRIMARY KEY,
        titulo TEXT,
        link TEXT UNIQUE,
        descripcion TEXT,
        categoria TEXT,
        fpubli DATE,
        encoded TEXT
    )";
    $link->exec($sqlCreate);
} catch (PDOException $e) {
    error_log("RSSElPais crear tabla: " . $e->getMessage());
}

$categoriaLista = ["Política","Deportes","Ciencia","España","Economía","Música","Cine","Europa","Justicia"];

foreach ($oXML->channel->item as $item) {
    $categoriaFiltro = "";
    // Categorías
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
    $descripcion = (string)$item->description;
    $fPubli = isset($item->pubDate) ? date('Y-m-d', strtotime((string)$item->pubDate)) : null;
    $content = $item->children("content", true);
    $encoded = $content ? (string)$content->encoded : '';

    // Check duplicate
    try {
        $sqlCheck = "SELECT COUNT(*) FROM elpais WHERE link = :link";
        $stmt = $link->prepare($sqlCheck);
        $stmt->execute([':link' => $linkNoticia]);
        $count = $stmt->fetchColumn();

        // Insertar si no existe aún (antes se requería categoría coincidente).
        // Ahora insertamos todas las noticias nuevas y guardamos la categoría si existe.
        if ($count == 0) {
            $categoriaInsert = $categoriaFiltro !== "" ? $categoriaFiltro : null;
            $sqlInsert = "INSERT INTO elpais (titulo, link, descripcion, categoria, fpubli, encoded)
                          VALUES (:titulo, :link, :descripcion, :categoria, :fpubli, :encoded)";
            $stmtIns = $link->prepare($sqlInsert);
            $stmtIns->execute([
                ':titulo' => $titulo,
                ':link' => $linkNoticia,
                ':descripcion' => $descripcion,
                ':categoria' => $categoriaInsert,
                ':fpubli' => $fPubli,
                ':encoded' => $encoded
            ]);
        }
    } catch (PDOException $e) {
        error_log("RSSElPais DB error: " . $e->getMessage());
        // continuar con siguiente item
    }
}