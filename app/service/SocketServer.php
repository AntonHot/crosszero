<?php

namespace Service;

use Exception;

/**
 * Обеспечивает соединение с клиентами, получение и отправку сообщений
 * @method void setOption()
 * @method boolean accept()
 * @method string read()
 * @method boolean send()
 */
class SocketServer {
    
    private $address;
    private $port;
    private $socket;
    private $connections = [];
    
    const DELAY = 100000; // microsec
    
    /**
     * Создает сокет-сервер
     * @param string $address
     * @param integer $port
     * @param integer $backlog
     * @param boolean $is_nonblock
     * @param integer $domain
     * @param integer $type
     * @param integer $protocol
     * 
     * @return resource
     * 
     * @throws Exception
     */
    public function __construct(
        $address,
        $port,
        $backlog,
        $is_nonblock = true,
        $domain = AF_INET,
        $type = SOCK_STREAM,
        $protocol = SOL_TCP
    ) {
        if (!extension_loaded('sockets')) {
            throw new Exception('Extension WebSockets not loaded');
        }
        
        $this->address = $address;
        $this->port = $port;
        $this->socket = socket_create($domain, $type, $protocol);
        
        socket_bind($this->socket, $this->address, $this->port);
        socket_listen($this->socket, $backlog);
        if ($is_nonblock) {
            socket_set_nonblock($this->socket);
        }
        
        return $this->socket;
    }
    
    /**
     * Устанавливает опции серверу
     * @param integer $level
     * @param integer $optname
     * @param mixed $optval
     */
    public function setOption($level, $optname, $optval) {
        socket_set_option($this->socket, $level, $optname, $optval);
    }
    
    public function accept() {
        $newConnection = socket_accept($this->socket);
        if ($newConnection) {
            socket_set_nonblock($newConnection);
            $header = socket_read($newConnection, 1024);
            $this->handshake($header, $newConnection);
            $this->add($newConnection);
            return true;
        } else {
            return false;
        }
    }
    
    /** @todo Как можно гарантировать, что клиент получил ответ и подключение установилось? */
    
    private function handshake($header, $connection) {
        $headers = [];
        $lines = preg_split("/\r\n/", $header);
        foreach ($lines as $line) {
            $line = rtrim($line);
            if (preg_match("/\A(\S+): (.*)\z/", $line, $mathes)) {
                $headers[$mathes[1]] = $mathes[2];
            }
        }
        $sKey = base64_encode(pack('H*', sha1($headers['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $strHeaders = "HTTP/1.1 101 Switching Protocols \r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: $this->address\r\n" .
            "WebSocket-Location: ws://$this->address:$this->port/websocket/server.php\r\n" .
            "Sec-WebSocket-Accept: $sKey\r\n\r\n";
        socket_write($connection, $strHeaders, strlen($strHeaders));
    }
    
    public function read() {
        $messages = [];
        foreach ($this->connections as $connection) {
            $data = '';
            do {
                if (socket_recv($connection, $partData, 1024, 0) === 0) {
                    $this->remove($connection);
                    $messages[] = 'User is disconnected. Resource: ' . $connection; // TODO сделать системное сообщение
                    break;
                }
                $data .= $partData;
            } while ($partData);
            if ($data) {
                $socketMessage = $this->unseal($data);
                $messages[] = json_decode($socketMessage);
            }
        }
    }
    
    function send($messages) {
        foreach ($messages as $message) {
            $messageLength = strlen($message);
            foreach ($this->connections as $connection) {
                @socket_write($connection, $message, $messageLength);
            }
        }
        return true;
    }
    
    public function timeout() {
        usleep(self::DELAY);
    }
    
    private function add($connection) {
        $this->connections[] = $connection;
    }
    
    private function remove($connection) {
        $key = array_search($connection, $this->connections);
        if ($key) {
            unset($this->connections[$key]);
            return true;
        } else {
            return false;
        }
    }
    
    private function seal($data) {
        $b1 = 0x81;
        $length = strlen($data);
        $header = '';
        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length > 125 && $length < 65636) {
            $header = pack('CCn', $b1, 126, $length);
        } elseif ($length >= 65636) {
            $header = pack('CCNN', $b1, 127, $length);
        }
        return $header . $data;
    }
    
    private function unseal($data) {
        $length = ord($data[1]) & 127;
        if ($length == 126) {
            $mask = substr($data, 4, 4);
            $content = substr($data, 8);
        } elseif ($length == 127) {
            $mask = substr($data, 10, 4);
            $content = substr($data, 14);
        } else {
            $mask = substr($data, 2, 4);
            $content = substr($data, 6);
        }
        $socketStr = "";
    
        for ($i = 0; $i < strlen($content); $i++) {
            $socketStr .= $content[$i] ^ $mask[$i%4];
        }
        return $socketStr;
    }
}

/*

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

*/