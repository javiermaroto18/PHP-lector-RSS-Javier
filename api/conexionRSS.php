<?php

function download($ruta){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ruta);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, false);
    // Mejoras: seguir redirecciones cuando esté permitido, timeout y user agent razonable
    @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'RSS-Reader/1.0 (+https://example.com)');
    // Fallar en códigos HTTP >=400
    curl_setopt($ch, CURLOPT_FAILONERROR, false);

    $salida = curl_exec($ch);
    if ($salida === false) {
        error_log('download curl error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 400) {
        error_log("download HTTP code $httpCode for $ruta");
        return false;
    }

    return $salida;
}




