<?php

namespace app\alipay\model;

class Wechat {

    protected $wechatConfig;
    public function __construct(){
        $this->wechatConfig = config('wechat');
    }

    /**
     * 商品的微信支付
     *
     * */
    public function Pay($order_num,$id,$sum,$title){
        $sum = $sum * 100;//微信支付的支付的单位不一样，需要乘于100
        $wechatConfig = $this->wechatConfig;
        $data = [
            'appid'=>$wechatConfig['appid'],
            'mch_id'=>$wechatConfig['mch_id'],
            'nonce_str'=>$this->rand_code(),//生成随机数
            'body'=>$title,//支付标题
            'spbill_create_ip'=>$_SERVER['REMOTE_ADDR'],//ip地址
            'total_fee'=>$sum,//总价
            'out_trade_no'=>$order_num,
            'notify_url'=>$wechatConfig['notify_url'],//回调地址
            'trade_type'=>'APP'
        ];

        $url = $wechatConfig['url'];
        $res = $this->wx_pay($data,$url);
        if($res['mois'] == 1){
            //此处在订单生成支付记录(自己的操作)

        }
        return $res;
    }


    protected function wx_pay(array $data = array(),string $url){
        $appid = $data['appid'];
        $mch_id = $data['mch_id'];
        $nonce_str = $data['nonce_str'];
        $data['sign'] = Wechat::getSign($data);        //获取签名
        $xml = $this->ToXml($data);            //数组转xml
//        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $xmldata = $this->postXmlCurlWechat($xml,$url,30);
        if($xmldata){
            $re = $this->FromXml($xmldata);
            if ($re['return_code'] != 'SUCCESS') {
                return ['mois'=>3,'msg'=>'签名失败','test'=>$re];
            } else {
                //接收微信返回的数据,传给APP!
                $time = time();
                $arr = array(
                    'prepayid' => $re['prepay_id'],
                    'appid' => $appid,
                    'partnerid' => $mch_id,
                    'package' => 'Sign=WXPay',
                    'noncestr' => $nonce_str,
                    'timestamp' => $time,
                );
                //第二次生成签名
                $sign = $this->getSign($arr);
                $arr['sign'] = $sign;
                return ['mois'=>1,'msg'=>'签名成功','wres'=>$arr,'order_num'=>$data['out_trade_no'],'total'=>$data['total_fee'],'ares'=>''];
            }
        }else{
            return ['mois'=>2,'msg'=>'签名失败'];
        }
    }




    /*
     * 生成xml
     *
     * */
    public function ToXml($data = array()){
        if(!is_array($data) || count($data) <= 0){
            return false;
        }
        $xml = "<xml>";
        foreach ($data as $key => $val){
            if(is_numeric($val)){
                $xml .= "<".$key.">".$val."</".$key.">";
            }else{
                $xml .= "<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 生成签名，多次会用到
     *
     * */
    public function getSign($param){
        ksort($param);//将参数数组按照参数名ASCII码从小到大排序
        foreach ($param as $key =>$val){
            //参数值为空的去除掉
            if(!empty($val)){
                $newArr[] = $key.'='.$val;
            }
        }
        $stringA = implode('&',$newArr);//使用&符号链接参数
        $wechatConfig = $this->wechatConfig;;//这里是微信的api_key
        $stringSignTemp = $stringA.'&key='.$wechatConfig['api_key'];
        $stringSignTemp = md5($stringSignTemp);//进行md5加密
        $sign = strtoupper($stringSignTemp);//将所有的字符串转为大写
        return $sign;
    }

    /**
     *将xml转为数组
     *
     * */
    public function FromXml($xml):array {
        if(!$xml){
            return [];
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }


    /*
     * 生成随机字符串，用于微信支付
     *
     * */
    public function rand_code(){
        $str = '0123456789abcdefghijklmnof4451z3pntnwWGGGDSGasd8a8416q4qrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = str_shuffle($str);
        $str = substr($str,0,32);
        return $str;
    }


    protected function postXmlCurlWechat($xml,$url,$second = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        }
        //设置header
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        //传输文件
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            curl_close($ch);
            return false;
        }
    }


}