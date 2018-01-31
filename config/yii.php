<?php

# 设置应用配置
$config = [
    'id'            => 'bims',
    'version'       => '0.1',
    'timeZone'      => 'Asia/Chongqing',
    'basePath'      => dirname(__DIR__),
    'runtimePath'   => dirname(__DIR__) . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['runtime', SITE_ID]),
    'defaultRoute'  => 'default',
    'bootstrap'     => ['log'],
    'params'        => include __DIR__ . '/params.php',
    'components'    => include __DIR__ . '/components.php',
];

# 针对开发环境调整配置项
if (YII_ENV_DEV && SITE_ID != 'cron')
{
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;

