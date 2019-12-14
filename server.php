<?php

// Запуск сервера: sudo php -f /var/www/html/server.php &

require_once "function.php";

set_time_limit(0);
error_reporting(-1);

define('ADDR', '84.201.185.53');
define('PORT', 889);

if (!extension_loaded('sockets')) {
    die("Extension 'WebSockets' not leaded" . PHP_EOL);
}

// Create socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, 0, PORT);
socket_listen($socket, 5);
socket_set_nonblock($socket);

echo "Start server!\n";

$webSockets = [];

while(true) {
    $newWebSocket = socket_accept($socket);
    if ($newWebSocket) {
        socket_set_nonblock($newWebSocket);
        $header = socket_read($newWebSocket, 1024);
        sendHeaders($header, $newWebSocket, ADDR, PORT);
        $webSockets[] = $newWebSocket;
        socket_getpeername($webSockets[0], $ipAddress);
        $connectionACK = newConnectionACK($ipAddress);
        send($connectionACK, $webSockets);
    }
    foreach ($webSockets as $webSocket) {
        echo $webSocket . PHP_EOL;
        socket_recv($webSocket, $socketData, 2048, 0);
        if ($socketData) {
            $socketMessage = unseal($socketData);
            echo $socketMessage . PHP_EOL;
            $messageObj = json_decode($socketMessage);
            $chatMessage = createChatMessage($messageObj->chat_user, $messageObj->chat_message);
            send($chatMessage, $webSockets);
        }
    }
    echo PHP_EOL;
    sleep(1);
}

socket_close($socket);
