<?php namespace service\core\alipay;

use yii;

class AbstractCaller
{

    # 构析函数
    public function __construct($param=[])
    {
        # 设置参数
        $this->partner       = $param['partner']
            ? $param['partner']      : Yii::$app->params['alipayApi']['partner'];
        $this->seller_id     = $param['seller_id']
            ? $param['seller_id']    : Yii::$app->params['alipayApi']['seller_id'];
        $this->seller_email  = $param['seller_email']
            ? $param['seller_email'] : Yii::$app->params['alipayApi']['seller_email'];
        $this->md5_sign_key  = $param['md5_sign_key']
            ? $param['md5_sign_key'] : Yii::$app->params['alipayApi']['md5_sign_key'];
        $this->input_charset = $param['input_charset']
            ? $param['input_charset']: Yii::$app->params['alipayApi']['input_charset'];
        $this->client_ip     = $param['client_ip']
            ? $param['client_ip']    : Yii::$app->params['alipayApi']['client_ip'];


        # 设置卖家支付宝用户号
        if (!($this->seller_id || $this->seller_email)) {
            $this->seller_id = $this->partner;
        }

        # 设置字符编码
        if (!$this->input_charset) {
            $this->input_charset = 'utf-8';
        }
        $this->input_charset = strtolower($this->input_charset);

        # 设置签名方式
        $this->sign_type = 'MD5';

        # 检测支付宝参数设置
        $this->_check_api_param();
    }


    ############################################################
    # 继承调用操作

    # 支付宝支付网关地址
    protected $alipay_gateway = 'https://mapi.alipay.com/gateway.do';
    protected $input_charset;       # 参数编码字符集
    protected $partner;             # 合作者身份ID
    protected $seller_id;           # 卖家支付宝用户号，以2088开头的16位纯数字组成
    protected $seller_email;        # 卖家支付宝账号，格式一般是邮箱或手机号
    protected $sign_type;           # 签名方式，DSA、RSA、MD5三个值可选，必须大写
    protected $md5_sign_key;        # MD5签名安全校验码
    protected $client_ip;           # 客户端IP

    /**
     *  生成支付宝接口地址
     *  @param  array $param    接口参数数据
     *  @return string
     */
    protected function _build_api_url($param=array())
    {
        # 设置基本参数
        $param['_input_charset'] = $this->input_charset;
        $param['partner']        = $this->partner;
        $param['sign_type']      = $this->sign_type;

        # 对请求参数进行签名
        $param['sign'] = $this->_generate_signature($param);

        # 组成完整的URL并返回
        $string = $this->_make_query_string($param, [], true);
        return $this->alipay_gateway .'?'. $string;
    }

    /**
     *  验证指定参数的签名是否正确
     *  @param  array $param
     *  @throws APIParameterError
     */
    protected function _verify_signature(Array $param)
    {
        $sign_local  = $this->_generate_signature($param);
        $sign_remote = $param['sign'];
        if ($sign_local != $sign_remote) {
            Yii::error('**local  signature**', $sign_local);
            Yii::error('**remote signature**', $sign_remote);
            throw new APIParameterError('signature_error');
        }
    }

    /**
     *  调用query_timestamp接口取得防钓鱼时间戳
     *  @param  bool $debug
     *  @return string
     */
    protected function _query_timestamp($debug=false)
    {
        # 生成验证接口地址
        $_array  = [
            'service'   => 'query_timestamp',
            'partner'   => $this->partner,
        ];
        $_string = $this->_make_query_string($_array, [], true);
        $url     = $this->alipay_gateway .'?'. $_string;
        if ($debug) dump($url);
        unset($_array, $_string);

        # 调用接口，取得时间戳
        $doc = new \DOMDocument();
        $doc->load($url);
        $elements    = $doc->getElementsByTagName( "encrypt_key" );
        $encrypt_key = $elements->item(0)->nodeValue;

        return $encrypt_key;
    }

    /**
     *  调用notify_verify接口验证通知校验ID
     *  @param  string  $notify_id      支付宝提供的notify_id
     *  @param  bool    $debug          调试标志位
     *  @throws APIParameterError
     */
    protected function _verify_notify_id($notify_id, $debug=false)
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


    ############################################################
    # 内部调用操作

    # 检测支付宝参数设置
    private function _check_api_param()
    {
        $is_success = true;

        # 必要参数检测
        if (!$this->partner)      $is_success = false;
        if (!$this->md5_sign_key) $is_success = false;
        if (!in_array($this->input_charset, ['utf-8', 'gbk', 'gb2312'])) {
            $is_success = false;
        }

        # 检测未通过则抛出异常
        if (!$is_success) {
            throw new APIParameterError('api_param_error');
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


