<?php namespace service\core\controller;
/**
 * 数据存储结构：
 * array(
 *      'moduleName' => array(
 *          'actionName' => **ActionConfig instances**,
 *          'actionName' => array(
 *              'get'    => **ActionConfig instances**,
 *              'post'   => **ActionConfig instances**,
 *              'put'    => **ActionConfig instances**,
 *              'delete' => **ActionConfig instances**,
 *          ),
 *          ...
 *      ),
 *      ...
 * );
 *
 * 添加应用配置：
 *
 *   方法一：
 *   $ac = new ActionConfig('module', 'action', 'request_method');
 *   $ac->balabala...
 *   ActionConfigContainer::addAppConfig($ac);
 *
 *   方法二：
 *   $ac = new ActionConfig('module', 'action', 'request_method');
 *   $ac->balabala...
 *   $ac->attach()
 *
 * 获取应用配置：
 * ActionConfigContainer::loadConfig($modules);
 * $config = ActionConfigContainer::getConfig($modules, $action);
 *
 */

use yii;

class ActionConfigContainer
{
    ############################################################
    # 外用调用方法

    # 加载动作配置文件
    public static function loadConfig($modules)
    {
        # 取得当前站点的ID, 服务配置文件存储的根目录
        $site_id   = SITE_ID;
        $root_path = Yii::$app->params['serviceConfigRootPath'];

        # 取得当前模块配置文件的相对路径
        $relative_path = self::_process_module_path($modules) .'.php';
        $relative_path = $site_id . DIRECTORY_SEPARATOR . $relative_path;

        # 组合当前模块配置文件的绝对路径
        $absolute_path = $root_path . DIRECTORY_SEPARATOR . $relative_path;

        if (!file_exists($absolute_path)) {
            throw new UnknownModule($relative_path);
        }

        # 加载当前模块的配置文件
        /** @noinspection PhpIncludeInspection */
        include $absolute_path;
    }

    # 添加一个 App 设置项
    public static function addConfig($setting)
    {
        # 获得该设置项对应的模块、操作、http请求模式
        /* @var $setting ActionConfig */
        list($module, $action) = $setting->getModuleAction();
        $request_method = $setting->getRequestMethod();

        # 未指定 http 请求模式时的处理
        if (is_null($request_method))
        {
            # 检测该操作是否已经设置了 http 请求模式
            if (is_array(self::$_container[$module][$action])) {
                throw new Error("MapData setting conflicts ({$module}/{$action})");
            }
            if (self::$_container[$module][$action] instanceof ActionConfig) {
                throw new Error("MapData setting conflicts ({$module}/{$action})");
            }

            self::$_container[$module][$action] = $setting;
        }
        # 指定了 http 请求模式时的处理
        else
        {
            # 检测该操作是否已经被设置过
            if (self::$_container[$module][$action] instanceof ActionConfig) {
                throw new Error("MapData setting conflicts ({$module}/{$action}::{$request_method})");
            }
            if (self::$_container[$module][$action][$request_method] instanceof ActionConfig) {
                throw new Error("MapData setting conflicts ({$module}/{$action}::{$request_method})");
            }

            self::$_container[$module][$action][$request_method] = $setting;
        }
    }

    # 根据路由取得当前环境对应的 App 设置项
    public static function getConfig($module, $action, $request_method)
    {
        $request_method = strtolower($request_method);

        # 判断当前模块是否存在
        if (!array_key_exists($module, self::$_container)) {
            throw new UnknownAction($module);
        }

        # 判断当前操作是否存在
        if (!array_key_exists($action, self::$_container[$module])) {
            throw new UnknownAction($module .'/'. $action);
        }

        # 如果不需要检测 http 请求模式，则直接返回对象
        if (!is_array(self::$_container[$module][$action])) {
            return self::$_container[$module][$action];
        }

        # 检测 http 请求模式
        if (!array_key_exists($request_method, self::$_container[$module][$action])) {
            throw new UnknownAction($module .'/'. $action .'@'. $request_method);
        }

        # 返回对应的对象
        return self::$_container[$module][$action][$request_method];
    }

    # 调试输出
    public static function dump()
    {
        echo "<pre>\n";
        foreach(self::$_container as $m_name => $module) {
            echo "Module: {$m_name}\n";
            if (!is_array($module)) continue;
            foreach($module as $a_name => $action) {
                if (is_array($action)) {
                    $request_list = implode(',', array_keys($action));
                    echo "  Action: {$a_name} ({$request_list})\n";
                }
                else {
                    echo "  Action: {$a_name}\n";
                }
            }
        }
        echo "</pre>\n";
        exit();
    }


    ############################################################
    # 内部属性

    private static $_container = array();

    # 处理模块名称
    private static function _process_module_path($modules)
    {
        $module_list = explode('/', strtolower($modules));
        return implode(DIRECTORY_SEPARATOR, $module_list);
    }

}


