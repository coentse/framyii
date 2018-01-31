<?php
define('SITE_ID', 'api');
############################################################

# 设置工作环境
if (@file_exists('/production_server')) {
    defined('YII_ENV')   or define('YII_ENV',   'prod');
    defined('YII_DEBUG') or define('YII_DEBUG', false);
} elseif (@file_exists('/test_server')) {
    defined('YII_ENV')   or define('YII_ENV',   'test');
    defined('YII_DEBUG') or define('YII_DEBUG', true);
} else {
    defined('YII_ENV')   or define('YII_ENV',   'dev');
    defined('YII_DEBUG') or define('YII_DEBUG', true);
}

# 加载引导文件
require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');

# 设置服务程序路径别名
Yii::setAlias('@service', realpath(__DIR__ .'/../../service'));

# 创建应用程序并运行
use service\core\WebApplication;
use service\core\controller\ParameterError;
use service\core\controller\ParameterFormatError;
$app = new WebApplication(
    include __DIR__ . '/../../config/yii.php'
);
try {
    $app->run();
} catch (ParameterError $e) {
    dump_json([
        'result'     => false,
        'error_code' => 'missing_parameter',
        'error_msg'  => $e->getMessage()
    ]);
} catch (ParameterFormatError $e) {
    dump_json([
        'result'     => false,
        'error_code' => 'parameter_format_error',
        'error_msg'  => $e->getMessage()
    ]);
}


