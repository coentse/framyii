<?php namespace service\core\wechat;

/*
 * 文档：https://mp.weixin.qq.com/wiki/7/aaa137b55fb2e0456bf8dd9148dd613f.html
 *
 * 支持的JS接口
 * onMenuShareTimeline, onMenuShareAppMessage, onMenuShareQQ, onMenuShareWeibo, onMenuShareQZone,
 * startRecord, stopRecord, onVoiceRecordEnd, playVoice, pauseVoice, stopVoice, onVoicePlayEnd, uploadVoice, downloadVoice,
 * chooseImage, previewImage, uploadImage, downloadImage, translateVoice, getNetworkType, openLocation, getLocation,
 * hideOptionMenu, showOptionMenu, hideMenuItems, showMenuItems, hideAllNonBaseMenuItem, showAllNonBaseMenuItem,
 * closeWindow, scanQRCode, chooseWXPay, openProductSpecificView, addCard, chooseCard, openCard
 */

class JSAPI extends AbstractCaller
{
    # 接口设置
    const API_TICKET = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi";

    # 创建单例操作
    private static $_instance;
    public static function getInstance($access_token=null)
    {
        if(is_null(self::$_instance)) self::$_instance = new self($access_token);
        return self::$_instance;
    }

    # 取得 jsapi_ticke
    public function getJSAPITicket($debug=false)
    {
        if ($this->jsapi_ticket) return $this->jsapi_ticket;

        # 调用微信接口获取 jsapi_ticket
        $data = $this->_call_api(self::API_TICKET, null, $debug);
        $this->jsapi_ticket = $data['ticket'];
        $this->expire_time  = $data['expires_in'];

        return $this->jsapi_ticket;
    }

    # 获取 jsapi_ticke 过期时间
    public function getExpireTime()
    {
        return $this->expire_time;
    }

    # 生成 jsapi 签名
    public function generateSignature($ticket, $url=null, $noncestr=null, $debug=false)
    {
        # 准备参数
        $timestamp = time();
        if (!$noncestr) $noncestr = create_random_string();
        if (!$url) {
            $_array = explode("#", get_current_full_url(false));
            $url = $_array[0];
            unset($_array);
        }

        # 组合参数
        $param = array(
            'noncestr'     => $noncestr,
            'jsapi_ticket' => $ticket,
            'timestamp'    => $timestamp,
            'url'          => $url,
        );
        ksort($param);
        if ($debug==1) dump($param);

        # 生成参数字符串
        $param_list = array();
        foreach($param as $_key => $_val) {
            $param_list[] = $_key .'='. $_val;
        }
        $string = implode('&', $param_list);
        unset($param, $param_list);
        if ($debug==2) dump($string);

        # 生成签名
        $signature = sha1($string);

        return array(
            'url'       => $url,
            'noncestr'  => $noncestr,
            'timestamp' => $timestamp,
            'signature' => $signature,
        );
    }


    ############################################################
    # 内部调用操作

    private $jsapi_ticket;
    private $expire_time;

}

