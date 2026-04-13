<?php

require 'config.php';

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    die("Socket creation failed: " . socket_strerror(socket_last_error()) . "\n");
}

if (!@socket_connect($socket, $host, $port)) {
    die("Connection failed: " . socket_strerror(socket_last_error($socket)) . "\n");
}

echo "Connected to server ($host:$port)\n";

$response = @socket_read($socket, 1024);
if ($response) {
    echo "Server: $response\n";
    
echo "Enter role (admin/user): ";
$role = trim(fgets(STDIN));

if ($role !== "admin" && $role !== "user") {
    echo "Invalid role! Defaulting to user.\n";
    $role = "user";
}

echo "Role set to: $role\n";


echo "\n===== AVAILABLE COMMANDS =====\n";
echo "/list\n";
echo "/read <file>\n";
echo "/upload <file>\n";
echo "/download <file>\n";
echo "/delete <file>\n";
echo "/search <keyword>\n";
echo "/info <file>\n";
echo "Type 'exit' to quit\n";
echo "==============================\n";


while (true) {

    echo "\nEnter command: ";
    $input = trim(fgets(STDIN));

    if ($input === "exit") {
        echo "Disconnecting...\n";
        break;
    }

    if ($input === "") {
        continue;
    }

  
    $adminOnly = ["/delete", "/upload"];

    foreach ($adminOnly as $cmd) {
        if ($role === "user" && strpos($input, $cmd) === 0) {
            echo "Permission denied! USER cannot use this command.\n";
            continue 2;
        }
    }

 
    $message = $role . "|" . $input . "\n";

    $sent = @socket_write($socket, $message, strlen($message));

    if ($sent === false) {
        echo "Send failed: " . socket_strerror(socket_last_error($socket)) . "\n";
        break;
    }

    $response = @socket_read($socket, 1024);

    if ($response === false || $response === "") {
        echo "Server disconnected or no response.\n";
        break;
    }

    echo "Server: " . $response;
}


socket_close($socket);
echo "Disconnected.\n";

?>
