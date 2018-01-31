<?php namespace app\consoles;

use service\core\ConsoleController;

class DefaultController extends ConsoleController
{

    # 默认操作
    public function actionDefault()
    {
        echo "./cron gen-task   # 生成采集任务\n";
        echo "./cron gather     # 执行采集操作\n";
    }

}


