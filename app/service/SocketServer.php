<?php

namespace Service;

use Exception;

/**
 * Сервер вебсокетов
 * 
 * @method setOption()      Настройка сервера
 * @method accept()         Принимает новое соединение
 * @method read()           Получает сообщение из сокета
 * @method send()           Отправляет массив сообщений
 * @method timeout()        Держит паузу
 * 
 * @todo Добавить синглтон для сервера
 */
class SocketServer {
    
    /** @var string */
    private $address;
    
    /** @var integer */
    private $port;
    
    /** @var resource */
    private $socket;
    
    /** @var boolean */
    private $isRun = false;
    
    /** @var array */
    private $connections = [];
    
    const DELAY = 100000; // microsec
    const SYSTEM_MESSAGE = 100;
    const TEXT_MESSAGE = 200;
    const GAME_MESSAGE = 201;
    
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
        
        socket_bind($this->socket, '0.0.0.0', $this->port);
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
    
    public function go() {
        $this->isRun = true;
        while($this->isRun) {
            // New connection
            $this->accept();
            
            // Processing inbox
            $this->read();
            
            // Sending outbox
            if (!empty($this->connections)) {
                $this->send();
            }
            
            $this->timeout();
        }
    }
    
    public function accept() {
        $resource = socket_accept($this->socket);
        if ($resource) {
            socket_set_nonblock($resource);
            $connection = new Connection($resource);
            $header = socket_read($connection->resource, 1024);
            $this->handshake($header, $connection->resource);
            $this->addConnection($connection);
        }
    }
    
    /** @todo Как можно гарантировать, что клиент получил ответ и подключение установилось? */
    private function handshake($header, &$resource) {
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
        socket_write($resource, $strHeaders, strlen($strHeaders));
    }
    
    /**
     * 1. Получает все данные из соединений
     * 2. Раскодирует
     * 3. Сохраняет все в один массив
     * @return array
    */
    public function read() {
        foreach ($this->connections as &$connection) {
            $data = '';
            do {
                if (socket_recv($connection->resource, $partData, 1024, 0) === 0) {
                    $this->removeConnection($connection);
                    break;
                }
                $data .= $partData;
            } while ($partData);

            if ($data) {
                $socketMessage = $this->unseal($data);
                $message = json_decode($socketMessage);
                switch ($message->type) {
                    case self::SYSTEM_MESSAGE:
                        $connection->id = $message->phpsessid;
                        $connection->name = $message->from;
                        break;
                    case self::TEXT_MESSAGE:
                        $connection->messages[] = [
                            'to' => $message->to,
                            'text' => $message->text
                        ];
                        break;
                    default:
                        throw new Exception('Unknown type message');
                        break;
                }
            }
        }
    }
    
    public function send() {
        foreach ($this->connections as $connection) {
            foreach ($connection->messages as $key => $message) {
                if ($message['to'] === 'all') {
                    $string = $this->seal(json_encode([
                        'from' => $connection->name,
                        'text' => $message['text']
                    ]));
                    $length = strlen($string);
                    unset($connection->messages[$key]);
                    foreach ($this->connections as $connect) {
                        @socket_write($connect->resource, $string, $length);
                    }
                } else {

                }
            }
        }
    }
    
    public function timeout() {
        usleep(self::DELAY);
    }
    
    private function addConnection($connection) {
        $this->connections[] = $connection;
    }
    
    private function removeConnection($connection) {
        $key = array_search($connection, $this->connections, true);
        if (isset($this->connections[$key])) {
            unset($this->connections[$key]);
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
