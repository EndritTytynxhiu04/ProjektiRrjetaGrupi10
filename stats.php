<?php

// Funksion qe lexon statistikat nga shared_data.json
function getServerData() {

    // Path korrekt i file-it (funksionon pa marre parasysh prej nga ekzekutohet)
    $file = __DIR__ . "/shared_data.json";

    // Vlera default nese file nuk ekziston ose ka gabim
    $defaultData = [
        "active_connections" => 0,
        "total_messages"     => 0,
        "clients"            => []
    ];

    // Nese file nuk ekziston atehere kthe default
    if (!file_exists($file)) {
        return $defaultData;
    }

    // Lexo permbajtjen e file-it
    $content = file_get_contents($file);

    // Konverto JSON ne array
    $data = json_decode($content, true);

    // Nese ka gabim ne JSON atehere kthe default
    if (!is_array($data)) {
        return $defaultData;
    }

    // Kthe te dhenat e sakta
    return $data;
}