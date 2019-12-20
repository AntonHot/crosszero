<?php

// Запуск сервера: sudo php -f /var/www/html/server.php &
// Запуск сервера: sudo php /var/www/html/server.php

require_once "app/settings.php";
require_once "function.php";

define('TEXT_MESSAGE', 100);
define('START_GAME', 101);
define('ADD_MEMBER', 102);
define('INVITE_MESSAGE', 103);

define('SERVER_USERNAME', '🤖');

set_time_limit(0);
ignore_user_abort(true);

if (!extension_loaded('sockets')) {
    die("Extension 'WebSockets' not loaded" . PHP_EOL);
}

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, 0, PORT);
socket_listen($socket, 5);
socket_set_nonblock($socket);

$webSockets = [];
$members = [];

while(true) {
    $newWebSocket = socket_accept($socket);
    if ($newWebSocket) {
        socket_set_nonblock($newWebSocket);
        $header = socket_read($newWebSocket, 1024);
        sendHeaders($header, $newWebSocket, ADDR, PORT);
        $webSockets[] = $newWebSocket;
    }
    foreach ($webSockets as $key => $webSocket) {
        $socketData = '';

        do {
            if (socket_recv($webSocket, $partData, 1024, 0) === 0) {
                unset($members[$webSocket]);
                unset($webSockets[$key]);
                $chatMessage = createChatMessage(SERVER_USERNAME, 'Кто-то покинул чат', $members);
                send($chatMessage, $webSockets);
                break;
            }
            $socketData .= $partData;
        } while ($partData);

        if ($socketData) {
            $socketMessage = unseal($socketData);
            echo $socketMessage . PHP_EOL;
            $messageObj = json_decode($socketMessage);
            print_r($messageObj);
            $codeMessage = $messageObj->code;
            switch ($codeMessage) {
                case TEXT_MESSAGE:
                    $chatMessage = createChatMessage($messageObj->username, $messageObj->text, $members);
                    send($chatMessage, $webSockets);
                    break;
                case START_GAME:
                    $chatMessage = createChatMessage(SERVER_USERNAME, $messageObj->username . ' ожидает игрока...', $members);
                    send($chatMessage, $webSockets);
                    break;
                case ADD_MEMBER:
                    $members[$webSocket] = [
                        'id' => $messageObj->phpsessid,
                        'username' => $messageObj->username
                    ];
                    $chatMessage = createChatMessage(SERVER_USERNAME, 'Вошел новый участник ' . $messageObj->username, $members);
                    send($chatMessage, $webSockets);
                    break;
                case INVITE_MESSAGE:
                    $invites[] = [
                        'user' => $messageObj->username,
                        'inviteto' => $messageObj->inviteto
                    ];
                    $members[$webSocket] = [
                        'id' => $messageObj->phpsessid,
                        'username' => $messageObj->username
                    ];
                    $chatMessage = createChatMessage(SERVER_USERNAME, 'Вошел новый участник ' . $messageObj->username, $members);
                    send($chatMessage, $webSockets);
                    break;
                default:
                    // code...
                    break;
            }
        }
    }
    usleep(100000 * 50);
}

socket_close($socket);
