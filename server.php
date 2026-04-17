<?php

require 'config.php';
require 'file_manager.php';

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

// Funksioni i ri per perditesimin e lidhjeve aktive
function updateActiveConnections($count) {
    $file = __DIR__ . '/shared_data.json';
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    
    $data['server_status'] = 'running';
    $data['active_connections'] = $count;
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Funksioni i modifikuar per shkrimin e statistikave ne shared_data.json
function logMessage($client_ip, $message, $role = 'user') {
    $file = __DIR__ . '/shared_data.json';
    
    // Lexon te dhenat ose krijon strukturen default
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

    // Sigurohemi qe celesat baze ekzistojne
    if (!isset($data['total_messages'])) $data['total_messages'] = 0;
    if (!isset($data['clients'])) $data['clients'] = [];

    // Rritim numrin total te mesazheve
    $data['total_messages']++;

    // Gjejme nese klienti ekziston ne liste
    $clientFound = false;
    foreach ($data['clients'] as &$client) {
        if ($client['ip'] === $client_ip) {
            $client['messages']++;
            $client['last_seen'] = date('Y-m-d H:i:s');
            $client['status'] = 'active';
            $client['type'] = $role; // Perditeso rolin nese ka ndryshuar
            $clientFound = true;
            break;
        }
    }

    // Nese eshte klient i ri, shtoje ne array
    if (!$clientFound) {
        $data['clients'][] = [
            'ip' => $client_ip,
            'type' => $role,
            'messages' => 1,
            'last_seen' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];
    }

    // E ben save ne json
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

$clients = array($master_socket);

// Inicializimi i numrit te lidhjeve aktive ne fillim
updateActiveConnections(0);

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
                $active_clients_count = count($clients) - 1;
                echo "New client connected from $client_ip. Total clients: " . $active_clients_count . "\n";
                
                // HOOK 1: Perditeso numrin e lidhjeve pas nje konektimi
                updateActiveConnections($active_clients_count);
                
                $welcome_msg = "Welcome to the TCP Server! You are connected.\n";
                socket_write($new_socket, $welcome_msg, strlen($welcome_msg));
            }
        }
        
        
        $key = array_search($master_socket, $read_sockets);
        unset($read_sockets[$key]);
    }

    //Menazhimi i diskonektimeve
    foreach ($read_sockets as $read_sock) {
        
        $data = @socket_read($read_sock, 2048);
        
        if ($data === false || trim($data) == '') {
            
            socket_getpeername($read_sock, $client_ip);
            $key = array_search($read_sock, $clients);
            unset($clients[$key]);
            socket_close($read_sock);
            
            $active_clients_count = count($clients) - 1;
            echo "Client $client_ip disconnected. Total clients: " . $active_clients_count . "\n";
            
            // HOOK 2: Perditeso numrin e lidhjeve pas nje diskonektimi
            updateActiveConnections($active_clients_count);
        }
        else {
            socket_getpeername($read_sock, $client_ip);
            
            // Ndajme rolin (admin/user) nga mesazhi per t'ia derguar statistikes
            $parts = explode("|", trim($data), 2);
            $role = (count($parts) >= 2) ? $parts[0] : 'user';

            // Shkruaj te dhenat ne shared_data.json
            logMessage($client_ip, $data, $role); 
                
            // Process the command
            $response = handleCommand($data, $read_sock);
            socket_write($read_sock, $response, strlen($response));
        }
    }
}


socket_close($master_socket);
?>