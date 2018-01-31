<?php namespace service\core\wechat;

use yii;

class Authentication
{
    private static $_instance;
    public static function getInstance($app_id=null, $app_secret=null)
    {
        if(is_null(self::$_instance)) self::$_instance = new self($app_id, $app_secret);
        return self::$_instance;
    }

    ############################################################
    # 外部调用操作

    const API_AUTHORIZE     = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={{APP_ID}}&redirect_uri={{REDIRECT_URI}}&response_type=code&scope={{SCOPE}}&state={{STATE}}#wechat_redirect";
    const API_GET_TOKEN     = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={{APP_ID}}&secret={{APP_SECRET}}&code={{AUTH_CODE}}&grant_type=authorization_code";
    const API_REFRESH_TOKEN = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid={{APP_ID}}&grant_type=refresh_token&refresh_token={{REFRESH_TOKEN}}";
    const API_GET_USERINFO  = "https://api.weixin.qq.com/sns/userinfo?access_token={{TOKEN}}&openid={{OPENID}}&lang={{LANG}}";
    const API_VERIFY_TOKEN  = "https://api.weixin.qq.com/sns/auth?access_token={{TOKEN}}&openid={{OPENID}}";

    # 构造函数
    public function __construct($app_id=null, $app_secret=null)
    {
        $this->app_id     = $app_id    ? $app_id    : Yii::$app->params['wechatApi']['app_id'];
        $this->app_secret = $app_secret? $app_secret: Yii::$app->params['wechatApi']['app_secret'];
    }

    /*
     * 生成请求用户同意授权地址（获取code）
     *
     * @param string $redirect_uri
     * @param string $scope 授权作用域，“snsapi_base”只能获取用户openid，“snsapi_userinfo”可通过openid拿到昵称、性别、所在地
     * @param string $state
     * @return string
     */
    public function generateAuthenticationURL($redirect_uri, $state='', $scope='snsapi_userinfo')
    {
        $url = self::API_AUTHORIZE;
        $url = str_replace('{{APP_ID}}',       $this->app_id, $url);
        $url = str_replace('{{REDIRECT_URI}}', urlencode($redirect_uri), $url);
        $url = str_replace('{{SCOPE}}',        $scope, $url);
        $url = str_replace('{{STATE}}',        $state, $url);
        return $url;
    }

    # 获取用户认证网页授权令牌（使用code兑换）
    public function getAccessTokenByCode($auth_code)
    {
        # 生成接口调用地址
        $call_api = self::API_GET_TOKEN;
        $call_api = str_replace('{{APP_ID}}',     $this->app_id, $call_api);
        $call_api = str_replace('{{APP_SECRET}}', $this->app_secret, $call_api);
        $call_api = str_replace('{{AUTH_CODE}}',  $auth_code, $call_api);

        # 调用接口，取得响应信息
        $response = http_fetch($call_api, null);
        return json_decode($response, true);
    }

    # 刷新网页授权令牌
    public function refreshAccessToken($refresh_token)
    {
        # 生成接口调用地址
        $call_api = self::API_REFRESH_TOKEN;
        $call_api = str_replace('{{APP_ID}}', $this->app_id, $call_api);
        $call_api = str_replace('{{REFRESH_TOKEN}}', $refresh_token, $call_api);

        # 调用接口，取得响应信息
        $response = http_fetch($call_api, null);
        return json_decode($response, true);
    }

    # 拉取用户信息
    public function getUserInfo($access_token, $open_id, $lang='zh_CN')
    {
        # 生成接口调用地址
        $call_api = self::API_GET_USERINFO;
        $call_api = str_replace('{{TOKEN}}',  $access_token, $call_api);
        $call_api = str_replace('{{OPENID}}', $open_id, $call_api);
        $call_api = str_replace('{{LANG}}',   $lang, $call_api);

        # 调用接口，取得响应信息
        $response = http_fetch($call_api, null);
        return json_decode($response, true);
    }

    # 检验网页授权令牌是否有效
    public function verifyAccessToken($access_token, $open_id)
    {
        # 生成接口调用地址
        $call_api = self::API_VERIFY_TOKEN;
        $call_api = str_replace('{{TOKEN}}',  $access_token, $call_api);
        $call_api = str_replace('{{OPENID}}', $open_id, $call_api);

        # 调用接口，取得响应信息
        $response = http_fetch($call_api, null);
        return json_decode($response, true);
    }


    ############################################################
    # 内部调用操作

    private $app_id;
    private $app_secret;

}


