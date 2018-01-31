<?php namespace service\core;

use Yii;
use yii\base\Action;
use yii\base\Response;
use yii\base\InlineAction;
use yii\base\InvalidRouteException;
use yii\web\Controller as YiiWebController;
use service\core\controller\ActionConfig;
use service\core\controller\ActionConfigContainer;

class WebDispatcher extends YiiWebController
{

    ############################################################
    # 修改原来的控制器的逻辑

    /**
     * @var string Action的默认ID
     */
    public $defaultAction = 'default';

    /**
     *
     * @param string $id the ID of the action to be executed.
     * @param array $params the parameters (name-value pairs) to be passed to the action.
     * @return mixed the result of the action.
     * @throws InvalidRouteException if the requested action ID cannot be resolved into an action successfully.
     * @see createAction()
     */
    public function runAction($id, $params = [])
    {
        return parent::runAction($id, $params);
    }

    /**
     *
     * @param string $id the action ID.
     * @return Action the newly created action instance. Null if the ID doesn't resolve into any action.
     */
    public function createAction($id)
    {
        if ($id === '') {
            $id = $this->defaultAction;
        }

        $actionMap = $this->actions();
        if (isset($actionMap[$id])) {
            return Yii::createObject($actionMap[$id], [$id, $this]);
        } elseif (preg_match('/^[a-z0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
            ############################################################
            # 自定义逻辑开始
            $methodName = 'action' . $this->invokerName;
            # 自定义逻辑结束
            ############################################################

            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    return new InlineAction($id, $this, $methodName);
                }
            }
        }

        return null;
    }


    ############################################################
    # 自定义控制器逻辑

    /*
     * @var string 默认方法调用器
     */
    public $invokerName = 'Invoker';
    public function actionInvoker()
    {
        # 取得当前站点ID和对应的模块和操作 (即Yii中的控制器ID和操作ID)
        $yii_app_id = strtolower(Yii::$app->id);
        $site_id    = SITE_ID;
        $module     = $this->id;
        $action     = $this->action->id;

        # 启用Session功能
        $this->_enable_session($yii_app_id, $site_id);

        # 调用当前站点的预处理器
        $_result = $this->_call_preprocessor($site_id, $module, $action);
        if ($_result instanceof Response) return $_result;
        if ($_result !== true) {
            Yii::$app->response->statusCode = 500;
            return $_result;
        }
        unset($_result);

        # 取得当前请求的请求模式
        $request_method = Yii::$app->request->getMethod();

        # 加载操作配置
        ActionConfigContainer::loadConfig($module);
        $actionConfig = ActionConfigContainer::getConfig($module, $action, $request_method);

        # 检测是否正确取得设置项
        if (!$actionConfig instanceof ActionConfig) {
            throw new controller\Error("can not found action setting!");
        }

        # 关闭Session写操作
        if (!$actionConfig->checkSessionWrite()) {
            session_write_close();
        }

        # 取得调试级别
        $debug = $actionConfig->getDebugLevel();

        # 调试输出
        if ($debug == -4) ActionConfigContainer::dump();
        if ($debug == -3) $actionConfig->dump();

        # 解析调用参数
        $param = $this->_parse_action_param($actionConfig);
        if ($debug == -1) dump($param);

        # 调用对应的方法
        if ($actionConfig['method']) {
            $result = $this->_call_action_method($site_id, $actionConfig['method'], $param, $debug);
        }
        else {
            $result = null;
        }
        if ($debug == -2) dump($result);
        if ($result instanceof Response) return $result;

        # 根据结果创建响应对象并返回
        return controller\ResponseBuilder::build($actionConfig, $result, $param);
    }


    ############################################################
    # 自定义控制器逻辑

    # 启用Session功能
    private function _enable_session($yii_app_id, $site_id)
    {
        # 启用Yii的Session功能
        Yii::$app->session->open();

        # 生成SessionID
        $session_id = '_'. $yii_app_id .'_'. $site_id;

        # 设置SessionID
        SystemVariable::setSessionID($session_id);
    }

    # 调用当前站点的预处理器
    private function _call_preprocessor($site_id, $module, $action)
    {
        # 取得服务程序的命名空间前缀
        $ns_prefix = Yii::$app->params['serviceNamespacePrefix'];

        # 组合预处理器的完整限定名
        $class_name = $ns_prefix . '\\application\\' . $site_id . '\\Preprocessor';
        if (class_exists($class_name) && method_exists($class_name, 'run')) {
            return $class_name::run($module, $action);
        }

        return true;
    }

    # 解析操作参数
    private function _parse_action_param($actionConfig)
    {
        if (!isset($actionConfig['param'])) {
            return array();
        }
        return controller\ParameterParser::parseParam($actionConfig['param']);
    }

    # 调用操作对应的方法
    private function _call_action_method($site_id, $method_phrase, &$param, $debug)
    {
        # 取得服务程序的命名空间前缀
        $ns_prefix = Yii::$app->params['serviceNamespacePrefix'];

        # 处理方法短语中的特殊符号
        if (substr($method_phrase, 0, 1) != '\\') {
            $method_phrase = $ns_prefix . '\\application\\' . $site_id . '\\' . $method_phrase;
        }

        # 拆分出类名称、方法名称
        list($class_name, $method_name) = explode('@', $method_phrase);

        # 判断类对象
        if (!class_exists($class_name)) {
            throw new controller\Error("can't found class: {$class_name}");
        }

        # 创建类对象
        $object = new $class_name;

        # 判断类方法
        if (!method_exists($object, $method_name)) {
            throw new controller\Error("can't found method: {$class_name}->{$method_name}");
        }

        # 调用类方法，并返回结果
        return call_user_func_array(array($object, $method_name), array(&$param, $debug));
    }

}

