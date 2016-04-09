<?php

namespace Mufuphlex\Sake;

/**
 * Interface SocketListenerInterface
 * @package Mufuphlex\Sake
 */
interface SocketListenerInterface
{
    /**
     * @param void
     * @return bool
     */
    public function run();
}