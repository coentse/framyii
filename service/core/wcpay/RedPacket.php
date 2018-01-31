<?php namespace service\core\wcpay;

class RedPacket extends AbstractCaller
{
    const API_SEND_REDPACK = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";

    /*
     *  发送红包
     *  @param string   $order_no       商户订单号
     *  @param string   $open_id        接收人的 open_id
     *  @param int      $amount         红包金额，单位为分
     *  @param string   $action_name    活动名称
     *  @param string   $message        活动信息
     *  @param string   $remark         活动备注
     *  @param bool     $debug          调试标志位
     */
    public function sendRedPacket($order_no, $open_id, $amount, $act_name, $message, $sender=null, $remark=null, $debug=false)
    {
        # 定单号长度检测
        if (strlen($order_no) < 1 || strlen($order_no) > 28) {
            throw new APIParameterError('order_no_length_error');
        }
        # 红包金额检测
        if ($amount < 100 || $amount > 20000) {
            throw new APIParameterError('amount_error');
        }

        # 组合参数
        $data = array(
            'mch_billno'   => $order_no,
            're_openid'    => $open_id,
            'total_amount' => $amount,
            'total_num'    => 1,
            'act_name'     => $act_name,
            'wishing'      => $message,
            'remark'       => $remark? $remark: $message,
            'mch_id'       => $this->merchant_id,
            'wxappid'      => $this->app_id,
            'send_name'    => $sender? $sender: $this->sender_name,
            'client_ip'    => $this->client_ip,
            'nonce_str'    => create_random_string(16),
        );

        # 取得参数的签名数据
        $data['sign'] = $this->_generate_signature($data);

        # 调用接口
        return $this->_call_api(self::API_SEND_REDPACK, $data, true, $debug);
    }

}


