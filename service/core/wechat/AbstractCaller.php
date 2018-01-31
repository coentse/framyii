<?php namespace service\core\wechat;


class AbstractCaller
{
    protected $access_token;

    public function __construct($access_token=null, $app_id=null, $app_secret=null)
    {
        if ($access_token) {
            $this->access_token = $access_token;
        }
        else {
            $AccessToken = new AccessToken($app_id, $app_secret);
            $this->access_token = $AccessToken->getAccessToken();
        }
        if (!$this->access_token) {
            throw new APIParameterError('invalid_access_token');
        }
    }


    ############################################################
    # 内部调用操作

    /*
     * 将所提供的消息发送至指定的网址
     */
    protected function _call_api($call_api, $call_data=null, $debug=false)
    {
        $call_api = $this->_append_token($call_api);

        # 将消息内容转换为 JSON
        if ($call_data && is_array($call_data)) {
            $call_data = json_encode($call_data);
            $call_data = urldecode($call_data);
        }

        # 输出调试信息
        if ($debug) {
            dump($call_api, false);
            dump($call_data);
        }

        # 将消息内容发送至指定地址，取得返回数据并解码
        $response = http_fetch($call_api, $call_data);
        return json_decode($response, true);
    }

    # 在 URL 中附加 Token 信息
    protected function _append_token($url)
    {
        $url .= (strpos($url, '?') === false)? '?': '&';
        $url .= "access_token=" . $this->access_token;
        return $url;
    }

}

