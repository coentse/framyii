<?php namespace service\core\wechat;

/*
 *  微信推送消息“主类型”、“子类型”约定表：
 *  主类型“general”：普通消息
 *      子类型“text”：文本消息
 *      子类型“image”：图片消息
 *      子类型“voice”：语音消息
 *      子类型“video”：视频消息
 *      子类型“shortvideo”：小视频消息
 *      子类型“location”：地理位置消息
 *      子类型“link”：链接消息
 *  主类型“event”：事件推送
 *      子类型“subscribe”：用户关注公众号
 *      子类型“unsubscribe”：用户取消关注公众号
 *      子类型“scancode”：扫描带参数二维码：用户已关注公众号
 *      子类型“scancode-subscribe”：扫描带参数二维码：用户未关注公众号
 *      子类型“location”：上报地理位置
 *      子类型“tplmsg-sendfinish”：模板消息发送完成
 *      子类型“massmsg-sendfinish”：群发消息发送完成
 *      子类型“menu-click”：自定义菜单-拉取消息事件
 *      子类型“menu-view”：自定义菜单-跳转链接事件
 *      子类型“menu-scancode-push”：自定义菜单-扫码推送
 *      子类型“menu-scancode-waitmsg”：自定义菜单-扫码推送并且弹出“消息接收中”提示框
 *      子类型“menu-pic-photo”：自定义菜单-系统拍照发图
 *      子类型“menu-pic-photo-or-album”：自定义菜单-系统拍照或者相册发图
 *      子类型“menu-pic-weixin”：自定义菜单-微信相册发图
 *      子类型“menu-location-select”：自定义菜单-地理位置选择
 */

use yii;
use Overtrue\Wechat\Utils\XML;
use service\core\SystemVariable;

class ReceiveResolver
{

    public function __construct($app_id=NULL, $token=NULL, $aes_key=NULL)
    {
        # 设置微信接口参数
        $this->_wechat_app_id  = $app_id ? $app_id : Yii::$app->params['wechatApi']['app_id'];
        $this->_wechat_token   = $token  ? $token  : Yii::$app->params['wechatApi']['token'];
        $this->_wechat_aes_key = $aes_key? $aes_key: Yii::$app->params['wechatApi']['aes_key'];
    }

    # 执行解析操作
    public function resolve()
    {
        # 检测微信 API 参数设置
        $this->_do_check_api_param();

        # 接口 Token 验证操作
        $_echostr = SystemVariable::httpGet('echostr');
        if ($_echostr) $this->_do_verify_token($_echostr);

        # 如果数据经过加密，则接收加密相关参数并进行校验
        $_encrypt_type = strtolower(SystemVariable::httpGet('encrypt_type'));
        if ($_encrypt_type) $this->_do_verify_encrypt($_encrypt_type);

        # 解析微信方传递过来的消息数据
        $this->_do_parse_message();

        # 解析消息模式、类型
        list($msg_mode, $msg_type) = $this->_do_parse_mode_type();

        # 返回解析结果
        return [
            'msg_mode' => $msg_mode,
            'msg_type' => $msg_type,
            'message'  => $this->_message,
        ];
    }


    ############################################################
    # 执行流程

    # 检测微信 API 参数设置
    private function _do_check_api_param()
    {
        if (!($this->_wechat_app_id && $this->_wechat_token))
            throw new APIParameterError('api_param_error');
    }

    # 接口 Token 验证操作
    private function _do_verify_token($echostr)
    {
        $this->_crypt_signature = SystemVariable::httpGet('signature');
        $this->_crypt_timestamp = SystemVariable::httpGet('timestamp');
        $this->_crypt_nonce     = SystemVariable::httpGet('nonce');
        echo $this->_verify_api_token()? $echostr: 'token_verify_failure';
        exit();
    }

    # 对加密数据进行校验
    private function _do_verify_encrypt($encrypt_type)
    {
        # AES加密验证
        if ($encrypt_type == 'aes') {
            # 接收加密参数
            $this->_crypt_signature     = SystemVariable::httpGet('signature');
            $this->_crypt_timestamp     = SystemVariable::httpGet('timestamp');
            $this->_crypt_nonce         = SystemVariable::httpGet('nonce');
            $this->_crypt_msg_signature = SystemVariable::httpGet('msg_signature');

            # 验证加密 Token
            if (!$this->_verify_api_token()) {
                throw new APIParameterError('token_verify_failure');
            }
        }
        return true;
    }

