<?php namespace service\core\wechat;

/*
 * 文档：http://mp.weixin.qq.com/wiki/18/28fc21e7ed87bec960651f0ce873ef8a.html
 */

use yii;

class AccessToken
{
    # 接口设置
    const API_TOKEN = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential";

    # 创建单例操作
    private static $_instance;
    public static function getInstance($app_id=null, $app_secret=null)
    {
        if(is_null(self::$_instance)) self::$_instance = new self($app_id, $app_secret);
        return self::$_instance;
    }

    public function __construct($app_id=null, $app_secret=null)
    {
        $this->app_id     = $app_id    ? $app_id    : Yii::$app->params['wechatApi']['app_id'];
        $this->app_secret = $app_secret? $app_secret: Yii::$app->params['wechatApi']['app_secret'];
    }

    # 获取 access_token
    public function getAccessToken()
    {
        if ($this->access_token) return $this->access_token;
        $this->_require_token();
        return $this->access_token;
    }

    # 获取 access_token 过期时间
    public function getExpireTime()
    {
        return $this->expire_time;
    }


    ############################################################
    # 内部调用操作

    private $app_id;
    private $app_secret;
    private $access_token;
    private $expire_time;

    # 通过微信接口获取 Token
    private function _require_token()
    {
        # 组合 URL
        $url = self::API_TOKEN . '&appid='. $this->app_id .'&secret='. $this->app_secret;

        # 获取 Token 数据
        $response = http_fetch($url);
        $data = json_decode($response, true);
        if ($data['errcode']) {
            throw new \Exception($data['errcode'] .': '. $data['errmsg']);
        }
        $this->access_token = $data['access_token'];
        $this->expire_time  = $data['expires_in'];
    }

}

