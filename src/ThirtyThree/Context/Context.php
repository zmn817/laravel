<?php

namespace ThirtyThree\Context;

use Ramsey\Uuid\Uuid;

class Context
{
    protected $id = null;

    public function __construct($app, $request)
    {
        if ($app->runningInConsole()) {
            $this->id = Uuid::uuid4();
        } else {
            $this->id = $request->header('X-Request-Id') ?: Uuid::uuid4();
        }
    }

    public function id()
    {
        return (string) $this->id;
    }
}
