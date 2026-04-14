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

?>