<?php
// Main function 
// Perpunon komandat nga klientet
function handleCommand($data, $client_socket) {

$parts = explode("|", trim($data), 2);

if (count($parts) < 2) {
    return "Invalid request format\n";
}

$role = $parts[0];
$commandLine = trim($parts[1]);

$cmdParts = explode(" ", $commandLine);
$command = $cmdParts[0];

// Definon folderin baze ku ruhen files
$BASE_DIR = __DIR__ . "/files";

if (!file_exists($BASE_DIR)) {
    mkdir($BASE_DIR);
}

//Ketu kam vendosur rregullat e aksesit ne baze te roleve (RBAC)
$permissions = [
        "admin" => ["*"], // admin ka akses te plote
        "user"  => ["/list", "/read", "/download", "/search", "/info"]
    ];

    if (
        !isset($permissions[$role]) ||
        (!in_array("*", $permissions[$role]) &&
         !in_array($command, $permissions[$role]))
    ) {
        return "Permission denied (RBAC)\n";
    }

if ($role === "user") {
        usleep(200000); // delay i vogel per user duke i jap priority adminit
}

//Ketu kontrollojme komandat e dhena nga klientet dhe i perpunojme ato
switch ($command) {

    //Liston files ne server
    case "/list":
        $files = array_diff(scandir($BASE_DIR), ['.', '..']);
        return empty($files) ? "No files\n" : implode("\n", $files) . "\n";

    //Lexon permbajtjen e nje fajlli
    case "/read":
        if (!isset($cmdParts[1])) return "Missing filename\n";

        $filename = basename($cmdParts[1]);
        $file = $BASE_DIR . "/" . $filename;

        if (!file_exists($file)) return "File not found\n";

        return file_get_contents($file) . "\n";

    //Ngarkon file nga klienti ne server    
    case "/upload":
        if (!isset($cmdParts[1])) return "Missing filename\n";

        $filename = basename($cmdParts[1]);

        socket_write($client_socket, "READY\n");

        $content = socket_read($client_socket, 4096);

        file_put_contents($BASE_DIR . "/" . $filename, $content);

        return "File uploaded\n";

    //Dergon fajllin nga serveri tek klienti
    case "/download":
        if (!isset($cmdParts[1])) return "Missing filename\n";

         $filename = basename($cmdParts[1]);
        $file = $BASE_DIR . "/" . $filename;

        if (!file_exists($file)) return "File not found\n";

        return file_get_contents($file) . "\n";

    //Fshin file nga serveri
    case "/delete":
        if (!isset($cmdParts[1])) return "Missing filename\n";

        $filename = basename($cmdParts[1]);
        $file = $BASE_DIR . "/" . $filename;

        if (!file_exists($file)) return "File not found\n";

        unlink($file);

        return "File deleted\n";

    //Kerkon file sipas keyword
    case "/search":
        if (!isset($cmdParts[1])) return "Missing keyword\n";

        $keyword = $cmdParts[1];
        $files = scandir($BASE_DIR);

        $result = [];

        foreach ($files as $f) {
            if (strpos($f, $keyword) !== false) {
                $result[] = $f;
            }
        }

        return empty($result) ? "No matches\n" : implode("\n", $result) . "\n";
    
    //Jep info rreth nje file (size dhe data)
    case "/info":
        if (!isset($cmdParts[1])) return "Missing filename\n";

        $filename = basename($cmdParts[1]);
        $file = $BASE_DIR . "/" . $filename;

        if (!file_exists($file)) return "File not found\n";

        return "Size: " . filesize($file) .
                "\nCreated: " . date("Y-m-d H:i:s", filectime($file)) .
                "\nModified: " . date("Y-m-d H:i:s", filemtime($file)) . "\n";

    default:
        return "Unknown command\n";
  }
}