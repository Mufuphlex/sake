<?php

namespace Mufuphlex\Sake;

/**
 * Class SocketRequester
 * @package Mufuphlex\Sake
 */
class SocketRequester extends SocketAbstract implements SocketRequesterInterface
{
    /** @var int */
    protected $socketReadLength = 32768;

    /**
     * @param string $data
     * @return string
     */
    public function request($data)
    {
        $this->socketCreate();
        $this->socketConnect();

        socket_write($this->socket, $data, strlen($data));
        $result = $this->socketRead($this->socket);
        socket_close($this->socket);

        return $result;
    }
}