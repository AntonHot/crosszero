<?php

// Запуск демона: sudo php -f /var/www/html/app/cli/server.php &

use Service\{SocketServer, Chat};

require_once "app/settings.php";
set_time_limit(0);
ignore_user_abort(true);

try {
    $server = new SocketServer(ADDR, PORT, 10);
    $server->setOption(SOL_SOCKET, SO_REUSEADDR, 1);
    $chat = new Chat();
} catch (Exception $e) {
    die($e->getMessage());
}

while(true) {
    // New connection
    $server->accept();
    
    // Processing inbox
    $inboxMessages = $server->read();
    $outboxMessages = [];
    foreach ($inboxMessages as $message) {
        $outboxMessages[] = $chat->handle($message);
    }
    
    // Sending outbox
    $server->send($outboxMessages);
    
    // Waiting
    $server->timeout();
}
