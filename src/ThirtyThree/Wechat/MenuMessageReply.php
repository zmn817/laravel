<?php

namespace ThirtyThree\Wechat;

use App\Models\WechatMenuItem;

class MenuMessageReply
{
    public static function build($id, $message)
    {
        $menu = WechatMenuItem::find($id);
        if (empty($menu)) {
            return null;
        }

        if (empty($menu->params['message'])) {
            return null;
        }

        return $menu->params['message'];
    }
}
