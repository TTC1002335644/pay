<?php

namespace app\apy\controller;
use app\alipay\model\Wechat;
use app\pay\model\Alipay;
use app\pay\model\Wechatpay;

class Pay{
    public function __construct(){
        vendor('alipay.aop.AopClient');
    }

    public function Pay(){

        $order_num = trim(input('post.order_num'));//订单号
        $type = trim(input('post.type'));//支付类型
        $id = trim(input('post.id'));//用户id

        if(empty($order_num)){
            return json(['mois'=>7,'msg'=>'参数异常']);
        }
        $title = '购买商品支付';
        $sum = 180;//总价（单位：元）
        if(empty($sum)){
            return json(['mois'=>2,'msg'=>'没有该订单']);
        }
        if($type == 1){
            $Alipay = new Alipay();
            $res = $Alipay->Pay($order_num,$id,$sum,$title);
        }else{
            $Wxpay = new Wechat();
            $res = $Wxpay->Pay($order_num,$id,$sum,$title);
        }
        return json($res);
    }


    /*支付宝接受的回调*/
    public function alipay_notify(){
        $conf = config('alipay');
        $data = input();
        $aop = new \AopClient;
        $aop->alipayrsaPublicKey = $conf['public_key'];
        $flag = $aop->rsaCheckV1($data, NULL, "RSA2");
        if($flag){
            //这里可以做一下你自己的订单逻辑处理

            echo 'success';//这个必须返回给支付宝，响应个支付宝，
        } else {
            //验证失败
            echo "fail";
        }
    }


    /**
     * 微信的回调接收地址
     *
     * */
    public function wx_notify(){
        $WechatModel = new Wechat();
        //接收微信返回的数据数据,返回的xml格式
        $xmlData = file_get_contents('php://input');
        //将xml格式转换为数组
        $data = $WechatModel->FromXml($xmlData);
        //用日志记录检查数据是否接受成功，验证成功一次之后，可删除。
//        $file = fopen('./log.txt', 'a+');
//        fwrite($file,var_export($data,true));
        //为了防止假数据，验证签名是否和返回的一样。
        //记录一下，返回回来的签名，生成签名的时候，必须剔除sign字段。
        $sign = $data['sign'];
        unset($data['sign']);
        if($sign == $WechatModel->getSign($data)){
            //签名验证成功后，判断返回微信返回的
            if ($data['result_code'] == 'SUCCESS') {
                //根据返回的订单号做业务逻辑

                //处理完成之后，告诉微信成功结果！
                    echo '<xml>
              <return_code><![CDATA[SUCCESS]]></return_code>
              <return_msg><![CDATA[OK]]></return_msg>
              </xml>';exit();
            }
            //支付失败，输出错误信息
            else{
//                $file = fopen('./log.txt', 'a+');
//                fwrite($file,"错误信息：".$data['return_msg'].date("Y-m-d H:i:s"),time()."\r\n");
            }
        }
        else{
//            $file = fopen('./log.txt', 'a+');
//            fwrite($file,"错误信息：签名验证失败".date("Y-m-d H:i:s"),time()."\r\n");
        }
    }
}