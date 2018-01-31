<?php namespace service\core;

use Yii;
use yii\console\Controller;

class ConsoleController extends Controller
{

    ############################################################
    # 修改原来的控制器的逻辑

    /**
     * @var string Action的默认ID
     */
    public $defaultAction = 'default';


    ############################################################
    # 增加自定义函数

    protected function _log($message) {
        echo date("Y-m-d H:i:s - ");
        print_r($message ."\n");
        Yii::error($message);
    }

}


