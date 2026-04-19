<?php

require 'config.php';

// krijimi i socketit per lidhje me serverin
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

if ($socket === false) {
    die("socket creation failed: " . socket_strerror(socket_last_error()) . "\n");
}

// lidhja me serverin tcp
if (!@socket_connect($socket, $host, $port)) {
    die("connection failed: " . socket_strerror(socket_last_error($socket)) . "\n");
}

echo "Connected to server ($host:$port)\n";

// leximi i mesazhit fillestar nga serveri
$response = @socket_read($socket, 1024);
if ($response) {
    echo "Server: $response\n";
}

// zgjedhja e rolit per user/admin
echo "Enter role (admin/user): ";
$role = trim(fgets(STDIN));

if ($role !== "admin" && $role !== "user") {
    echo "Invalid role! Defaulting to user.\n";
    $role = "user";
}

echo "Role set: $role\n";

// info per sesionin aktual te klientit
$sessionStart = date("Y-m-d H:i:s");
$totalCommands = 0;
$history = [];


function showHelp()
{
    echo "\n      COMMANDS      \n";
    echo "/list\n";
    echo "/read <file>\n";
    echo "/upload <file>\n";
    echo "/download <file>\n";
    echo "/delete <file>\n";
    echo "/search <keyword>\n";
    echo "/info <file>\n";
    echo "/help\n";
    echo "/history\n";
    echo "/status\n";
    echo "/clear\n";
    echo "exit\n";
    echo "                      \n";
}

showHelp();

// loop kryesor ku useri shkruan komandat
while (true) {

    echo "\n$role> ";
    $input = trim(fgets(STDIN));

    if ($input === "") continue;

    if ($input === "exit") {
        echo "Disconnecting...\n";
        break;
    }

    if ($input === "/help") {
        showHelp();
        continue;
    }

    if ($input === "/clear") {
        system('cls'); // per windows
        continue;
    }

    
    if ($input === "/history") {
        echo "\n--- HISTORY ---\n";
        foreach ($history as $i => $cmd) {
            echo ($i + 1) . ". $cmd\n";
        }
        continue;
    }

    // shfaq informata te sesionit
    if ($input === "/status") {
        echo "\n      SESSION      \n";
        echo "Server      : $host:$port\n";
        echo "Role        : $role\n";
        echo "Started     : $sessionStart\n";
        echo "Commands    : $totalCommands\n";
        echo "                     \n";
        continue;
    }

    // kontrolli i permissions per user
    $adminOnly = ["/upload", "/delete"];

    foreach ($adminOnly as $cmd) {
        if ($role === "user" && strpos($input, $cmd) === 0) {
            echo "Permission denied (admin only)\n";
            continue 2;
        }
    }

    // ruajtja e komandave ne history
    $history[] = $input;

$message = $role . "|" . $input . "\n";
    
    $sent = @socket_write($socket, $message, strlen($message));

    if ($sent === false) {
        echo "Send failed.\n";
        break;
    }

    // marrja e pergjigjes nga serveri
    $response = @socket_read($socket, 4096);

    if ($response === false || $response === "") {
        echo "Server disconnected or no response.\n";
        break;
    }

    echo "Server: " . $response;

    $totalCommands++;
}


socket_close($socket);

echo "Disconnected.\n";

?>
