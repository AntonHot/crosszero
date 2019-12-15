<?php

// Запуск сервера: sudo php -f /var/www/html/server.php &
// Запуск сервера: sudo php /var/www/html/server.php

require_once "function.php";
require_once "settings.php";

set_time_limit(0);
error_reporting(-1);

if (!extension_loaded('sockets')) {
    die("Extension 'WebSockets' not loaded" . PHP_EOL);
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
        socket_getpeername($newWebSocket, $ipAddress);
        $connectionACK = createChatMessage(null, 'Robot', 'Вошел новый юзер');
        send($connectionACK, $webSockets);
    }
    foreach ($webSockets as $webSocket) {
        $socketData = '';
        while (socket_recv($webSocket, $partData, 1024, 0)) {
            $socketData .= $partData;
        }
        if ($socketData) {
            $socketMessage = unseal($socketData);
            echo $socketMessage . PHP_EOL;
            $messageObj = json_decode($socketMessage);
            $chatMessage = createChatMessage(null, $messageObj->username, $messageObj->text);
            send($chatMessage, $webSockets);
        }
    }
    usleep(100000);
}

socket_close($socket);
