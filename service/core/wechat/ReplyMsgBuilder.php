<?php namespace service\core\wechat;

use yii;

class ReplyMsgBuilder
{

    /*
     * 生成文本消息
     *
     * @param string $from_user 开发者微信号
     * @param int    $to_user   接收方OpenID
     * @param string $content   回复的消息内容（换行：在content中能够换行，微信客户端就支持换行显示）
     * @param int    $timestamp 消息创建时间戳
     */
    public static function make_text_xml($from_user, $to_user, $content, $timestamp=NULL)
    {
        # 组合模板替换变量
        $replace_map = array(
            '#FROM_USER#'   => $from_user,
            '#TO_USER#'     => $to_user,
            '#CREATE_TIME#' => $timestamp? $timestamp: time(),
            '#CONTENT#'     => $content,
        );

        # 生成消息数据并返回
        return self::_build(self::$templates['text'], $replace_map);
    }

    /*
     * 生成图片消息
     *
     * @param string $from_user 开发者微信号
     * @param int    $to_user   接收方OpenID
     * @param string $media_id  通过素材管理接口上传多媒体文件，得到的id
     * @param int    $timestamp 消息创建时间戳
     */
    public static function make_image_xml($from_user, $to_user, $media_id, $timestamp=NULL)
    {
        # 组合模板替换变量
        $replace_map = array(
            '#FROM_USER#'   => $from_user,
            '#TO_USER#'     => $to_user,
            '#CREATE_TIME#' => $timestamp? $timestamp: time(),
            '#MEDIA_ID#'    => $media_id,
        );

        # 生成消息数据并返回
        return self::_build(self::$templates['image'], $replace_map);
    }

    /*
     * 生成语音消息
     *
     * @param string $from_user 开发者微信号
     * @param int    $to_user   接收方OpenID
     * @param string $media_id  通过素材管理接口上传多媒体文件，得到的id
     * @param int    $timestamp 消息创建时间戳
     */
    public static function make_voice_xml($from_user, $to_user, $media_id, $timestamp=NULL)
    {
        # 组合模板替换变量
        $replace_map = array(
            '#FROM_USER#'   => $from_user,
            '#TO_USER#'     => $to_user,
            '#CREATE_TIME#' => $timestamp? $timestamp: time(),
            '#MEDIA_ID#'    => $media_id,
        );

        # 生成消息数据并返回
        return self::_build(self::$templates['voice'], $replace_map);
    }

    /*
     * 生成视频消息
     *
     * @param string $from_user 开发者微信号
     * @param int    $to_user   接收方OpenID
     * @param string $media_id  通过素材管理接口上传多媒体文件，得到的id
     * @param array  $param     其它可选参数
     *
     * $param = array(
     *     'timestamp'      => '',  # 消息创建时间戳
     *     'title'          => '',  # 视频消息的标题
     *     'description'    => '',  # 视频消息的描述
     * );
     */
    public static function make_video_xml($from_user, $to_user, $media_id, $param=array())
    {
        # 组合模板替换变量
        $replace_map = array(
            '#FROM_USER#'   => $from_user,
            '#TO_USER#'     => $to_user,
            '#CREATE_TIME#' => $param['timestamp']? $param['timestamp']: time(),
            '#MEDIA_ID#'    => $media_id,
            '#TITLE#'       => $param['title']? $param['title']: '',
            '#DESCRIPTION#' => $param['description']? $param['description']: '',
        );

        # 生成消息数据并返回
        return self::_build(self::$templates['video'], $replace_map);
    }

    /*
     * 生成音乐消息
     *
     * @param string $from_user 开发者微信号
     * @param int    $to_user   接收方OpenID
     * @param array  $param     其它可选参数
     *
     * $param = array(
     *     'timestamp'      => '',  # 消息创建时间戳
     *     'title'          => '',  # 音乐标题
     *     'description'    => '',  # 音乐描述
     *     'music_url'      => '',  # 音乐链接
     *     'hq_music_url'   => '',  # 高质量音乐链接，WIFI环境优先使用该链接播放音乐
     *     'thumb_media_id' => '',  # 缩略图的媒体id，通过素材管理接口上传多媒体文件，得到的id
     * );
     */
    public static function make_music_xml($from_user, $to_user, $param=array())
    {
        # 组合模板替换变量
        $replace_map = array(
            '#FROM_USER#'      => $from_user,
            '#TO_USER#'        => $to_user,
            '#CREATE_TIME#'    => $param['timestamp']? $param['timestamp']: time(),
            '#TITLE#'          => $param['title']? $param['title']: '',
            '#DESCRIPTION#'    => $param['description']? $param['description']: '',
            '#MUSIC_URL#'      => $param['music_url']  ? $param['music_url']: '',
            '#HQ_MUSIC_URL#'   => $param['hq_music_url']? $param['hq_music_url']: '',
            '#THUMB_MEDIA_ID#' => $param['thumb_media_id']? $param['thumb_media_id']: '',
        );

        # 生成消息数据并返回
        return self::_build(self::$templates['music'], $replace_map);
    }

