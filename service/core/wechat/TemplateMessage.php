<?php namespace service\core\wechat;


class TemplateMessage extends AbstractCaller
{
    const API_SEND_MESSAGE = "https://api.weixin.qq.com/cgi-bin/message/template/send";
    const API_ADD_TEMPLATE = "https://api.weixin.qq.com/cgi-bin/template/api_add_template";
    const API_SET_INDUSTRY = "https://api.weixin.qq.com/cgi-bin/template/api_set_industry";

    # 设置所属行业
    public function setIndustry($industry_id1, $industry_id2, $debug=false)
    {
        $data = array(
            'industry_id1' => $industry_id1,
            'industry_id2' => $industry_id2
        );
        return $this->_call_api(self::API_SET_INDUSTRY, $data, $debug);
    }

    # 添加模板
    public function addTemplate($short_tpl_id, $debug=false)
    {
        $data = array('template_id_short' => $short_tpl_id);
        return $this->_call_api(self::API_ADD_TEMPLATE, $data, $debug);
    }

    /*
     * 发送消息
     * @param string    $open_id        接收人的OpenId
     * @param string    $template_id    模板消息ID
     * @param array     $data           模板数据
     * @param string    $url            相关网址
     * @param bool      $debug          调试标记位
     *
     *  $data = array(
     *      DataKey1 => DataValue1,
     *      DataKey2 => array(DataValue2, Color),
     *      ...
     *  )
     */
    public function sendMessage($open_id, $template_id, $data, $url, $debug=false)
    {
        # 组合消息数据
        $message = array(
            'touser'      => $open_id,
            'template_id' => $template_id,
            'url'         => $url,
            'data'        => $this->_build_msg_data($data),
        );

        # 调用接口
        return $this->_call_api(self::API_SEND_MESSAGE, $message, $debug);
    }


    ############################################################
    # 内部调用操作

    # 生成消息数据
    private function _build_msg_data($data) {
        $array = array();
        foreach($data as $key => $entry) {
            if (is_array($entry)) {
                $array[$key]['value'] = $entry[0];
                if ($entry[1]) {
                    $array[$key]['color'] = $entry[1];
                }
            }
            else {
                $array[$key]['value'] = $entry;
            }
        }
        return $array;
    }

}


