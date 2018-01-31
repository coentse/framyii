<?php namespace service\core\wechat;

/*
 * 高级群发接口
 * 文档：http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html
 */

class MassMessage extends AbstractCaller
{
    const API_PREVIEW   = "https://api.weixin.qq.com/cgi-bin/message/mass/preview";
    const API_SEND_ALL  = "https://api.weixin.qq.com/cgi-bin/message/mass/sendall";
    const API_SEND_USER = "https://api.weixin.qq.com/cgi-bin/message/mass/send";
    const API_QUERY     = "https://api.weixin.qq.com/cgi-bin/message/mass/get";
    const API_DELETE    = "https://api.weixin.qq.com/cgi-bin/message/mass/delete";

    ############################################################
    # 预览操作

    # 预览图文消息
    public function sendNewsMsgPreview($openid, $account, $media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('mpnews', array('media_id' => $media_id));
        if ($openid)  $message['touser']   = $openid;
        if ($account) $message['towxname'] = $account;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_PREVIEW, $message, $debug);
        return $result;
    }

    # 预览文本消息
    public function sendTextMsgPreview($openid, $account, $content, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('text', array('content' => $content));
        if ($openid)  $message['touser']   = $openid;
        if ($account) $message['towxname'] = $account;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_PREVIEW, $message, $debug);
        return $result;
    }

    # 预览语音消息
    public function sendVoiceMsgPreview($openid, $account, $media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('voice', array('media_id' => $media_id));
        if ($openid)  $message['touser']   = $openid;
        if ($account) $message['towxname'] = $account;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_PREVIEW, $message, $debug);
        return $result;
    }

    # 预览图片消息
    public function sendImageMsgPreview($openid, $account, $media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('image', array('media_id' => $media_id));
        if ($openid)  $message['touser']   = $openid;
        if ($account) $message['towxname'] = $account;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_PREVIEW, $message, $debug);
        return $result;
    }

    # 预览视频消息
    public function sendVideoMsgPreview($openid, $account, $media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('mpvideo', array('media_id' => $media_id));
        if ($openid)  $message['touser']   = $openid;
        if ($account) $message['towxname'] = $account;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_PREVIEW, $message, $debug);
        return $result;
    }

    # 预览卡卷消息
    public function sendCardMsgPreview($openid, $account, $card_id, $card_ext, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('wxcard', array(
            'card_id'  => $card_id,
            'card_ext' => $card_ext
        ));
        if ($openid)  $message['touser']   = $openid;
        if ($account) $message['towxname'] = $account;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_PREVIEW, $message, $debug);
        return $result;
    }


    ############################################################
    # 群发操作

    # 群发图文消息
    public function sendNewsMsgToAll($media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('mpnews', array('media_id' => $media_id));
        $message['filter'] = array('is_to_all' => true);

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendNewsMsgToGroup($group_id, $media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('mpnews', array('media_id' => $media_id));
        $message['filter'] = array(
            'is_to_all' => false,
            'group_id'  => $group_id
        );

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendNewsMsgToUser($user_list, $media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('mpnews', array('media_id' => $media_id));
        $message['touser'] = $user_list;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_USER, $message, $debug);
        return $result;
    }

    # 群发文本消息
    public function sendTextMsgToAll($content, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('text', array('content' => $content));
        $message['filter'] = array('is_to_all' => true);

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendTextMsgToGroup($group_id, $content, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('text', array('content' => $content));
        $message['filter'] = array(
            'is_to_all' => false,
            'group_id'  => $group_id
        );

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendTextMsgToUser($user_list, $content, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('text', array('content' => $content));
        $message['touser'] = $user_list;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_USER, $message, $debug);
        return $result;
    }

    # 群发语音消息
    public function sendVoiceMsgToAll($media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('voice', array('media_id' => $media_id));
        $message['filter'] = array('is_to_all' => true);

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendVoiceMsgToGroup($group_id, $media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('voice', array('media_id' => $media_id));
        $message['filter'] = array(
            'is_to_all' => false,
            'group_id'  => $group_id
        );

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendVoiceMsgToUser($user_list, $media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('voice', array('media_id' => $media_id));
        $message['touser'] = $user_list;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_USER, $message, $debug);
        return $result;
    }

    # 群发图片消息
    public function sendImageMsgToAll($media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('image', array('media_id' => $media_id));
        $message['filter'] = array('is_to_all' => true);

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendImageMsgToGroup($group_id, $media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('image', array('media_id' => $media_id));
        $message['filter'] = array(
            'is_to_all' => false,
            'group_id'  => $group_id
        );

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendImageMsgToUser($user_list, $media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('image', array('media_id' => $media_id));
        $message['touser'] = $user_list;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_USER, $message, $debug);
        return $result;
    }

    # 群发视频消息
    public function sendVideoMsgToAll($media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('mpvideo', array('media_id' => $media_id));
        $message['filter'] = array('is_to_all' => true);

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendVideoMsgToGroup($group_id, $media_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('mpvideo', array('media_id' => $media_id));
        $message['filter'] = array(
            'is_to_all' => false,
            'group_id'  => $group_id
        );

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendVideoMsgToUser($user_list, $media_id, $title=NULL, $description=NULL, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('mpvideo', array(
            'media_id'     => $media_id,
            'title'       => $title,
            'description' => $description,
        ));
        $message['touser'] = $user_list;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_USER, $message, $debug);
        return $result;
    }

    # 群发卡券消息
    public function sendCardMsgToAll($card_id, $card_ext, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('wxcard', array(
            'card_id'  => $card_id,
            'card_ext' => $card_ext
        ));
        $message['filter'] = array('is_to_all' => true);

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendCardMsgToGroup($group_id, $card_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('wxcard', array('card_id'  => $card_id));
        $message['filter'] = array(
            'is_to_all' => false,
            'group_id'  => $group_id
        );

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_ALL, $message, $debug);
        return $result;
    }
    public function sendCardMsgToUser($user_list, $card_id, $debug=false)
    {
        # 生成消息数据
        $message = $this->_build_message('wxcard', array('card_id'  => $card_id));
        $message['touser'] = $user_list;

        # 调用接口发送消息
        $result = $this->_call_api(self::API_SEND_USER, $message, $debug);
        return $result;
    }


    ############################################################
    # 发送状态查询操作

    public function getSendStatus($msg_id, $debug=false)
    {
        $message = array('msg_id' => $msg_id);
        return $this->_call_api(self::API_QUERY, $message, $debug);
    }


    ############################################################
    # 删除已发送成功的消息（当前只能删除图文消息和视频消息）

    public function deleteFinishMsg($msg_id, $debug=false)
    {
        $message = array('msg_id' => $msg_id);
        return $this->_call_api(self::API_DELETE, $message, $debug);
    }


    ############################################################
    # 内部调用操作

    /*
     * 生成群发消息数据
     *
     * “type”为“text”时，“$param”中必须包含“content”
     * “type”为“mpnews”、“voice”、“image”、“mpvideo”时，“$param”中必须包含“media_id”
     * “type”为“wxcard”时，“$param”中必须包含“card_id”，可以包含“card_ext”
     * “type”为“video”时，“$param”中必须包含“media_id”、“title”、“description”
     */
    private function _build_message($type, $param)
    {
        $message = array('msgtype' => $type);
        if ($type == 'text') {
            $message[$type]['content'] = $param['content'];
        }
        elseif (in_array($type, array('image', 'voice', 'mpnews', 'mpvideo'))) {
            $message[$type]['media_id'] = $param['media_id'];
        }
        elseif ($type == 'video') {
            $message[$type]['media_id']    = $param['media_id'];
            $message[$type]['title']       = $param['title'];
            $message[$type]['description'] = $param['description'];
        }
        elseif ($type == 'wxcard') {
            $message[$type]['card_id']  = $param['card_id'];
            if (is_array($param['card_ext'])) {
                $message[$type]['card_ext'] = json_encode($param['card_ext']);
            }
            else {
                $message[$type]['card_ext'] = $param['card_ext'];
            }
        }
        return $message;
    }

}


