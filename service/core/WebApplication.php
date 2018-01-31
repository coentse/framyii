<?php namespace service\core;

use yii;
use yii\base\InvalidConfigException;
use yii\web\Application as YiiWebApplication;
use service\core\db\DatabaseQuery;

class WebApplication extends YiiWebApplication
{

    ############################################################
    # 修改原来的应用程序逻辑

    /**
     * 构造函数
     *
     * @param array $config name-value pairs that will be used to initialize the object properties.
     * Note that the configuration must contain both [[id]] and [[basePath]].
     * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        # 加载必需文件
        $this->_load_necessary_file();

        # 加载数据表别名
        $this->_load_table_alias();
    }

    /*
     * 创建一个控制器调度器实例
     *
     * @param string $route the route consisting of module, controller and action IDs.
     * @return array|boolean If the controller is created successfully, it will be returned together
     * with the requested action ID. Otherwise false will be returned.
     * @throws InvalidConfigException if the controller class and its file do not match.
     */
    public function createController($route)
    {
        if ($route === '') {
            $route = $this->defaultRoute;
        }

        // double slashes or leading/ending slashes may cause substr problem
        $route = trim($route, '/');
        if (strpos($route, '//') !== false) {
            return false;
        }

        if (strpos($route, '/') !== false) {
            list ($id, $route) = explode('/', $route, 2);
        } else {
            $id = $route;
            $route = '';
        }

        // module and controller map take precedence
        if (isset($this->controllerMap[$id])) {
            $controller = Yii::createObject($this->controllerMap[$id], [$id, $this]);
            return [$controller, $route];
        }
        $module = $this->getModule($id);
        if ($module !== null) {
            return $module->createController($route);
        }

        if (($pos = strrpos($route, '/')) !== false) {
            $id .= '/' . substr($route, 0, $pos);
            $route = substr($route, $pos + 1);
        }

        ############################################################
        # 自定义逻辑开始
        $controller = $this->_create_dispatcher($id);
        # 自定义逻辑结束
        ############################################################

        return $controller === null ? false : [$controller, $route];
    }


    ############################################################
    # 自定义应用程序逻辑

    /* @var string 默认控制器调度器 */
    public $defaultDispatcher = 'Web';

    # 加载必需文件
    private function _load_necessary_file()
    {
        # 取得要加载的必需文件列表
        $file_list = $this->params['necessaryFiles'];
        if (!is_array($file_list) || !$file_list) return;

        # 依次加载文件
        $include_path = $this->params['includePath'];
        foreach($file_list as $filename) {
            $filepath = $include_path . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($filepath)) {
                /** @noinspection PhpIncludeInspection */
                include $filepath;
            }
        }
    }

    # 加载数据表别名
    private function _load_table_alias()
    {
        # 取得数据表别名配置
        $alias_list = $this->params['tableAliases'];
        if (!is_array($alias_list) || !$alias_list) return;

        # 依次加载数据表别名文件
        $include_path = $this->params['includePath'];
        foreach($alias_list as $handle_key => $filename) {
            $filepath = implode(DIRECTORY_SEPARATOR, [$include_path, 'tablealias', $filename]);
            if (file_exists($filepath)) {
                /** @noinspection PhpIncludeInspection */
                DatabaseQuery::set_global_alias_map(include $filepath, $handle_key);
            }
        }
    }

    /*
     * 根据指定的控制器ID创建控制器调度器对象
     *
     * @param string $id 控制器ID
     */
    private function _create_dispatcher($id)
    {
        # 组合调度器对应类的完整限定名称
        $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $this->defaultDispatcher))) . 'Dispatcher';
        $className = ltrim($this->controllerNamespace . '\\' . $className, '\\');
        if (strpos($className, '-') !== false || !class_exists($className)) {
            return null;
        }

        if (is_subclass_of($className, 'yii\base\Controller')) {
            $controller = Yii::createObject($className, [$id, $this]);
            return get_class($controller) === $className ? $controller : null;
        } elseif (YII_DEBUG) {
            throw new InvalidConfigException("Controller class must extend from \\yii\\base\\Controller.");
        } else {
            return null;
        }
    }

}


