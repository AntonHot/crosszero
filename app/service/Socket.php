<?php

namespace Service;

class SocketServer {

    public $mainSocket;
    public $clientSockets = [];
    private $host;
    private $port;
    const DELAY = 100000; // microsec

    public function __construct($host, $port) {
        if (!extension_loaded('sockets')) {
            die("Extension 'WebSockets' not loaded" . PHP_EOL);
        }
        $this->host = $host;
        $this->port = $port;
        $this->mainSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->mainSocket, SOLmainSocket, SO_REUSEADDR, 1);
        socket_bind($this->mainSocket, 0, $this->port);
        socket_listen($this->mainSocket, 5);
        socket_set_nonblock($this->mainSocket);
    }

    public function __destruct() {
        socket_close($this->mainSocket);
    }

    public function run() {
        while(true) {
            $newClientSocket = socket_accept($this->mainSocket);
            if ($newClientSocket) {
                socket_set_nonblock($newClientSocket);
                $header = socket_read($newClientSocket, 1024);
                $this->sendHeaders($header, $newClientSocket, ADDR, PORT);
                $this->clientSockets[] = $newClientSocket;
                $message = $this->createChatMessage(null, '🤖', 'Вошел новый юзер');
                $this->sendToAll($message);
            }
            foreach ($this->clientSockets as $socket) {
                $dataFromSocket = '';
                while (socket_recv($socket, $partData, 1024, 0)) {
                    $dataFromSocket .= $partData;
                }
                if (!empty($dataFromSocket)) {
                    $messageFromClient = $this->unseal($dataFromSocket);
                    $messageObj = json_decode($messageFromClient);
                    $message = $this->createChatMessage(null, $messageObj->username, $messageObj->text);
                    $this->sendToAll($message);
                }
            }
            usleep($this->DELAY);
        }
    }

    //// ЗАКОНЧИЛ ТУТ
    private function sendHeaders($headersText, $newSocket) {
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
        $strHeaders = "HTTP/1.1 101 Switching Protocols \r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: $host\r\n" .
            "WebSocket-Location: ws://$host:$port/websocket/server.php\r\n" .
            "Sec-WebSocket-Accept: $sKey\r\n\r\n";
        socket_write($newSocket, $strHeaders, strlen($strHeaders));
    }

    function sendToAll($message) {
        $messageLength = strlen($message);
        foreach ($this->clientSockets as $clientSocket) {
            @socket_write($clientSocket, $message, $messageLength);
        }
        return true;
    }
    
    function createChatMessage($type, $username, $textMessage) {
        $message = [
            'type' => $type,
            'user' => $username,
            'message' => $textMessage
        ];
        return seal(json_encode($message));
    }
    
    public function seal($socketData) {
        $b1 = 0x81;
        $length = strlen($socketData);
        $header = '';
        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length > 125 && $length < 65636) {
            $header = pack('CCn', $b1, 126, $length);
        } elseif ($length >= 65636) {
            $header = pack('CCNN', $b1, 127, $length);
        }
        return $header . $socketData;
    }
    
    public function unseal($socketData) {
        $length = ord($socketData[1]) & 127;
        if ($length == 126) {
            $mask = substr($socketData, 4, 4);
            $data = substr($socketData, 8);
        } elseif ($length == 127) {
            $mask = substr($socketData, 10, 4);
            $data = substr($socketData, 14);
        } else {
            $mask = substr($socketData, 2, 4);
            $data = substr($socketData, 6);
        }
        $socketStr = "";
    
        for ($i = 0; $i < strlen($data); $i++) {
            $socketStr .= $data[$i] ^ $mask[$i%4];
        }
        return $socketStr;
    }
}
