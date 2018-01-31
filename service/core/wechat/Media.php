<?php namespace service\core\wechat;

/*
 * 文档：http://mp.weixin.qq.com/wiki/4/b3546879f07623cb30df9ca0e420a5d0.html
 */


class Media extends AbstractCaller
{

    const API_GET = "https://api.weixin.qq.com/cgi-bin/media/get?media_id=";
    const API_UPLOAD = "https://api.weixin.qq.com/cgi-bin/media/upload";
    const API_UPLOAD_NEWS_IMAGE = "https://api.weixin.qq.com/cgi-bin/media/uploadimg";

    # 获取临时素材
    public function fetchMedia($media_id, $debug=false)
    {
        $call_api = self::API_GET . $media_id;
        $call_api = $this->_append_token($call_api);
        if ($debug) dump($call_api);

        # 将消息内容发送至指定地址，取得返回数据
        $response = http_fetch($call_api);
        return $response;
    }

    # 上传临时素材（“image”图片、“voice”语音、“video”视频、“thumb”缩略图）
    public function uploadMedia($type, $filepath, $debug=false)
    {
        # 生成提交数据
        if (!file_exists($filepath)) return false;
        $param = array('type' => $type);
        $files = array('media' => $filepath);

        # 生成接口调用地址
        $call_api = $this->_append_token(self::API_UPLOAD);

        # 调试输出
        if ($debug) {
            dump($call_api, false);
            dump($files, false);
            dump($param);
        }

        # 上传文件，取得响应信息并解码
        $response = http_upload($call_api, $files, $param);
        return json_decode($response, true);
    }

    /*
     * 上传图文消息内的图片素材
     *
     * 注：
     * 微信方会自动过滤图文消息内容中的外链图片，所以需要使用此接口上传所需要的图片来获得可以
     * 在图文内容中查看的图片链接。
     * 本接口所上传的图片不占用公众号的素材库中图片数量的5000个的限制。图片仅支持jpg/png格式，
     * 大小必须在1MB以下。
     */
    public function uploadNewsImage($filepath, $debug=false)
    {
        # 生成提交数据
        if (!file_exists($filepath)) return false;
        $files = array('media' => $filepath);

        # 生成接口调用地址
        $call_api = $this->_append_token(self::API_UPLOAD_NEWS_IMAGE);

        # 调试输出
        if ($debug) {
            dump($call_api, false);
            dump($files);
        }

        # 上传文件，取得响应信息并解码
        $response = http_upload($call_api, $files);
        return json_decode($response, true);
    }

}

