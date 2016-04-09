<?php

namespace Mufuphlex\Tests\Sake;

use Mufuphlex\Sake\SocketListener;

class SocketListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testRun()
    {
        $socketListener = new SocketListener('127.0.0.1', 7080);
        $this->assertFalse($socketListener->isListening());
        $phpUnit = $this;

        $socketListener->setAfterListen(function(SocketListener $listener) use ($phpUnit){
            try {
                static $tick = -1;
                $tick++;

                if (!$tick) {
                    $phpUnit->assertTrue($listener->isListening());
                    return true;
                }

                $listener->stop();
                //            $this->assertFalse($listener->isListening());
                $phpUnit->assertTrue($listener->isListening());
            } catch (\Exception $e) {
                $phpUnit->fail('Achtung!!!');
            }
        });

        $socketListener->run();
    }
}