<?php

require_once "conexionRSS.php";
require_once "conexionBBDD.php";

// Descargar feed (intenta HTTP y HTTPS)
$sXML = download("https://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/portada");
if (!$sXML) {
    $sXML = download("https://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/portada");
}

if (!$sXML) {
    error_log("RSSElPais: no se pudo descargar el feed de El País");
    echo "Error: no se pudo descargar el feed de El País.";
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

        if ($count == 0 && $categoriaFiltro !== "") {
            $sqlInsert = "INSERT INTO elpais (titulo, link, descripcion, categoria, fpubli, encoded)
                          VALUES (:titulo, :link, :descripcion, :categoria, :fpubli, :encoded)";
            $stmtIns = $link->prepare($sqlInsert);
            $stmtIns->execute([
                ':titulo' => $titulo,
                ':link' => $linkNoticia,
                ':descripcion' => $descripcion,
                ':categoria' => $categoriaFiltro,
                ':fpubli' => $fPubli,
                ':encoded' => $encoded
            ]);
        }
    } catch (PDOException $e) {
        error_log("RSSElPais DB error: " . $e->getMessage());
        // continuar con siguiente item
    }
}