    # 解析 XML 文本数据
    private function _do_parse_message()
    {
        # 获取POST数据
        $post_data = SystemVariable::httpRawPost();
        if (!$post_data) throw new APIParameterError('no_post_data');

        # 未加密消息处理方式
        if (!$this->_crypt_msg_signature) {
            $this->_message = XML::parse($post_data);
            return;
        }

        # 加密消息处理方式
        $this->_check_crypt_param();
        Crypt2::init($this->_wechat_app_id, $this->_wechat_token, $this->_wechat_aes_key);
        $this->_message = Crypt2::decrypt(
            $post_data, $this->_crypt_msg_signature, $this->_crypt_nonce, $this->_crypt_timestamp
        );
        return;
    }

    # 解析消息模式、类型
    private function _do_parse_mode_type()
    {
        $msg_type = strtolower($this->_message['MsgType']);
        if ($msg_type != 'event') {
            return ['general', $msg_type];
        }
        else {
            return ['event', $this->_parse_event_type()];
        }
    }


    ############################################################
    # 内部调用操作

    # 微信接口参数
    private $_wechat_app_id;
    private $_wechat_aes_key;
    private $_wechat_token;

    # 微信消息加密参数
    private $_crypt_signature;
    private $_crypt_timestamp;
    private $_crypt_nonce;
    private $_crypt_msg_signature;

    # 微信消息
    private $_message;

    # 推送事件类型代码映射表
    private $_event_code_maps = array(
        # 用户关注/取消关注相关事件
        'SUBSCRIBE'             => 'subscribe',
        'UNSUBSCRIBE'           => 'unsubscribe',
        # 消息发送相关事件
        'TEMPLATESENDJOBFINISH' => 'tplmsg-sendfinish',
        'MASSSENDJOBFINISH'     => 'massmsg-sendfinish',
        # 自定义菜单相关事件
        'CLICK'                 => 'menu-click',
        'VIEW'                  => 'menu-view',
        'SCANCODE_PUSH'         => 'menu-scancode-push',
        'SCANCODE_WAITMSG'      => 'menu-scancode-waitmsg',
        'PIC_SYSPHOTO'          => 'menu-pic-photo',
        'PIC_PHOTO_OR_ALBUM'    => 'menu-pic-photo-or-album',
        'PIC_WEIXIN'            => 'menu-pic-weixin',
        'LOCATION_SELECT'       => 'menu-location-select',
        # 其它事件
        'SCAN'                  => 'scancode',      # 扫描二维码
        'LOCATION'              => 'location',      # 上报地理位置
    );

    # 检测微信调用加密参数设置
    private function _check_crypt_param()
    {
        if (!($this->_wechat_aes_key))
            throw new APIParameterError('api_param_error');
        if (!($this->_crypt_signature && $this->_crypt_timestamp && $this->_crypt_nonce))
            throw new APIParameterError('crypt_param_error');
    }

    # 接口 token 验证操作（在微信后台绑定接口时使用）
    private function _verify_api_token()
    {
        # 使用 Token 生成验证数据签名
        $param_list = array($this->_wechat_token, $this->_crypt_timestamp, $this->_crypt_nonce);
        sort($param_list, SORT_STRING);
        $param_str  = implode('', $param_list);
        $ciphertext = sha1($param_str);

        # 判断签名是否一致
        return $this->_crypt_signature == $ciphertext;
    }

    # 解析推送事件类型
    private function _parse_event_type()
    {
        # 取得事件类型
        $event_type = $this->_event_code_maps[strtoupper($this->_message['Event'])];
        if (!$event_type) {
            throw new APIParameterError("unknow_event_type");
        }

        # 扫码订阅事件检测
        if ($event_type == 'subscribe' && $this->_message['Ticket']) {
            return 'scancode-subscribe';
        }

        return $event_type;
    }

}

