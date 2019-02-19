<?php

namespace ThirtyThree\Context;

class LoggingFormatter
{
    /**
     * 为日志添加上下文ID.
     *
     * @param \Illuminate\Log\Logger $logger
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(function ($record) {
                $record['extra']['contextId'] = app('context')->id();

                return $record;
            });
        }
    }
}
