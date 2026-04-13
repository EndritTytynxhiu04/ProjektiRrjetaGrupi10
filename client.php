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
if ($response !== false) {
    echo "Server: $response\n";
}


echo "Enter role (admin/user): ";
$role = trim(fgets(STDIN));

if ($role !== "admin" && $role !== "user") {
    echo "Invalid role! Defaulting to user.\n";
    $role = "user";
}

echo "Role set to: $role\n";


while (true) {

    echo "\nEnter command (type 'exit' to quit): ";
    $input = trim(fgets(STDIN));

    if ($input === "exit") {
        echo "Disconnecting...\n";
        break;
    }

    if ($input === "") {
        continue;
    }

   
    $message = strtoupper($role) . ":" . $input . "\n";


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

    echo "Server: $response\n";
}


socket_close($socket);
echo "Disconnected.\n";

?>
