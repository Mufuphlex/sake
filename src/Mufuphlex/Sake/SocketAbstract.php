<?php

namespace Mufuphlex\Sake;

/**
 * Class SocketAbstract
 * @package Mufuphlex\Sake
 */
abstract class SocketAbstract
{
    /** @var string */
    protected $address = '';

    /** @var int */
    protected $port = 0;

    /** @var int */
    protected $domain = AF_INET;

    /** @var int */
    protected $type = SOCK_STREAM;

    /** @var int */
    protected $protocol = SOL_TCP;

    /** @var resource */
    protected $socket;

    /** @var int */
    protected $socketReadLength = 4096;

    /** @var int */
    protected $socketReadType = PHP_BINARY_READ;

    /**
     * SocketAbstract constructor.
     * @param string $address
     * @param int $port
     */
    public function __construct($address, $port)
    {
        $port = (int)$port;

        if (!$port) {
            throw new \InvalidArgumentException('$port must be a valid port number');
        }

        $this->port = $port;

        if (!is_string($address)) {
            throw new \InvalidArgumentException('$address must be a string: either hostname or IP');
        }

        $this->address = $address;
    }

    /**
     * @param resource $socket
     * @return string
     */
    protected function getSocketError($socket = null)
    {
        if ($socket === null && $this->socket) {
            $socket = $this->socket;
        }

        return socket_strerror(socket_last_error($socket));
    }

    /**
     * @param void
     * @return void
     */
    protected function socketCreate()
    {
        $this->socket = socket_create($this->domain, $this->type, $this->protocol);

        if ($this->socket === false) {
            throw new Exception('socket_create() error: ' . $this->getSocketError());
        }
    }

    /**
     * @param void
     * @return void
     */
    protected function socketBind()
    {
        if (socket_bind($this->socket, $this->address, $this->port) === false) {
            throw new Exception('socket_bind() error: ' . $this->getSocketError());
        }
    }

    /**
     * @param void
     * @return void
     */
    protected function socketListen()
    {
        if (socket_listen($this->socket, 5) === false) {
            throw new Exception('socket_listen() error: ' . $this->getSocketError());
        }
    }

    /**
     * @param void
     * @return void
     */
    protected function socketConnect()
    {
        if (socket_connect($this->socket, $this->address, $this->port) === false) {
            throw new Exception('socket_connect() error: ' . $this->getSocketError());
        }
    }

    /**
     * @param resource $socket
     * @return false|string
     */
    protected function socketRead($socket)
    {
        return socket_read($socket, $this->socketReadLength, $this->socketReadType);
    }
}