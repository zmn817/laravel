<?php

namespace ThirtyThree\Wechat;

use App\Models\WechatCallbackEventConfig;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProgramReply
{
    public static function build($event, $message)
    {
        $config = WechatCallbackEventConfig::where('account', $message['ToUserName'])
            ->where('event', $event)
            ->first();
        if (empty($config)) {
            return null;
        }

        $jobClass = $config->job;
        if (! class_exists($jobClass)) {
            return null;
        }
        $job = new $jobClass($message);
        if (! $job instanceof ProgramHandler) {
            return null;
        }
        if ($job instanceof ShouldQueue) {
            dispatch(new $job($message));

            return null;
        }

        try {
            return $job->handle();
        } catch (\Exception $e) {
        }

        return null;
    }
}
