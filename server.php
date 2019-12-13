<?php

if (extension_loaded('sockets')) {
    echo "WebSockets OK\n";
}

set_time_limit(0);
error_reporting(-1);

// Create socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

define('ADDR', '84.201.185.4');
define('PORT', 889);
if (socket_bind($socket, 0, PORT)) {
    echo "Bind OK\n";
}

if (socket_listen($socket, 10)) {
    echo "Listen OK\n";
}

echo "Start server!\n";

while(true) {
    $accept = socket_accept($socket);
    if ($accept) {
        $header = socket_read($accept, 1024);
        sendHeaders($header, $accept, ADDR, PORT);
    }
}

socket_close($socket);

function sendHeaders($headersText, $newSocket, $host, $port) {
    $headers = [];
    $tmpLine = preg_split("/\r\n/", $headersText);
    foreach ($tmpLine as $line) {
        $line = rtrim($line);
        if (preg_match("/\A(\S+): (.*)\z/", $line, $mathes)) {
            $headers[$mathes[1]] = $mathes[2];
        }
    }
    $key = $headers['Sec-WebSocket-Key'];
    $sKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    // $sKey = base64_encode(pack('H*', sha1($key)));
    $strHeaders = "HTTP/1.1 101 Switching Protocols \r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "WebSocket-Origin: $host\r\n" .
        "WebSocket-Location: ws://$host:$port/websocket/server.php\r\n" .
        "Sec-WebSocket-Accept: $sKey\r\n\r\n";
    socket_write($newSocket, $strHeaders, strlen($strHeaders));
}
