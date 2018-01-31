<?php namespace service\core\wechat;

use yii;
use Overtrue\Wechat\Crypt as OvertrueWechatCrypt;

class Crypt2
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
        $crypt = new OvertrueWechatCrypt(self::$app_id, self::$token, self::$aes_key);
        return $crypt->encryptMsg($xml_data, $nonce, $timestamp);
    }

    # 解密推送数据
    public static function decrypt($xml_data, $signature, $nonce, $timestamp)
    {
        self::_check_api_param();
        $crypt = new OvertrueWechatCrypt(self::$app_id, self::$token, self::$aes_key);
        return $crypt->decryptMsg($signature, $nonce, $timestamp, $xml_data);
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


