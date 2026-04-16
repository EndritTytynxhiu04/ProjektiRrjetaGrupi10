<?php

// Lidhja me stats.php
require_once __DIR__ . '/stats.php';

// Konfigurimi
$httpHost = "127.0.0.1";
$httpPort = 8080;

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

// Loopa e serverit HTTP per te degjuar kerkesat
while (true) {
    $clientSocket = socket_accept($httpSocket);
    
    if ($clientSocket !== false) {
        $request = readRequest($clientSocket);

        // Kontrollo nese kerkesa eshte per /stats
        if (strpos($request, "GET /stats") !== false) {
            
            // Merr te dhenat JSON nga stats.php
            $jsonResponse = handleStats();

            // Krijo pergjigjen HTTP
            $httpResponse = "HTTP/1.1 200 OK\r\n";
            $httpResponse .= "Content-Type: application/json\r\n";
            $httpResponse .= "Access-Control-Allow-Origin: *\r\n"; // Lejon kerkesat nga browseri
            $httpResponse .= "Connection: close\r\n\r\n";
            $httpResponse .= $jsonResponse;

            socket_write($clientSocket, $httpResponse, strlen($httpResponse));
        } else {
            // Nese kerkohet dicka tjeter
            $httpResponse = "HTTP/1.1 404 Not Found\r\n\r\nEndpoint not found. Përdor /stats";
            socket_write($clientSocket, $httpResponse, strlen($httpResponse));
        }
        
        socket_close($clientSocket);
    }
}

?>