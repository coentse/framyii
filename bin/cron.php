#!/usr/bin/env php
<?php
define('SITE_ID', 'cron');
############################################################
# 环境初始化操作

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
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

# 设置服务程序路径别名
Yii::setAlias('@service', realpath(__DIR__ .'/../service'));

# 创建应用程序
$cfg = include __DIR__ . '/../config/yii.php';
$cfg['controllerNamespace'] = 'app\\consoles';
$app = new service\core\ConsoleApplication($cfg);
$app->run();

