<?php namespace service\core\wcpay;

/*
 *  网页端调起支付API
 *
 *  文档地址：
 *  https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=7_7&index=6
 */

class JSAPI extends AbstractCaller
{

    # 创建接口参数
    public function generateApiParam($package)
    {
        # 组合参数
        $param = [
            'appId'     => $this->app_id,
            'timeStamp' => time(),
            'nonceStr'  => $this->_generate_nonce_str(),
            'package'   => $package,
            'signType'  => 'MD5',
        ];

        # 生成签名
        $signature = $this->_create_signature($param);

        # 返回完整参数
        $param['paySign'] = $signature;
        return $param;
    }


    ############################################################
    # 内部调用操作

    # 生成随机字符串
    private function _generate_nonce_str($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    # 生成签名
    private function _create_signature($param)
    {
        # 对参数排序
        ksort($param);

        # 将参数组合为字符串
        $string = $this->_make_query_string($param, ['sign']);

        # 为字符串加入 key
        $string .= '&key='. $this->signature_key;

        # 返回md5加密后的结果
        return strtoupper(md5($string));
    }

}


