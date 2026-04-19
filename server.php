<?php

require 'config.php';
require 'file_manager.php';


$server_state = [
    "server_status" => "running",
    "active_connections" => 0,
    "total_messages" => 0,
    "messages_list" => [],
    "clients" => [] 
];

function saveState() {
    global $server_state;
    $data_to_save = $server_state;
    $data_to_save['clients'] = array_values($server_state['clients']);
    file_put_contents(__DIR__ . '/shared_data.json', json_encode($data_to_save, JSON_PRETTY_PRINT), LOCK_EX);
}

saveState();

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
$client_map = []; 

// Timeout Configuration
$idle_timeout_seconds = 60; // Disconnect users after 60 seconds of inactivity

while (true) {
    $read_sockets = $clients;
    $write_sockets = null;
    $except_sockets = null;
    
    // Timeout vendosur ne 2 sekonda per te kontrolluar idle users
    if (socket_select($read_sockets, $write_sockets, $except_sockets, 2) === false) {
        echo "Socket select failed: " . socket_strerror(socket_last_error()) . "\n";
        break;
    }
    
    // Lidhja e re
    if (in_array($master_socket, $read_sockets)) {
        $new_socket = socket_accept($master_socket); 
        
        if ($new_socket !== false) {
            socket_getpeername($new_socket, $client_ip, $client_port);
            $client_id = $client_ip . ":" . $client_port; 
            
            if ((count($clients) - 1) >= $max_clients) {
                $refuse_msg = "Server is full. Connection refused.\n";
                socket_write($new_socket, $refuse_msg, strlen($refuse_msg));
                socket_close($new_socket);
            } else {
                $clients[] = $new_socket;
    
                $key = array_search($new_socket, $clients, true); 
                $client_map[$key] = $client_id;
                
                // Shto klientin ne memorien e serverit
                $server_state['active_connections'] = count($clients) - 1;
                $server_state['clients'][$client_id] = [
                    'ip' => $client_id,
                    'type' => 'pending',
                    'messages' => 0,
                    'last_seen' => date('Y-m-d H:i:s'),
                    'status' => 'active'
                ];
                
                saveState();
                
                echo "New client connected from $client_id. Total: " . $server_state['active_connections'] . "\n";
                $welcome_msg = "Welcome to the TCP Server! You are connected.\n";
                socket_write($new_socket, $welcome_msg, strlen($welcome_msg));
            }
        }
        
        $key = array_search($master_socket, $read_sockets);
        unset($read_sockets[$key]);
    }

    // --- BONUS PRIORITY: Ndarja e Adminave dhe Userave ---
    $admin_sockets = [];
    $user_sockets = [];

    foreach ($read_sockets as $sock) {
        $key = array_search($sock, $clients, true);
        $client_id = isset($client_map[$key]) ? $client_map[$key] : "unknown";
        
        // Gjej rolin aktual të këtij socket-i në memorien e serverit
        $role = isset($server_state['clients'][$client_id]['type']) ? $server_state['clients'][$client_id]['type'] : 'pending';

        // Nese eshte admin, fute ne array-n e prioriteteve te larta
        if ($role === 'admin') {
            $admin_sockets[] = $sock;
        } else {
            $user_sockets[] = $sock;
        }
    }

    // Bashko listat: Adminat dalin te paret, pastaj Userat e thjeshte
    $prioritized_read_sockets = array_merge($admin_sockets, $user_sockets);
    // --------------------------------------------------------

    // menaxhimi i mesazheve dhe diskonektimeve sipas prioritetit
    foreach ($prioritized_read_sockets as $read_sock) {
        $data = @socket_read($read_sock, 2048);
        
        // Gjejme numrin e sakt te indeksit nga array $clients
        $key = array_search($read_sock, $clients, true);
        $client_id = isset($client_map[$key]) ? $client_map[$key] : "unknown";
        
        // diskonektimi
        if ($data === false || trim($data) == '') {
            unset($clients[$key]);
            unset($client_map[$key]);
            socket_close($read_sock);
            
            $server_state['active_connections'] = count($clients) - 1;
            if (isset($server_state['clients'][$client_id])) {
                $server_state['clients'][$client_id]['status'] = 'disconnected';
            }
            
            saveState();
            echo "Client $client_id disconnected.\n";
        }
        // mesazh i ri
       else {

  $commandLine = trim($data);

$msg_role = $server_state['clients'][$client_id]['type'] ?? 'user';


            $server_state['total_messages']++;

            $server_state['messages_list'][] = [
                'time' => date('Y-m-d H:i:s'),
                'ip' => $client_id,
                'role' => $msg_role,
                'text' => trim($data)
            ];

            if (count($server_state['messages_list']) > 50) {
                array_shift($server_state['messages_list']);
            }

           // Perditeso statistikat duke perfshire rolin
            if (isset($server_state['clients'][$client_id])) {
                $server_state['clients'][$client_id]['messages']++;
                $server_state['clients'][$client_id]['last_seen'] = date('Y-m-d H:i:s'); // Reset timeout
                $server_state['clients'][$client_id]['type'] = $msg_role; 
                $server_state['clients'][$client_id]['status'] = 'active';
            }

            saveState(); 
                
          $response = handleCommand($msg_role . "|" . $commandLine, $read_sock);
            socket_write($read_sock, $response, strlen($response));
        }
    }

    // --- IDLE CONNECTION MANAGEMENT (TIMEOUT) ---
    $current_time = time();
    foreach ($clients as $key => $client_sock) {
        // Mos e diskonekto master socket-in
        if ($client_sock === $master_socket) continue;

        $client_id = isset($client_map[$key]) ? $client_map[$key] : null;

        if ($client_id && isset($server_state['clients'][$client_id])) {
            $last_seen_str = $server_state['clients'][$client_id]['last_seen'];
            $last_seen_time = strtotime($last_seen_str);

            if (($current_time - $last_seen_time) > $idle_timeout_seconds) {
                echo "Timeout: Client $client_id disconnected due to inactivity.\n";

                $timeout_msg = "Connection closed due to inactivity (timeout).\n";
                @socket_write($client_sock, $timeout_msg, strlen($timeout_msg));

                socket_close($client_sock);
                unset($clients[$key]);
                unset($client_map[$key]);

                $server_state['active_connections'] = count($clients) - 1;
                $server_state['clients'][$client_id]['status'] = 'disconnected';
                saveState();
            }
        }
    }
}

socket_close($master_socket);
?>
