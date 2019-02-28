<?php

return [
    // HTTP 请求的超时时间（秒）
    'timeout' => 5.0,

    // 默认发送配置
    'default' => [
        // 网关调用策略，默认：顺序调用
        'strategy' => Overtrue\EasySms\Strategies\OrderStrategy::class,

        // 默认可用的发送网关
        'gateways' => [
            'submail',
        ],
    ],
    // 可用的网关配置
    'gateways' => [
        'errorlog' => [
            'file' => storage_path('logs/sms.log'),
        ],
        'submail' => [
            'app_id' => '31838',
            'app_key' => '172f6c5d47c60f8d7bd99a254424cc71',
        ],
    ],
];
