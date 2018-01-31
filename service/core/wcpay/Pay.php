<?php namespace service\core\wcpay;

class Pay extends AbstractCaller
{
    # 接口地址定义
    const API_UNIFIED_ORDER = "https://api.mch.weixin.qq.com/pay/unifiedorder";
    const API_QUERY_ORDER   = "https://api.mch.weixin.qq.com/pay/orderquery";
    const API_CLOSE_ORDER   = "https://api.mch.weixin.qq.com/pay/closeorder";
    const API_APPLY_REFUND  = "https://api.mch.weixin.qq.com/secapi/pay/refund";
    const API_QUERY_REFUND  = "https://api.mch.weixin.qq.com/pay/refundquery";
    const API_DOWNLOAD_BILL = "https://api.mch.weixin.qq.com/pay/downloadbill";

    # 交易类型
    private $trade_type = 'JSAPI';

    /**
     *  设置交易类型
     *  @param   string     $type   交易类型代号，可选JSAPI、NATIVE、APP
     *  @return  bool
     *  @throws  APIParameterError
     */
    public function setTradeType($type)
    {
        $type = strtoupper($type);

        # 检测指定的交易类型是否为允许值
        $allow_list = ['JSAPI', 'NATIVE', 'APP'];
        if (!in_array($type, $allow_list)) {
            throw new APIParameterError('unknown_trade_type');
        }

        # 设置交易类型
        $this->trade_type = $type;
        return true;
    }

    /**
     *  统一下单操作
     *  @param  string  $order_no       商户订单号
     *  @param  string  $open_id        付款人的 open_id
     *  @param  int     $amount         付款金额，单位为分
     *  @param  string  $description    商品或支付说明
     *  @param  int     $expire_minute  支付过期保留时间，单位为分钟，最少为5分钟
     *  @param  bool    $debug          调试标志位
     *  @return array
     *  @throws APIParameterError
     */
    public function createOrder($order_no, $open_id, $amount, $description, $expire_minute=0, $debug=false)
    {
        # 定单号长度检测
        if (strlen($order_no) < 1 || strlen($order_no) > 32) {
            throw new APIParameterError('order_no_length_error');
        }
        # 过期分钟数检测
        if ($expire_minute && $expire_minute < 5) {
            throw new APIParameterError('expire_minute_error');
        }

        # 设置过期时间
        if ($expire_minute) {
            $expire_time = date("YmdHis", strtotime("+". $expire_minute ." minute"));
        }
        else {
            $expire_time = '';
        }

        # 组合参数
        $data = array(
            'appid'             => $this->app_id,
            'mch_id'            => $this->merchant_id,
            'device_info'       => 'WEB',
            'nonce_str'         => create_random_string(16),
            'body'              => $description,
            'out_trade_no'      => $order_no,
            'fee_type'          => 'CNY',
            'total_fee'         => $amount,
            'spbill_create_ip'  => $this->client_ip,
            'time_start'        => date("YmdHis"),
            'time_expire'       => $expire_time,
            'notify_url'        => $this->notify_url,
            'trade_type'        => $this->trade_type,
            'openid'            => $open_id,
        );

        # 取得参数的签名数据
        $data['sign'] = $this->_generate_signature($data);

        # 调用接口
        return $this->_call_api(self::API_UNIFIED_ORDER, $data, false, $debug);
    }

    /**
     *  查询定单
     *  @param  string  $order_no       商户订单号
     *  @param  string  $wc_order_no    微信订单号
     *  @param  bool    $debug          调试标志位
     *  @return array
     *  @throws APIParameterError
     */
    public function queryOrder($order_no=null, $wc_order_no=null, $debug=false)
    {
        # 定单号检测
        if (!($order_no || $wc_order_no)) {
            throw new APIParameterError('order_no_error');
        }

        # 组合参数
        $data = array(
            'appid'             => $this->app_id,
            'mch_id'            => $this->merchant_id,
            'transaction_id'    => $wc_order_no,
            'out_trade_no'      => $order_no,
            'nonce_str'         => create_random_string(16),
        );

        # 取得参数的签名数据
        $data['sign'] = $this->_generate_signature($data);

        # 调用接口
        return $this->_call_api(self::API_QUERY_ORDER, $data, false, $debug);
    }

    /**
     *  关闭定单
     *  @param  string  $order_no       商户订单号
     *  @param  bool    $debug          调试标志位
     *  @return array
     */
    public function closeOrder($order_no=null, $debug=false)
    {
        # 组合参数
        $data = array(
            'appid'             => $this->app_id,
            'mch_id'            => $this->merchant_id,
            'out_trade_no'      => $order_no,
            'nonce_str'         => create_random_string(16),
        );

        # 取得参数的签名数据
        $data['sign'] = $this->_generate_signature($data);

        # 调用接口
        return $this->_call_api(self::API_CLOSE_ORDER, $data, false, $debug);
    }

    /**
     *  申请退款
     *  @param  string  $refund_no      商户退款单号
     *  @param  int     $order_amount   订单总金额，单位为分
     *  @param  int     $refund_amount  退款总金额，单位为分
     *  @param  string  $order_no       原订单的商户订单号
     *  @param  string  $wc_order_no    原订单的微信订单号
     *  @param  bool    $debug          调试标志位
     *  @return array
     *  @throws APIParameterError
     */
    public function applyRefund($refund_no, $order_amount, $refund_amount, $order_no=null, $wc_order_no=null, $debug=false)
    {
        # 定单号检测
        if (!($order_no || $wc_order_no)) {
            throw new APIParameterError('order_no_error');
        }

        # 组合参数
        $data = array(
            'appid'             => $this->app_id,
            'mch_id'            => $this->merchant_id,
            'nonce_str'         => create_random_string(16),
            'transaction_id'    => $wc_order_no,
            'out_trade_no'      => $order_no,
            'out_refund_no'     => $refund_no,
            'total_fee'         => $order_amount,
            'refund_fee'        => $refund_amount,
            'refund_fee_type'   => 'CNY',
            'op_user_id'        => $this->merchant_id,
        );

        # 取得参数的签名数据
        $data['sign'] = $this->_generate_signature($data);

        # 调用接口
        return $this->_call_api(self::API_APPLY_REFUND, $data, true, $debug);
    }

    /**
     *  查询退款
     *  @param  string  $refund_no      商户退款单号
     *  @param  string  $wc_refund_no   微信退款单号
     *  @param  bool    $debug          调试标志位
     *  @return array
     *  @throws APIParameterError
     */
    public function queryRefund($refund_no=null, $wc_refund_no=null, $debug=false)
    {
        # 定单号检测
        if (!($refund_no || $wc_refund_no)) {
            throw new APIParameterError('refund_no_error');
        }

        # 组合参数
        $data = array(
            'appid'             => $this->app_id,
            'mch_id'            => $this->merchant_id,
            'device_info'       => 'WEB',
            'nonce_str'         => create_random_string(16),
            'out_refund_no'     => $refund_no,
            'refund_id'         => $wc_refund_no,
        );

        # 取得参数的签名数据
        $data['sign'] = $this->_generate_signature($data);

        # 调用接口
        return $this->_call_api(self::API_QUERY_REFUND, $data, false, $debug);
    }

}

