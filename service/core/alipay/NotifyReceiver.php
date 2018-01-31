<?php namespace service\core\alipay;

use yii;

class NotifyReceiver
{

    public function __construct($partner='', $md5_sign_key='')
    {
        # 设置支付宝合作者身份ID
        if ($partner) {
            $this->setPartner($partner);
        }

        # 设置MD5签名安全校验码
        if ($md5_sign_key) {
            $this->setMD5SignKey($md5_sign_key);
        } else {
            $this->setMD5SignKey(Yii::$app->params['alipayApi']['md5_sign_key']);
        }
    }

    /**
     *  设置支付宝合作者身份ID
     *
     *  @param $partner
     */
    public function setPartner($partner) {
        $this->partner = $partner;
    }

    /**
     *  设置MD5签名安全校验码
     *  @param $md5_sign_key
     */
    public function setMD5SignKey($md5_sign_key) {
        $this->md5_sign_key = $md5_sign_key;
    }

    /**
     *  取得支付宝的异步通知原始数据（未核验）
     *
     *  @return array
     *  @throws APIParameterError
     */
    public function getOriginalNotifyData()
    {
        $param = $_POST;
        if (!$param) throw new APIParameterError('no_notify_data');
        return $param;
    }

    /**
     *  核验证异步通知数据
     *
     *  @param  $param
     *  @throws APIParameterError
     */
    public function verifyNotifyData($param)
    {
        # 验证签名
        $sign_local  = $this->_generate_signature($param);
        $sign_remote = $param['sign'];
        if ($sign_local != $sign_remote) {
            Yii::error('**local  signature**', $sign_local);
            Yii::error('**remote signature**', $sign_remote);
            throw new APIParameterError('signature_error');
        }

        # 验证通知校验ID
        $this->_verify_notify_id($param['notify_id']);
    }


    ############################################################
    # 内部调用操作

    # 支付宝支付网关地址
    private $alipay_gateway = 'https://mapi.alipay.com/gateway.do';
    private $partner;               # 合作者身份ID
    private $md5_sign_key;          # MD5签名安全校验码


    /**
     *  调用notify_verify接口验证通知校验ID
     *  @param  string  $notify_id      支付宝提供的notify_id
     *  @param  bool    $debug          调试标志位
     *  @throws APIParameterError
     */
    private function _verify_notify_id($notify_id, $debug=false)
    {
        # 生成验证接口地址
        $_array  = [
            'service'   => 'notify_verify',
            'partner'   => $this->partner,
            'notify_id' => $notify_id,
        ];
        $_string = $this->_make_query_string($_array, [], true);
        $url     = $this->alipay_gateway .'?'. $_string;
        if ($debug) dump($url);
        unset($_array, $_string);

        # 将消息内容发送至指定地址，取得返回数据
        $response = https_fetch($url);

        # 返回数据检测
        if (trim($response) != 'true') {
            Yii::error('**remote response**', $response);
            throw new APIParameterError('notify_id_error');
        }
    }

    /**
     *  生成签名数据
     *  @param  array $param    待签名数据
     *  @return string
     */
    private function _generate_signature(Array $param)
    {
        # 对参数排序
        ksort($param);

        # 将参数组合为字符串
        $string = $this->_make_query_string($param, ['sign', 'sign_type']);

        # 为字符串加入 key
        $string .= $this->md5_sign_key;

        # 返回md5加密后的结果
        return md5($string);
    }

    /**
     *  生成URL查询字符串
     *  @param  array   $param              参数数据
     *  @param  array   $ignore_param_list  需要忽略的参数key列表
     *  @param  bool    $url_encode         是否为参数值进行URL编码
     *  @return string
     */
    private function _make_query_string(Array $param, Array $ignore_param_list=[], $url_encode=false)
    {
        $_array = [];
        foreach($param as $_key => $_val) {
            if (is_null($_val) || $_val === '') {
                continue;
            }
            if (is_array($_val)) continue;
            if (in_array($_key, $ignore_param_list)) {
                continue;
            }
            if ($url_encode) {
                $_array[] = $_key .'='. urlencode($_val);
            } else {
                $_array[] = $_key .'='. $_val;
            }
        }
        return implode('&', $_array);
    }

}

