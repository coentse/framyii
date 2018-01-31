<?php namespace service\core\wcpay;

/*
 * 异步通知接收器
 */

use yii;
use Overtrue\Wechat\Utils\XML;
use service\core\SystemVariable;

class NotifyReceiver
{

    public function __construct($signature_key='')
    {
        if ($signature_key) {
            $this->setSignatureKey($signature_key);
        } else {
            $this->setSignatureKey(Yii::$app->params['wcpayApi']['sign_key']);
        }
    }

    /**
     *  设置签名密钥
     *
     *  @param  string  $key    签名密钥
     */
    public function setSignatureKey($key)
    {
        $this->_signature_key = $key;
    }

    /**
     *  取得微信支付的异步通知原始数据（未核验）
     *
     *  @return array
     *  @throws APIParameterError
     */
    public function getOriginalNotifyData()
    {
        # 取得原始POST数据
        $raw_data = SystemVariable::httpRawPost();
        if (!$raw_data) throw new APIParameterError('no_notify_data');

        # 对数据进行XML解析
        $param = XML::parse($raw_data);
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
        $sign_local  = $this->_generate_signature($param);
        $sign_remote = $param['sign'];
        if ($sign_local != $sign_remote) {
            Yii::error('**local  signature**', $sign_local);
            Yii::error('**remote signature**', $sign_remote);
            throw new APIParameterError('signature_error');
        }
    }


    ############################################################
    # 内部调用操作

    private $_signature_key;    # 签名密钥

    /**
     *  生成签名
     *
     *  @param  array   $param  待签名数据
     *  @return string
     */
    private function _generate_signature($param)
    {
        # 对参数排序
        ksort($param);

        # 将参数组合为字符串
        $string = $this->_make_query_string($param, ['sign']);

        # 为字符串加入 key
        $string .= '&key='. $this->_signature_key;

        # 返回md5加密后的结果
        return strtoupper(md5($string));
    }

    /**
     * 生成URL查询字符串
     *
     * @param array $param
     * @param array $ignore_param_list
     * @return string
     */
    protected function _make_query_string(Array $param, Array $ignore_param_list=[])
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
            $_array[] = $_key .'='. $_val;
        }
        return implode('&', $_array);
    }

}