    /*
     * 生成图文消息
     *
     * @param string $from_user 开发者微信号
     * @param int    $to_user   接收方OpenID
     * @param array  $articles  图文消息信息
     * @param int    $timestamp 消息创建时间戳
     *
     * $articles = array(
     *     array(
     *         'title'       => '', # 图文消息标题
     *         'description' => '', # 图文消息描述
     *         'pic_url'     => '', # 图片链接，支持JPG、PNG格式，较好的效果为大图360*200，小图200*200
     *         'url'         => '', # 点击图文消息跳转链接
     *     ),
     *     ...
     * );
     */
    public static function make_news_xml($from_user, $to_user, $articles, $timestamp=NULL)
    {
        # 生成图文消息-文章数据
        $article_data = array();
        foreach($articles as $article) {
            $_replace_map = array(
                '#TITLE#'       => $article['title'],
                '#DESCRIPTION#' => $article['description'],
                '#PIC_URL#'     => $article['pic_url'],
                '#URL#'         => $article['url'],
            );
            $article_data[] = self::_build(self::$templates['news_item'], $_replace_map);
            unset($article, $_replace_map);
        }
        unset($articles);

        # 生成图文消息
        $replace_map = array(
            '#FROM_USER#'      => $from_user,
            '#TO_USER#'        => $to_user,
            '#CREATE_TIME#'    => $timestamp? $timestamp: time(),
            '#ARTICLE_COUNT#'  => count($article_data),
            '#ARTICLE_DATA#'   => implode("\n", $article_data),
        );

        # 生成消息数据并返回
        return self::_build(self::$templates['news_base'], $replace_map);
    }


    ############################################################
    # 内部调用操作

    # 消息模板
    private static $templates = [
        # 文本消息
        'text'      => <<<'EOT'
<xml>
<ToUserName><![CDATA[#TO_USER#]]></ToUserName>
<FromUserName><![CDATA[#FROM_USER#]]></FromUserName>
<CreateTime>#CREATE_TIME#</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[#CONTENT#]]></Content>
</xml>
EOT
,
        # 图片消息
        'image'     => <<<'EOT'
<xml>
<ToUserName><![CDATA[#TO_USER#]]></ToUserName>
<FromUserName><![CDATA[#FROM_USER#]]></FromUserName>
<CreateTime>#CREATE_TIME#</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
<Image>
<MediaId><![CDATA[#MEDIA_ID#]]></MediaId>
</Image>
</xml>
EOT
,
        # 语音消息
        'voice'     => <<<'EOT'
<xml>
<ToUserName><![CDATA[#TO_USER#]]></ToUserName>
<FromUserName><![CDATA[#FROM_USER#]]></FromUserName>
<CreateTime>#CREATE_TIME#</CreateTime>
<MsgType><![CDATA[voice]]></MsgType>
<Voice>
<MediaId><![CDATA[#MEDIA_ID#]]></MediaId>
</Voice>
</xml>
EOT
,
        # 视频消息
        'video'     => <<<'EOT'
<xml>
<ToUserName><![CDATA[#TO_USER#]]></ToUserName>
<FromUserName><![CDATA[#FROM_USER#]]></FromUserName>
<CreateTime>#CREATE_TIME#</CreateTime>
<MsgType><![CDATA[video]]></MsgType>
<Video>
<MediaId><![CDATA[#MEDIA_ID#]]></MediaId>
<Title><![CDATA[#TITLE#]]></Title>
<Description><![CDATA[#DESCRIPTION#]]></Description>
</Video>
</xml>
EOT
,
        # 音乐消息
        'music'     => <<<'EOT'
<xml>
<ToUserName><![CDATA[#TO_USER#]]></ToUserName>
<FromUserName><![CDATA[#FROM_USER#]]></FromUserName>
<CreateTime>#CREATE_TIME#</CreateTime>
<MsgType><![CDATA[music]]></MsgType>
<Music>
<Title><![CDATA[#TITLE#]]></Title>
<Description><![CDATA[#DESCRIPTION#]]></Description>
<MusicUrl><![CDATA[#MUSIC_URL#]]></MusicUrl>
<HQMusicUrl><![CDATA[#HQ_MUSIC_URL#]]></HQMusicUrl>
<ThumbMediaId><![CDATA[#THUMB_MEDIA_ID#]]></ThumbMediaId>
</Music>
</xml>
EOT
,
        # 图文消息：基础数据
        'news_base' => <<<'EOT'
<xml>
<ToUserName><![CDATA[#TO_USER#]]></ToUserName>
<FromUserName><![CDATA[#FROM_USER#]]></FromUserName>
<CreateTime>#CREATE_TIME#</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>#ARTICLE_COUNT#</ArticleCount>
<Articles>
#ARTICLE_DATA#
</Articles>
</xml>
EOT
,
        # 图文消息：文章数据
        'news_item' => <<<'EOT'
<item>
<Title><![CDATA[#TITLE#]]></Title>
<Description><![CDATA[#DESCRIPTION#]]></Description>
<PicUrl><![CDATA[#PIC_URL#]]></PicUrl>
<Url><![CDATA[#URL#]]></Url>
</item>
EOT
,
    ];

    # 是否需要加密消息
    public static $need_encrypt = true;

    # 生成消息
    private static function _build($template, $replace_map)
    {
        $message = self::_replace_template_var($template, $replace_map);
        $message = str_replace("\n", '', $message);

        # 加密消息
        if (self::$need_encrypt) {
            try {
                $message = Crypt2::encrypt($message);
            }
            catch(\Exception $e) {
                Yii::error('encrypt message failure!');
                Yii::error($e->getMessage());
            }
        }
        return $message;
    }

    # 模板变量替换操作
    private static function _replace_template_var($template, $var_list)
    {
        foreach($var_list as $key => $value) {
            $template = str_replace($key, $value, $template);
        }
        return $template;
    }

}

