<?php

namespace Mufuphlex\Sake;

/**
 * Class SocketListener
 * @package Mufuphlex\Sake
 */
class SocketListener extends SocketAbstract implements SocketListenerInterface, InputProcessableInterface
{
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
    private $tvSec = 2;

    /** @var InputProcessorInterface */
    private $inputProcessor;

    /** @var bool */
    private $listening = false;

    /** @var callable */
    private $onAfterInit;

    /** @var callable */
    private $onAfterListen;

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
            ($func = $this->onAfterInit) && $func($this);
        } catch (\Exception $e) {
            $this->log('Init error: ' . $e->__toString());
            return false;
        }

        $this->log('Listening..');
        $this->listening = true;

        try {
            do {
                if (!$this->listen()) {
                    $this->log('Stop listening due to error');
                    $this->listening = false;
                    break;
                }

                ($func = $this->onAfterListen) && $func($this);
            } while ($this->listening);
        } catch (\Exception $e) {
            $this->log($e->__toString());
            $this->listening = false;
        }

        $this->log("Close\n");
        socket_close($this->socket);
        return false;
    }

    /**
     *
     */
    public function stop()
    {
        $this->listening = false;
        //@TODO Notify consumers
    }

    /**
     * @return bool
     */
    public function isListening()
    {
        return $this->listening;
    }

    public function setAfterInit($callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException();
        }

        $this->onAfterInit = $callable;
    }

    public function setAfterListen($callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException();
        }

        $this->onAfterListen = $callable;
    }

    /**
     * @param void
     * @return void
     */
    private function init()
    {
        $this->socketCreate();
        $this->socketBind();
        $this->socketListen();
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
        $this->log(__FUNCTION__);
        $this->readArray = ($this->socketClients ?: array($this->socket));
        $ts = -microtime(true);

        if (socket_select($this->readArray, $this->writeArray, $this->exceptArray, $this->tvSec) < 1) {
            $this->log("\t\tfinish socket select: ".(microtime(true)+$ts));
            return true;
        }

        try {
            $ts = -microtime(true);
            $this->putSocketMessageIfAny();
            $this->log("\t\tfinish put: ".(microtime(true)+$ts));
        } catch (Exception $e) {
            $this->log($e->__toString());
            return false;
        }

        $ts = -microtime(true);
        $this->processSocketClients();
        $this->log("\t\tfinish clients: ".(microtime(true)+$ts));
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
            throw new Exception('socket_accept() error: ' . $this->getSocketError());
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

            $buffer = $this->socketRead($client);

            if (false === $buffer) {
                $this->log('socket_read() error: ' . $this->getSocketError($client));
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