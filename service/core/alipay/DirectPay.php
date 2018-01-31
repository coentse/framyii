<?php namespace service\core\alipay;

/*
 * 即时到账交易
 * https://doc.open.alipay.com/docs/doc.htm?treeId=62&articleId=104744&docType=1
 */

use yii;

class DirectPay extends AbstractCaller
{

    private $anti_phishing_key = false;

    ############################################################
    # 发起请求相关操作

    /**
     * 启用防钓鱼时间戳功能
     */
    public function enableAntiPhishingKey()
    {
        $this->anti_phishing_key = true;
    }

    /**
     *  生成用户支付跳转地址
     *
     *  @param  string  $trade_no       商家端交易号
     *  @param  float   $amount         付款金额，单位为元，小数点后两位
     *  @param  string  $description    商品或支付说明
     *  @param  string  $notify_url     异步通知地址
     *  @param  string  $return_url     支付后页面跳转地址
     *  @return string
     */
    public function generatePayUrl($trade_no, $amount, $description, $notify_url=null, $return_url=null)
    {
        # 基本参数
        $param = [
            'service'           => 'create_direct_pay_by_user',
            'notify_url'        => $notify_url,
            'return_url'        => $return_url,
            'out_trade_no'      => $trade_no,
            'subject'           => $description,
            'payment_type'      => 1,
            'total_fee'         => $amount,
            'seller_id'         => $this->seller_id,
            'seller_email'      => $this->seller_email,
            'exter_invoke_ip'   => $this->client_ip,
        ];

        # 防钓鱼时间戳功能
        if ($this->anti_phishing_key) {
            $param['anti_phishing_key'] = $this->_query_timestamp();
        }

        return $this->_build_api_url($param);
    }

    /**
     *  生成有密退款跳转地址
     *  @param  string  $alipay_trade_no    退款交易对应的支付宝交易号
     *  @param  string  $refund_no          商家端退款单号
     *  @param  float   $refund_amount      退款金额，单位为元，小数点后两位
     *  @param  string  $refund_reason      退款原因
     *  @param  string  $notify_url         异步通知地址
     *  @return string
     */
    public function generateRefundUrl($alipay_trade_no, $refund_no, $refund_amount, $refund_reason, $notify_url=null)
    {
        $detail = $alipay_trade_no .'^'. $refund_amount .'^'. $refund_reason;
        $param  = [
            'service'       => 'refund_fastpay_by_platform_pwd',
            'notify_url'    => $notify_url,
            'seller_id'     => $this->seller_id,
            'seller_email'  => $this->seller_email,
            'refund_date'   => date("Y-m-d H:i:s"),
            'batch_no'      => $refund_no,
            'batch_num'     => 1,
            'detail_data'   => $detail,
        ];
        return $this->_build_api_url($param);
    }


    ############################################################
    # 接收消息相关操作

    # 取得支付完成重定向时的参数数据
    public function getRedirectData()
    {
        # 取得支付宝传递的数据
        $param = $_GET;
        if (!$param) throw new APIParameterError('no_param_data');

        # 验证签名
        $this->_verify_signature($param);


        # 验证通知校验ID
        $this->_verify_notify_id($param['notify_id']);

        return $param;
    }

}


