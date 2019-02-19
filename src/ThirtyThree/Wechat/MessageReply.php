<?php

namespace ThirtyThree\Wechat;

use App\Models\WechatMaterial;
use EasyWeChat\Kernel\Messages\Link;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\Video;
use EasyWeChat\Kernel\Messages\Voice;
use EasyWeChat\Kernel\Messages\Location;
use EasyWeChat\Kernel\Messages\NewsItem;
use EasyWeChat\Kernel\Messages\Transfer;

class MessageReply
{
    public static function build($config, $message)
    {
        // 转发客服
        if ($config->type == 'kefu') {
            $transfer = new Transfer();
            if (! empty($config->content)) {
                $transfer->account($config->content);
            }

            return $transfer;
        }
        // 文字
        if ($config->type == 'message') {
            return new Text($config->content);
        }
        // 文字
        if ($config->type == 'link') {
            $content = json_decode($config->content, true);

            return new Link([
                'title' => array_get($content, 'title'),
                'description' => array_get($content, 'description'),
                'url' => array_get($content, 'url'),
                'thumb_url' => array_get($content, 'thumb_url'),
            ]);
        }
        // 文字
        if ($config->type == 'location') {
            $content = json_decode($config->content, true);

            return new Location([
                'latitude' => array_get($content, 'latitude'),
                'longitude' => array_get($content, 'longitude'),
                'scale' => array_get($content, 'scale'),
                'label' => array_get($content, 'label'),
                'precision' => array_get($content, 'precision'),
            ]);
        }
        // 小程序
        if ($config->type == 'program') {
            $event = 'text.'.$config->content;

            return ProgramReply::build($event, $message);
        }
        // 素材
        if (in_array($config->type, ['image', 'video', 'voice', 'news'])) {
            $material = WechatMaterial::find($config->content);
            if (empty($material)) {
                return null;
            }
            switch ($config->type) {
                case 'image':
                    return new Image($material->media_id);
                case 'video':
                    return new Video($material->media_id);
                case 'voice':
                    return new Voice($material->media_id);
                case 'news':
                    $items = [];
                    foreach ($material->extra as $news) {
                        $items[] = new NewsItem([
                            'title' => $news['title'],
                            'description' => array_get($news, 'description'),
                            'url' => array_get($news, 'url'),
                            'image' => array_get($news, 'thumb_url'),
                        ]);
                    }

                    return new News($items);
            }
        }

        return null;
    }
}
