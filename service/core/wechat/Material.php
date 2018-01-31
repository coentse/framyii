<?php namespace service\core\wechat;

/*
 * 文档：http://mp.weixin.qq.com/wiki/4/b3546879f07623cb30df9ca0e420a5d0.html
 */

class Material extends AbstractCaller
{
    const API_GET_COUNT   = "https://api.weixin.qq.com/cgi-bin/material/get_materialcount";
    const API_GET_LIST    = "https://api.weixin.qq.com/cgi-bin/material/batchget_material";
    const API_GET         = "https://api.weixin.qq.com/cgi-bin/material/get_material";
    const API_DELETE      = "https://api.weixin.qq.com/cgi-bin/material/del_material";
    const API_ADD_GENERAL = "https://api.weixin.qq.com/cgi-bin/material/add_material";
    const API_ADD_NEWS    = "https://api.weixin.qq.com/cgi-bin/material/add_news";
    const API_UPDATE_NEWS = "https://api.weixin.qq.com/cgi-bin/material/update_news";

    ############################################################
    # 能用素材操作

    # 获取素材数量
    public function getMaterialCount($debug=false)
    {
        return $this->_call_api(self::API_GET_COUNT, null, $debug);
    }

    # 获取素材列表
    public function getMaterialList($type, $offset=0, $count=20, $debug=false)
    {
        $data = array(
            'type'   => $type,
            'offset' => $offset,
            'count'  => $count,
        );
        return $this->_call_api(self::API_GET_LIST, $data, $debug);
    }

    # 添加永久普通素材（“image”图片、“voice”语音、“thumb”缩略图）
    public function addMaterial($type, $filepath, $debug=false)
    {
        return $this->_upload_material($type, $filepath, array(), $debug);
    }

    # 下载永久素材（“image”图片、“voice”语音、“thumb”缩略图）
    public function fetchMaterial($media_id, $debug=false)
    {
        # 生成调用接口地址、提交数据
        $call_api  = $this->_append_token(self::API_GET);
        $call_data = array('media_id' => $media_id);

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

        # 将消息内容发送至指定地址，取得返回数据
        return http_fetch($call_api, $call_data);
    }

    # 获取永久素材（“video”视频素材、“news”图文素材）
    public function getMaterial($media_id, $debug=false)
    {
        $data = array('media_id' => $media_id);
        return $this->_call_api(self::API_GET, $data, $debug);
    }

    # 删除永久素材
    public function deleteMaterial($media_id, $debug=false)
    {
        $data = array('media_id' => $media_id);
        return $this->_call_api(self::API_DELETE, $data, $debug);
    }


    ############################################################
    # 视频素材操作

    # 添加视频素材
    public function addVideoMaterial($filepath, $title, $introduction, $debug=false)
    {
        $param = array(
            'description' => urldecode(json_encode(array(
                'title' => urlencode($title),
                'introduction' => urlencode($introduction)
            )))
        );
        return $this->_upload_material('video', $filepath, $param, $debug);
    }


    ############################################################
    # 图文素材操作

    /*
     *  添加图文素材
     *
     *  $article_list = array(
     *      array(
     *          'title'      => '',
     *          'thumb_id'   => '',
     *          'author'     => '',
     *          'summary'    => '',
     *          'show_cover' => '',
     *          'content'    => '',
     *          'source_url' => '',
     *      ),
     *  );
     */
    public function addNewsMaterial($article_list, $debug=false)
    {
        $data = $this->_build_news_data($article_list);
        return $this->_call_api(self::API_ADD_NEWS, $data, $debug);
    }

    /*
     *  更新图文素材
     *  ps：只能更新已存在的图文文章数据，不能通过增加索引号来添加文章
     *
     *  $article = array(
     *      'title'      => '',
     *      'thumb_id'   => '',
     *      'author'     => '',
     *      'summary'    => '',
     *      'show_cover' => '',
     *      'content'    => '',
     *      'source_url' => '',
     *  );
     */
    public function updateNewsMaterial($media_id, $index, $article, $debug=false)
    {
        $data = array(
            'media_id' => $media_id,
            'index'    => $index,
            'articles' => $this->_build_news_item($article),
        );
        return $this->_call_api(self::API_UPDATE_NEWS, $data, $debug);
    }


    ############################################################
    # 内部调用操作

    # 素材上传基本操作
    private function _upload_material($type, $filepath, $param=array(), $debug=false)
    {
        # 生成提交数据
        if (!file_exists($filepath)) return false;
        $files = array('media' => $filepath);
        $param['type'] = $type;

        # 生成接口调用地址
        $call_api = $this->_append_token(self::API_ADD_GENERAL);

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

    # 生成完整图文数据
    private function _build_news_data($article_list)
    {
        $data = array();
        foreach($article_list as $article) {
            $data[] = $this->_build_news_item($article);
            if (count($data) == 10) break;
        }
        return array('articles' => $data);
    }

    # 生成单个图文项
    private function _build_news_item($article)
    {
        return array(
            'title'              => urlencode($article['title']),
            'thumb_media_id'     => $article['thumb_id'],
            'author'             => urlencode($article['author']),
            'digest'             => urlencode($article['summary']),
            'show_cover_pic'     => $article['show_cover']? 1: 0,
            'content'            => urlencode($article['content']),
            'content_source_url' => $article['source_url'],
        );
    }

}

