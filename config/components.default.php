<?php

$components = [

    # 配置日志记录器组件
    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'targets' => [
            'file' => [
                'class'  => 'yii\log\FileTarget',
                'levels' => ['error', 'warning', 'info'],
            ],
        ],
    ],


    ############################################################
    # 数据库组件设置

    'defaultDB' => [
        'class'    => 'yii\db\Connection',
        'dsn'      => 'mysql:host={SERVER_ADDRESS};dbname={DB_NAME}',
        'username' => '{USERNAME}',
        'password' => '{PASSWORD}',
        'charset'  => 'utf8',
    ],


    ############################################################
    # 模板引擎配置

    'view' => [
        'class'     => 'yii\web\View',
        'renderers' => [
            'twig' => [
                'class'     => 'yii\twig\ViewRenderer',
                'cachePath' => '@runtime/twig',
                'options'   => [
                    'auto_reload' => true,
                ],
                'globals'   => ['html' => '\yii\helpers\Html'],
                'uses'      => ['yii\bootstrap'],

                # 自定义模板函数
                'functions' => [
                    'dump'          => 'dump',
                    'make_url'      => 'make_url',
                    'return_url'    => 'get_return_url',
                    'current_url'   => 'get_current_url',
                    'check_path'    => 'check_path',
                    'check_module'  => 'check_module',
                    'check_action'  => 'check_action',
                ],
            ],
        ],
    ],

];

# 配置请求组件
if (SITE_ID != 'cron') {
    $components['request'] = [
        'enableCsrfValidation' => false,        # 关闭 CSRF 检测
        'cookieValidationKey'  => '{11111-22222-33333-44444}',
                                                # 在不同运行环境下需要修改此参数
    ];
}

return $components;

