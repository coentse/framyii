<?php namespace service\core\wechat;

use yii;
use service\extensions\WXBizMsgCrypt\WXBizMsgCrypt;

use Overtrue\Wechat\Utils\XML;

class Crypt
{
    # 初始化加密器
    public static function init($app_id=NULL, $token=NULL, $aes_key=NULL) {
        if (!$app_id)  $app_id  = Yii::$app->params['wechatApi']['app_id'];
        if (!$token)   $token   = Yii::$app->params['wechatApi']['token'];
        if (!$aes_key) $aes_key = Yii::$app->params['wechatApi']['aes_key'];
        self::$app_id  = $app_id;
        self::$token   = $token;
        self::$aes_key = $aes_key;
        self::$is_init = true;
    }

    # 加密回复数据
    public static function encrypt($xml_data, $nonce=NULL, $timestamp=NULL)
    {
        self::_check_api_param();

        # 补全加密参数
        if (!$nonce)     $nonce = create_random_string();
        if (!$timestamp) $timestamp = time();

        # 加密数据
        $crypt = new WXBizMsgCrypt(self::$token, self::$aes_key, self::$app_id);
        $encrypt_msg = "";
        if ($_code = $crypt->encryptMsg($xml_data, $timestamp, $nonce, $encrypt_msg) != 0) {
            throw new \Exception('encrypt wechat message error (code: '. $_code .')');
        }
        return $encrypt_msg;
    }

    # 解密推送数据
    public static function decrypt($xml_data, $signature, $nonce, $timestamp)
    {
        self::_check_api_param();

        # 加密数据
        $crypt = new WXBizMsgCrypt(self::$token, self::$aes_key, self::$app_id);
        $decrypt_msg = "";
        if ($_code = $crypt->decryptMsg($signature, $timestamp, $nonce, $xml_data, $decrypt_msg) != 0) {
            throw new \Exception('decrypt wechat message error (code: '. $_code .')');
        }

        return XML::parse($decrypt_msg);
    }


    ############################################################
    # 内部调用操作

    private static $is_init;
    private static $app_id;
    private static $token;
    private static $aes_key;

    # 检测 API 参数
    private static function _check_api_param()
    {
        if (!self::$is_init) self::init();
        if (!(self::$app_id && self::$token && self::$aes_key)) {
            throw new APIParameterError('api_param_error');
        }
    }

}


