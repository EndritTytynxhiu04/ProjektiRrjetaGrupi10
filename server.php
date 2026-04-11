<?php

require 'config.php';

//Krijimi i TCP/IP socket-it
$master_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($master_socket === false) {
    die("Socket creation failed: " . socket_strerror(socket_last_error()) . "\n");
}


if (!socket_bind($master_socket, $host, $port)) {
    die("Socket bind failed: " . socket_strerror(socket_last_error($master_socket)) . "\n");
}


if (!socket_listen($master_socket)) {
    die("Socket listen failed: " . socket_strerror(socket_last_error($master_socket)) . "\n");
}

echo "TCP Server started.\nListening on $host:$port...\n";


$clients = array($master_socket);

//Loopa e serverit kryesor
while (true) {
    $read_sockets = $clients;
    $write_sockets = null;
    $except_sockets = null;

    
    if (socket_select($read_sockets, $write_sockets, $except_sockets, null) === false) {
        echo "Socket select failed: " . socket_strerror(socket_last_error()) . "\n";
        break;
    }

    
    if (in_array($master_socket, $read_sockets)) {
        $new_socket = socket_accept($master_socket); 
        
        if ($new_socket !== false) {
            socket_getpeername($new_socket, $client_ip);
            
            
            
            if ((count($clients) - 1) >= $max_clients) {
                $refuse_msg = "Server is full. Connection refused.\n";
                socket_write($new_socket, $refuse_msg, strlen($refuse_msg));
                socket_close($new_socket);
                echo "Connection refused for $client_ip: Max clients reached.\n";
            } else {
                $clients[] = $new_socket;
                echo "New client connected from $client_ip. Total clients: " . (count($clients) - 1) . "\n";
                
                $welcome_msg = "Welcome to the TCP Server! You are connected.\n";
                socket_write($new_socket, $welcome_msg, strlen($welcome_msg));
            }
        }
        
        
        $key = array_search($master_socket, $read_sockets);
        unset($read_sockets[$key]);
    }

    //Menazhimi i diskonektimeve
    foreach ($read_sockets as $read_sock) {
        
        $data = @socket_read($read_sock, 1024, PHP_NORMAL_READ);
        
        if ($data === false || trim($data) == '') {
            
            socket_getpeername($read_sock, $client_ip);
            $key = array_search($read_sock, $clients);
            unset($clients[$key]);
            socket_close($read_sock);
            echo "Client $client_ip disconnected. Total clients: " . (count($clients) - 1) . "\n";
        }
    }
}


socket_close($master_socket);
?>