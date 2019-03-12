<?php

namespace ThirtyThree\Tests\Feature\Unit;

use ThirtyThree\Tests\TestCase;

class LogTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function testCanLog()
    {
        $res = \Log::info('abc');
        $this->assertTrue(true);
    }
}
