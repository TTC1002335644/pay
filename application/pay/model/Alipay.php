<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/3
 * Time: 11:04
 */
namespace app\pay\model;

class Alipay{

     public function __construct(){
         $this->config = config('alipay');
         vendor('alipay.aop.AopClient');
         vendor('alipay.aop.request.AlipayTradeAppPayRequest');
    }

    /**
     *支付宝支付(商品支付)
     *@praam int $order_num 订单号
     *@praam int $id 用户id
     *@praam int $sum 支付金额（单位：元）
     *@praam int $title 支付标题
     * */
    public function Pay($order_num,$id,$sum,$title){
        $pay_array = [

        ];//支付带的额外参数
        $orderString = $this->Alipay($sum,$order_num,$title,$pay_array);
        if($orderString){
            //此处在订单生成支付记录

        }
        return ['mois'=>1,'msg'=>'生成成功','ares'=>$orderString,'order_num'=>$order_num,'wres'=>''];
    }


    /*
	**
	***********************************
	支付宝下单接口
	***********************************
	**
	*/
    protected function Alipay($price,$order_num,$title,$array = array()){
        $conf = $this->config;
        $aop = new \AopClient;
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = $conf['app_id'];
        $aop->rsaPrivateKey = $conf['private_key'];
        $aop->apiVersion  = '1.0';
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $conf['public_key'];//对应填写
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();

        $bizcontent = json_encode(array(
            'body'=>$title,
            'subject' => '支付的标题',//支付的标题，
            'out_trade_no' => $order_num,//订单号
            'timeout_express' => '1d',//過期時間（分钟）
            'total_amount' => $price,//金額最好能要保留小数点后两位数
            'product_code' => 'QUICK_MSECURITY_PAY',
            'array_data'=>$array//额外的参数
        ),JSON_UNESCAPED_UNICODE);

        $request->setNotifyUrl($conf['notify_url']);//你在应用那里设置的异步回调地址
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
        return htmlspecialchars_decode($response);
    }



}