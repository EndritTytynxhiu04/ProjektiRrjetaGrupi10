<?php

// Lidhja me config.php dhe stats.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/stats.php';

// Krijimi i socket-it
$httpSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

if ($httpSocket === false) {
    die("Gabim: Nuk u krijua socket-i: " . socket_strerror(socket_last_error()) . "\n");
}

socket_set_option($httpSocket, SOL_SOCKET, SO_REUSEADDR, 1);

$result = socket_bind($httpSocket, $httpHost, $httpPort);
if ($result === false) {
    die("Gabim: Nuk u lidh me portin $httpPort\n");
}

socket_listen($httpSocket, 5);

echo "HTTP Monitoring Server\n";
echo "Server aktiv ne: http://$httpHost:$httpPort\n";

// Lexo kerkesen HTTP nga browser-i
function readRequest($clientSocket) {
    $request = "";

    while (true) {
        $data = socket_read($clientSocket, 1024);

        if ($data === false || $data === "") {
            break;
        }

        $request .= $data;
        
        // Nese arrihet fundi i header-it HTTP, ndalu
        if (strpos($request, "\r\n\r\n") !== false) {
            break;
        }
    }

    return $request;
}

?>