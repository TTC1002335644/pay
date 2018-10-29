<?php

return [
    'alipay'=>[
        'debug'       => false, // 沙箱模式
        'app_id'      => '*****', // 应用ID
        'public_key'  => '*****',// 支付宝公钥(1行填写)
        'private_key' => '*****', // 支付宝私钥(1行填写)
        'notify_url'  => 'http://youwebsite/index.php/pay/Pay/alipay_notify', // 支付通知URL
    ],
    'wechat'=>[
        'appid'=>'****',//微信的appid
        'mch_id'=>'****',//商户号
        'api_key'=>'****',//秘钥
        'url'=>'https://api.mch.weixin.qq.com/pay/unifiedorder',//调用的地址
        'notify_url'=>'http://youwebsite/index.php/pay/Pay/wx_notify',//调用的地址
    ]
];
