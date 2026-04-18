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
        $data = @socket_read($clientSocket, 1024);

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

// Funksion per me dergu pergjigje HTTP te browser-i
function sendResponse($clientSocket, $body, $statusCode = 200) {

    // Percakto tekstin e statusit sipas kodit
    if ($statusCode === 200) {
        $statusText = "200 OK"; 
    } elseif ($statusCode === 404) {
        $statusText = "404 Not Found"; 
    } elseif ($statusCode === 500) {
        $statusText = "500 Internal Server Error"; 
    } else {
        $statusText = "200 OK"; // default nese s'perputhet
    }

    // Gjatesia e body
    $length = strlen($body);

    // Ndertimi i pergjigjes HTTP
    $response  = "HTTP/1.1 $statusText\r\n"; // status line
    $response .= "Content-Type: application/json\r\n"; // tipi i te dhenave (JSON)
    $response .= "Content-Length: $length\r\n"; // madhesia e pergjigjes
    $response .= "Access-Control-Allow-Origin: *\r\n"; // lejon kerkesa nga cdo origin
    $response .= "Connection: close\r\n"; // mbyll lidhjen pas pergjigjes
    $response .= "\r\n"; // ndan header nga body
    $response .= $body; // permbajtja (data)

    // Dergon pergjigjen te klienti
    socket_write($clientSocket, $response);

    // Mbyll lidhjen me klientin
    socket_close($clientSocket);
}

// Numri total i kerkesave
$totalRequests = 0;

// Loop kryesor - pret kerkesa nga browser-i
while (true) {
    $clientSocket = socket_accept($httpSocket);
    if ($clientSocket === false) continue;

    socket_getpeername($clientSocket, $clientIp);

    // Timeout 5 sekonda
    socket_set_option($clientSocket, SOL_SOCKET, SO_RCVTIMEO, [
        'sec' => 5,
        'usec' => 0
    ]);

    $startTime = microtime(true);
    
    $request = readRequest($clientSocket);

    if (!$request) {
        socket_close($clientSocket);
        continue;
    }

    $totalRequests++;

    
    $line = strtok($request, "\r\n");

    
    if (preg_match('/^(\w+) (\S+)/', $line, $m)) {
        $method = $m[1]; //GET
        $path = $m[2]; // /stats
    } else {
        $method = 'UNKNOWN';
        $path = '/';
    }

    $cleanPath = parse_url($path, PHP_URL_PATH);

    // Log
    echo "[" . date("H:i:s") . "] Kerkese #$totalRequests nga IP: $clientIp\n";
    echo "-> " . $method . " " . $path . "\n";

    // Routing
    if ($method === 'GET' && $cleanPath === '/stats') {
        $body = handleStats($totalRequests);
        sendResponse($clientSocket, $body, 200);

    } elseif ($method === 'GET' && $cleanPath === '/') {
        $body = json_encode([
            "message" => "HTTP Monitoring Server",
            "endpoints" => [
                "/stats" => "Statistikat e serverit"
            ]
        ], JSON_PRETTY_PRINT);
        sendResponse($clientSocket, $body, 200);

    } else {
        $body = json_encode([
            "error" => "404 - Faqja nuk u gjet"
        ], JSON_PRETTY_PRINT);
        sendResponse($clientSocket, $body, 404);
    }

    // Koha e pergjigjes
    $ms = round((microtime(true) - $startTime) * 1000, 2);
    echo "-> Pergjigja u dergua ne: {$ms}ms\n";
    echo "---\n";

    //mbyllet ne sendResponse()
}

?>