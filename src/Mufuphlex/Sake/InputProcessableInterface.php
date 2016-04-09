<?php

namespace Mufuphlex\Sake;

/**
 * Interface InputProcessableInterface
 * @package Mufuphlex\Sake
 */
interface InputProcessableInterface
{
    /**
     * @param InputProcessorInterface $inputProcessor
     * @return mixed
     */
    public function setInputProcessor(InputProcessorInterface $inputProcessor);
}