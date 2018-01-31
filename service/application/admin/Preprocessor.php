<?php namespace service\application\admin;


class Preprocessor
{

    # 运行预处理器
    static public function run($module, $action)
    {
        unset($module, $action);

        # 验证客户端操作许可
        if (YII_ENV != 'dev') {
            return '功能暂不对外开放！';
        }

        return true;
    }


    ############################################################
    # 内部调用操作


}

