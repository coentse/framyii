<?php

$params = [
    # 应用程序的访问网址
    'applicationURL' => 'url',

    # 文件存储路径
    'fileStoragePath' => 'filePath',

    # 接口服务相关配置
    'apiServiceClient' => [
        'client' => [
            '1.1.1.1' => '1234567890-1234567890-1234567890',
            '2.2.2.2' => '1234567890-1234567890-1234567890',
        ],
    ],

    # 数据收集器数据获取频率初始值
    # 可选单位：day、week
    'gathererInitialFrequency' => [
        'business_data'             => [4, 'week'],
        'manage_exception'          => [4, 'week'],
        'court_announcement'        => [4, 'week'],
        'court_judgement'           => [4, 'week'],
        'court_execution'           => [4, 'week'],
        'court_dishonest_people'    => [4, 'week'],
    ],

    # 数据表别名配置文件设置
    'tableAliases'  => [
        'temp' => 'temp.php',
    ],

    # 应用程序相关路径配置
    'configPath'    => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config',
    'includePath'   => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'include',
    'templatePath'  => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'template',

    # 需要加载的必需文件列表
    'necessaryFiles' => ['statement.php', 'function.php', 'xxtea.php'],

    # 服务相关配置
    'serviceNamespacePrefix'  => 'service',
    'serviceConfigRootPath'   => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'include',

];

return $params;

