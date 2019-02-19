<?php

namespace ThirtyThree\Wechat;

interface ProgramHandler
{
    public function __construct($message);

    public function handle();
}
