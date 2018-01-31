<?php namespace service\core;

use yii;

class Template
{

    static public function render($view_name, $param=[])
    {
        # 设置参数对象
        self::$_param = $param;
        unset($param);

        # 设置CSRF校验令牌
        self::_set_yii_csrf_token();

        # 合并模板共享数据
        self::_merge_share_template_data();

        # 取得模板文件根目录，并在Yii中设置为别名“@template”
        self::_init_view_root_path();

        # 取得模板文件的完整路径
        $file_path = self::_get_view_file_path($view_name);

        # 显示模板
        return Yii::$app->controller->view->renderFile($file_path, self::$_param);
    }


    ############################################################
    # 内部调用操作

    static private $_param;
    static private $_root_path;

    # 设置CSRF校验令牌
    static private function _set_yii_csrf_token()
    {
        self::$_param['_csrf_token'] = Yii::$app->request->getCsrfToken();
    }

    # 合并模板共享数据
    static private function _merge_share_template_data()
    {
        # 取得模板共享数据
        $share_data = GlobalObject::get('_tpl_share_data');
        if (!is_array($share_data) || !$share_data) return;

        # 合并数据
        $current_keys = array_keys(self::$_param);
        foreach($share_data as $key => $val) {
            if (!in_array($key, $current_keys)) {
                self::$_param[$key] = $val;
            }
        }
    }

    # 取得模板文件根路径，并在Yii中设置为别名“@template”
    static private function _init_view_root_path()
    {
        # 组合根路径
        $site_id  = SITE_ID;
        $tpl_root = Yii::$app->params['templatePath'];
        self::$_root_path = implode(DIRECTORY_SEPARATOR, [$tpl_root, $site_id]);

        # 在Yii中将模板文件根路径设置为别名“@template”
        Yii::setAlias('@template', self::$_root_path);
    }

    # 取得模板文件的完整路径
    static private function _get_view_file_path($view_name)
    {
        $view_name  = str_replace('::', DIRECTORY_SEPARATOR, $view_name);
        $file_path  = implode(DIRECTORY_SEPARATOR, array(self::$_root_path, $view_name));
        $file_path .= '.twig';
        return $file_path;
    }

}


