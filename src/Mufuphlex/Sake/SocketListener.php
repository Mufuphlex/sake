<?php

namespace Mufuphlex\Sake;

/**
 * Class SocketListener
 * @package Mufuphlex\Sake
 */
class SocketListener
{
    /** @var string */
    private $address = '';

    /** @var string */
    private $port;

    /** @var resource */
    private $socket;

    /** @var int */
    private $socketReadLength = 4096;

    /** @var int */
    private $socketReadType = PHP_BINARY_READ;

    /** @var int */
    private $domain = AF_INET;

    /** @var int */
    private $type = SOCK_STREAM;

    /** @var int */
    private $protocol = SOL_TCP;

    /** @var array */
    private $readArray = array();

    /** @var array */
    private $writeArray = array();

    /** @var array */
    private $exceptArray = array();

    /** @var array */
    private $socketClients = array();

    /** @var int */
    private $clientCounter = 0;

    /** @var int */
    private $tvSec = 5;

    /** @var InputProcessorInterface */
    private $inputProcessor;

    /**
     * Listener constructor.
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
     * @param InputProcessorInterface $inputProcessor
     * @return $this
     */
    public function setInputProcessor(InputProcessorInterface $inputProcessor)
    {
        if ($this->inputProcessor !== null) {
            throw new Exception('Can not redefine $inputProcessor');
        }

        $this->inputProcessor = $inputProcessor;
        return $this;
    }

    /**
     * @return bool
     */
    public function run()
    {
        try {
            $this->init();
        } catch (\Exception $e) {
            $this->log('Init error: ' . $e->__toString());
            return false;
        }

        $this->log('Listening..');

        try {
            do {
                if (!$this->listen()) {
                    $this->log('Stop listening due to error');
                    break;
                }
            } while (true);
        } catch (\Exception $e) {
            $this->log($e->__toString());
        }

        $this->log('Close');
        socket_close($this->socket);
        return false;
    }

    /**
     * @param void
     * @return void
     */
    private function init()
    {
        if (($this->socket = socket_create($this->domain, $this->type, $this->protocol)) === false) {
            throw new Exception('socket_create() error: ' . socket_strerror(socket_last_error()));
        }

        if (socket_bind($this->socket, $this->address, $this->port) === false) {
            throw new Exception('socket_bind() error: ' . socket_strerror(socket_last_error($this->socket)));
        }

        if (socket_listen($this->socket, 5) === false) {
            throw new Exception('socket_listen() error: ' . socket_strerror(socket_last_error($this->socket)));
        }
    }

    /**
     * @param string $msg
     * @return void
     */
    private function log($msg)
    {
        echo "\n" . date('Y-m-d H:i:s') . "\t" . $msg;
    }

    /**
     * @return bool
     */
    private function listen()
    {
        $this->readArray = ($this->socketClients ?: array($this->socket));

        if (socket_select($this->readArray, $this->writeArray, $this->exceptArray, $this->tvSec) < 1) {
            return true;
        }

        try {
            $this->putSocketMessageIfAny();
        } catch (Exception $e) {
            $this->log($e->__toString());
            return false;
        }

        $this->processSocketClients();
        return true;
    }

    /**
     * @param  void
     * @return void
     */
    private function putSocketMessageIfAny()
    {
        if (!in_array($this->socket, $this->readArray)) {
            return;
        }

        if (($socketMessage = socket_accept($this->socket)) === false) {
            throw new Exception('socket_accept() error: ' . socket_strerror(socket_last_error($this->socket)));
        }

        $this->socketClients[++$this->clientCounter] = $socketMessage;
    }

    /**
     * @param  void
     * @return void
     */
    private function processSocketClients()
    {
        foreach ($this->socketClients as $key => $client) {
            if (!in_array($client, $this->readArray)) {
                continue;
            }

            if (false === ($buffer = socket_read($client, $this->socketReadLength, $this->socketReadType))) {
                $this->log('socket_read() error: ' . socket_strerror(socket_last_error($client)));
                continue;
            }

            if (!$buffer = trim($buffer, " \t\r\n")) {
                continue;
            }

            $ts = microtime(true);
            $result = $this->processInput($buffer);

            socket_write($client, $result, strlen($result));
            socket_close($client);
            unset($this->socketClients[$key]);
            $this->log(' processed: ' . round(microtime(true) - $ts, 6) . ' / ' . number_format(memory_get_usage(), null, null, ' '));
        }
    }

    /**
     * @param string $input
     * @return mixed
     */
    private function processInput($input)
    {
        return $this->inputProcessor->process($input);
    }
}