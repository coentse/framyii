<?php namespace service\core\wechat;


class Menu extends AbstractCaller
{
    const API_QUERY   = "https://api.weixin.qq.com/cgi-bin/menu/get";
    const API_CREATE  = "https://api.weixin.qq.com/cgi-bin/menu/create";
    const API_DELETE  = "https://api.weixin.qq.com/cgi-bin/menu/delete";
    const API_GETINFO = "https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info";

    # 取得通过API设置的自定义菜单信息
    public function getMenuList($debug=false)
    {
        return $this->_call_api(self::API_QUERY, null, $debug);
    }

    /*
     * 设置自定义菜单
     *
     *  array(
     *      array('菜单类型', '菜单名称', '对应数据'),
     *      array('parent', '父菜单名称', array(
     *          array('click', '子菜单名称', '键值'),
     *          array('view', '子菜单名称', '网址'),
     *      ),
     *  );
     */
    public function setMenu($data, $debug=false)
    {
        $button_list = array();
        foreach($data as $entry)
        {
            list($type, $name, $value) = $entry;
            $name = urlencode($name);

            # 父级菜单处理
            if ($type == 'parent') {
                if (!is_array($value)) continue;

                # 生成子菜单按钮数据
                $sub_button = $this->_create_submenu($value);
                if (!$sub_button) continue;

                # 组合父菜单数据
                $button = array('name' => $name, 'sub_button' => $sub_button);
            }
            # 独立按钮处理
            else {
                $button = $this->_create_menu_item($type, $name, $value);
            }
            if (!$button) continue;

            # 增加菜单项
            $button_list[] = $button;

            # 当菜单项数量达到3个时不再处理后续数据
            if (count($button_list) == 3) break;
        }
        if (!$button_list) return NULL;

        return $this->_call_api(self::API_CREATE, array(
            'button' => $button_list
        ), $debug);
    }

    # 清除自定义菜单
    public function cleanMenu($debug=false)
    {
        return $this->_call_api(self::API_DELETE, null, $debug);
    }

    # 取得当前自定义菜单信息
    public function getMenuInfo($debug=false)
    {
        return $this->_call_api(self::API_GETINFO, null, $debug);
    }


    ############################################################
    # 内部调用操作

    # 生成子菜单数据
    private function _create_submenu($data)
    {
        $button_list = array();
        foreach ($data as $entry) {
            list($type, $name, $value) = $entry;
            $name = urlencode($name);
            $button = $this->_create_menu_item($type, $name, $value);
            if (!$button) continue;
            $button_list[] = $button;
            if (count($button_list) == 5) break;
        }
        return $button_list;
    }

    /*
     * 创建菜单项
     *
     * “$type”可选值：
     *      click：点击推送事件
     *      view：跳转URL
     *      scancode_push：扫码推送事件
     *      scancode_waitmsg：扫码推送事件且弹出“消息接收中”提示框
     *      system_photo：弹出系统拍照发图
     *      photo_or_album：弹出拍照或者相册发图
     *      wechat_pic：弹出微信相册发图器
     *      location_select：弹出地理位置选择器
     *      media：下发消息（除文本消息）
     *      view_limited：跳转图文消息URL
     */
    private function _create_menu_item($type, $name, $value)
    {
        switch($type) {
            case 'click':
                return array('type' => 'click', 'name' => $name, 'key' => $value);
            case 'view':
                return array('type' => 'view', 'name' => $name, 'url' => $value);
            case 'scancode_push':
                return array('type' => 'scancode_push', 'name' => $name, 'key' => $value);
            case 'scancode_waitmsg':
                return array('type' => 'scancode_waitmsg', 'name' => $name, 'key' => $value);
            case 'system_photo':
                return array('type' => 'pic_sysphoto', 'name' => $name, 'key' => $value);
            case 'photo_or_album':
                return array('type' => 'pic_photo_or_album', 'name' => $name, 'key' => $value);
            case 'wechat_pic':
                return array('type' => 'pic_weixin', 'name' => $name, 'key' => $value);
            case 'location_select':
                return array('type' => 'location_select', 'name' => $name, 'key' => $value);
            case 'media':
                return array('type' => 'media_id', 'name' => $name, 'media_id' => $value);
            case 'view_limited':
                return array('type' => 'view_limited', 'name' => $name, 'media_id' => $value);
        }
        return array();
    }

}


