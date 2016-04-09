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

    /**
     * @return bool
     */
    public function isListening();

    /**
     * @param  void
     * @return void
     */
    public function stop();
}