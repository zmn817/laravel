<?php

namespace ThirtyThree\Wechat;

class HelloWorldMessage implements ProgramHandler
{
    public function __construct($message)
    {
    }

    public function handle()
    {
        return 'hello world';
    }
}
