<?php

// Запуск демона: sudo php -f /var/www/html/app/cli/server.php &

use Service\SocketServer;

define('ROOT', dirname(dirname(__FILE__)));
require_once(ROOT . '/settings.php');
require_once(ROOT . '../../vendor/autoload.php');

set_time_limit(0);
ignore_user_abort(true);

try {
    $server = new SocketServer(ADDR, PORT, 10);
    $server->setOption(SOL_SOCKET, SO_REUSEADDR, 1);
} catch (Exception $e) {
    die($e->getMessage());
}

$server->go();
