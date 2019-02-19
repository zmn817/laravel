<?php

namespace ThirtyThree\Yuntongxun;

use App\Models\CallRecord;
use App\Models\YuntongxunCallRecord;

class Call
{
    public static function shuangXiangWaiHu($caller, $callee)
    {
        if (is_array($caller)) {
            $caller_id = array_get($caller, 'user_id');
            $caller_number = array_get($caller, 'number');
        } else {
            $caller_id = null;
            $caller_number = $caller;
        }
        if (is_array($callee)) {
            $callee_id = array_get($callee, 'user_id');
            $callee_number = array_get($callee, 'number');
        } else {
            $callee_id = null;
            $callee_number = $callee;
        }
        if (empty($caller_number) || empty($callee_number)) {
            throw new CallException('缺少电话号码', 422);
        }
        if ($caller_number == $callee_number) {
            throw new CallException('主叫与被叫号码相同', 422);
        }

        $record = CallRecord::create([
            'type' => 3,
            'caller_id' => $caller_id,
            'caller_number' => $caller_number,
            'callee_id' => $callee_id,
            'callee_number' => $callee_number,
            'service' => 'yuntongxun',
        ]);

        $api = new Api();
        try {
            $res = $api->callCallback($caller_number, $callee_number, [
                'needRecord' => 1,
                'recordPoint' => 1,
                'cbContenType' => 'json',
                'hangupCdrUrl' => app('Dingo\Api\Routing\UrlGenerator')->version('v1')->route('yuntongxun.hangup-callback'),
            ]);

            YuntongxunCallRecord::create([
                'call_record_id' => $record->id,
                'caller_number' => $caller_number,
                'callee_number' => $callee_number,
                'call_sid' => $res['CallBack']['callSid'],
                'order_id' => $res['CallBack']['orderId'],
                'api_status' => 1,
            ]);

            $record->status = 2;
            $record->save();
        } catch (\Exception $e) {
            YuntongxunCallRecord::create([
                'call_record_id' => $record->id,
                'caller_number' => $caller_number,
                'callee_number' => $callee_number,
                'api_status' => 0,
                'api_error' => $e->getMessage(),
            ]);

            $record->status = 11;
            $record->save();

            throw new CallException('拨打失败', 500);
        }

        return $record;
    }
}
