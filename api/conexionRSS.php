<?php

function download($ruta){
    $maxAttempts = 3;
    $attempt = 0;
    $backoff = 1; // segundos

    while ($attempt < $maxAttempts) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ruta);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'RSS-Reader/1.0 (+https://example.com)');
        curl_setopt($ch, CURLOPT_FAILONERROR, false);

        $salida = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($salida !== false && $httpCode >= 200 && $httpCode < 400) {
            return $salida;
        }

        // Log detalles para debug
        error_log("download attempt " . ($attempt+1) . " failed for $ruta — http:$httpCode curl:$curlErr");

        $attempt++;
        sleep($backoff);
        $backoff *= 2;
    }

    return false;
}




