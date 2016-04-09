<?php

namespace Mufuphlex\Sake;

/**
 * Interface SocketRequesterInterface
 * @package Mufuphlex\Sake
 */
interface SocketRequesterInterface
{
    /**
     * @param string $data
     * @return string
     */
    public function request($data);
}