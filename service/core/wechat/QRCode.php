<?php namespace service\core\wechat;


class QRCode extends AbstractCaller
{
    const API_CREATE  = "https://api.weixin.qq.com/cgi-bin/qrcode/create";
    const API_DISPLAY = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=";

    # 创建永久二维码
    public function createQRCode($scene_id, $debug=false)
    {
        $data = array(
            'action_name' => 'QR_LIMIT_SCENE',
            'action_info' => array(
                'scene' => array('scene_id' => intval($scene_id))
            )
        );

        return $this->_call_api(self::API_CREATE, $data, $debug);
    }

    # 创建永久字符串参数值二维码
    public function createStringQRCode($scene_str, $debug=false)
    {
        $data = array(
            'action_name' => 'QR_LIMIT_STR_SCENE',
            'action_info' => array(
                'scene' => array('scene_str' => $scene_str)
            )
        );

        return $this->_call_api(self::API_CREATE, $data, $debug);
    }

    # 创建临时二维码
    public function createTempQRCode($scene_id, $expire_time=604800, $debug=false)
    {
        $data = array(
            'action_name' => 'QR_SCENE',
            'action_info' => array(
                'scene' => array('scene_id' => $scene_id)
            ),
            'expire_seconds' => $expire_time
        );

        return $this->_call_api(self::API_CREATE, $data, $debug);
    }


    # 根据 ticket 生成二维码图片地址
    public static function generateQRCodeURL($ticket)
    {
        return self::API_DISPLAY . urlencode($ticket);
    }

}

