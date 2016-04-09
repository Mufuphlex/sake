<?php

namespace Mufuphlex\Sake;

/**
 * Interface InputProcessorInterface
 * @package Mufuphlex\Sake
 */
interface InputProcessorInterface
{
    /**
     * @param string $input
     * @return mixed
     */
    public function process($input);
}