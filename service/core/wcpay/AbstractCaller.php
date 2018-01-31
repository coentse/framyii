<?php namespace service\core\wcpay;

use yii;
use Overtrue\Wechat\Utils\XML;

class AbstractCaller
{

    # 构析函数
    public function __construct($param=array())
    {
        # 设置必要参数
        $this->app_id = $param['app_id']
            ? $param['app_id']       : Yii::$app->params['wcpayApi']['app_id'];
        $this->merchant_id = $param['merchant_id']
            ? $param['merchant_id']  : Yii::$app->params['wcpayApi']['mch_id'];
        $this->sender_name = $param['sender_name']
            ? $param['sender_name']  : Yii::$app->params['wcpayApi']['send_name'];
        $this->signature_key = $param['signature_key']
            ? $param['signature_key']: Yii::$app->params['wcpayApi']['sign_key'];
        $this->notify_url = $param['notify_url']
            ? $param['notify_url']   : Yii::$app->params['wcpayApi']['notify_url'];

        # 设置 SSL 证书、私钥
        $this->certificate_file = $param['certificate_file']
            ? $param['certificate_file']: Yii::$app->params['wcpayApi']['cert_file'];
        $this->private_key_file = $param['private_key_file']
            ? $param['private_key_file']: Yii::$app->params['wcpayApi']['key_file'];

        # 设置客户端IP
        $this->client_ip = $param['client_ip']? $param['client_ip']: '127.0.0.1';
    }


    ############################################################
    # 继承调用操作

    protected $app_id;              # 微信AppID
    protected $merchant_id;         # 微信商户ID
    protected $sender_name;         # 发送者名称
    protected $signature_key;       # 签名密钥
    protected $certificate_file;    # SSL证书
    protected $private_key_file;    # SSL私钥
    protected $client_ip;           # 调用端IP地址
    protected $notify_url;          # 异步通知地址

    /**
     *  接口调用基础方法
     *  @param  string  $call_api       接口地址
     *  @param  array   $call_data      调用参数
     *  @param  bool    $use_ssl_cert   是否使用指定的SSL证书
     *  @param  bool    $debug          调试标志位
     *  @return array
     *  @throws APICallError
     */
    protected function _call_api($call_api, $call_data=[], $use_ssl_cert=false, $debug=false)
    {
        # 将调用数据转换为 XML 格式
        $call_data = XML::build($call_data);

        # 输出调试信息
        if ($debug) {
            dump($call_api, false);
            dump($call_data);
        }

        # 设置 HTTPS 参数
        $ssl_param = array(
            'disable_peer_verify' => true,
            'disable_host_verify' => true,
        );
        if ($use_ssl_cert) {
            $ssl_param['certificate_file'] = $this->certificate_file;
            $ssl_param['private_key_file'] = $this->private_key_file;
        }

        # 将消息内容发送至指定地址，取得返回的 XML 数据
        $response = https_fetch($call_api, $call_data, $ssl_param);

        # 解码 XML 数据
        $response = XML::parse($response);

        # 进行签名验证
        if ($response['sign']) {
            $_signature = $this->_generate_signature($response);
            if ($response['sign'] != $_signature) {
                throw new APICallError('signature_verify_error');
            }
        }

        # 判断调用通信是否成功
        if ($response['return_code'] != 'SUCCESS') {
            throw new APICallError($response['return_msg']);
        }

        return $response;
    }

    /**
     *  生成签名数据
     *  @param  array $param    需要签名的参数
     *  @return string
     */
    protected function _generate_signature($param)
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

