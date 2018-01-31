<?php namespace service\core\controller;
/*
 * 可供使用的输出设置格式：
 *
 * 1. "ClassName@methodName"
 * 功能说明：调用指定的方法来输出数据
 * 数据格式：一个变量/对象
 *
 * 2. array('none')
 * 功能说明：不进行输出
 *
 * 3. array('string'[, '数组的键名'])
 * 功能说明：如果结果为字符串则直接输出，如果为数组则输出指定键名的对应值
 * 数据格式：array 返回数据
 *
 * 4. array('json')
 * 功能说明：将数据转换为 JSON 格式后输出
 * 数据格式：mixed 返回数据
 *
 * 5. array('apijson')
 * 功能说明：将 ActionResult 转换为符合 API 标签的 JSON 格式后输出（{"result": true|false, "message": '...', "data": xxx}）
 * 数据格式：mixed 返回数据
 *
 * 6. array('template', '模板名称/路径')
 * 功能说明：显性模板输出；模板地址已事先指定好
 * 数据格式：array 返回数据
 *
 * 7. array('redirect', '默认跳转地址'[, '失败跳转地址'])
 *    array('redirect', '@return_url'[, '@current_url']);
 * 功能说明：显性跳转；跳转地址已事先指定好
 * 数据格式：object ActionResult
 *
 * 8. array('page_redirect', '模板名称/路径', '默认跳转地址'[, '失败跳转地址'])
 *    array('page_redirect', '模板名称/路径', '@return_url'[, '@current_url']);
 * 功能说明：调用一个模板页面来进行跳转操作；会向模板页面传递以下变量：
 *          bool   result       操作结果
 *          string message      结果信息
 *          string url          跳转地址
 *          string last_code    最后结果代码
 *          string last_message 最后结果信息
 * 数据格式：object ActionResult
 *
 */

use yii;
use service\core\Template;

class ResponseBuilder
{
    /* @var string 当前站点的ID */
    private static $_site_id;

    /*
     * 生成响应数据
     *
     * @param ActionConfig  $actionConfig
     * @param array|ActionResult  $result
     * @param array  $param
     */
    public static function build($actionConfig, $result, $param)
    {
        self::$_site_id = SITE_ID;

        if (is_null($actionConfig['output'])) {
            throw new Error("can't found Output setting");
        }

        # 处理内置的输出规则
        if (is_array($actionConfig['output'])) {
            return self::_process_output_rule($actionConfig['output'], $result, $param);
        }
        # 调用其它数据输出方法
        else {
            return self::_call_output_method($actionConfig['output'], $result, $param);
        }
    }


    ############################################################
    # 内部调用操作

    /*
     *
     */
    private static function _call_output_method($method_phrase, $result, $param)
    {
        # 取得服务程序的命名空间前缀
        $ns_prefix = Yii::$app->params['serviceNamespacePrefix'];

        # 方法字符串格式检测
        if (strpos($method_phrase, '@') === false) {
            unset($data);
            throw new Error("output format error ({$method_phrase})");
        }

        # 处理方法短语中的特殊符号
        if (substr($method_phrase, 0, 1) != '\\') {
            $method_phrase = $ns_prefix . '\\application\\' . self::$_site_id . '\\' . $method_phrase;
        }

        # 查找当前操作对应的类名称、方法名称
        list($class_name, $method_name) = explode('@', $method_phrase);

        # 判断类对象
        if (!class_exists($class_name)) {
            unset($data);
            throw new Error("can't found class: {$class_name}");
        }

        # 创建类对象
        $object = new $class_name;

        # 判断类方法
        if (!method_exists($object, $method_name)) {
            unset($data);
            throw new Error("can't found method: {$class_name}->{$method_name}");
        }

        # 调用类方法
        return call_user_func(array($object, $method_name), $result, $param);
    }

    /*
     *
     */
    private static function _process_output_rule($rule, $result, $param)
    {
        $operate = $rule[0];
        switch($operate)
        {
            case 'dump':
                return dump($result);
            case 'string':
                return self::_processStringRule($rule, $result);
            case 'json':
                return self::_processJSONRule($result);
            case 'apijson':
                return self::_processAPIJSONRule($result);
            case 'redirect':
                return self::_processRedirectRule($rule, $result);
            case 'page_redirect':
                return self::_processPageRedirectRule($rule, $result);
            case 'template':
                return self::_processTemplateRule($rule, $result, $param);
        }
        return null;
    }


    ############################################################
    # 各种输出规则处理

    # String 输出规则处理
    private static function _processStringRule($rule, $data)
    {
        if ($data instanceof ActionResult) {
            dump_msg($data->getMessage(), $data->getLastCode());
        }
        if (is_array($data) && count($rule) > 1) {
            return $data[$rule[1]];
        }
        else {
            return strval($data);
        }
    }

    # JSON 输出规则处理
    private static function _processJSONRule($data)
    {
        if ($data instanceof ActionResult) {
            dump_msg($data->getMessage(), $data->getLastCode());
        }
        return json_encode($data);
    }

    # API JSON 输出规则处理
    private static function _processAPIJSONRule($data)
    {
        # 结果类型为 ActionResult 时的处理
        if ($data instanceof ActionResult) {
            return json_encode([
                'result'    => $data->getResult(),
                'code'      => $data->getLastCode(),
                'message'   => $data->getLastMessage(),
                'fullmsg'   => $data->getMessage()
            ]);
        }
        # 结果为其它类型时的处理
        else {
            return json_encode(array('result' => true, 'data' => $data));
        }
    }

    # 网址跳转规则处理
    private static function _processRedirectRule($rule, $result)
    {
        # 参数、数据检测
        if (count($rule) < 2) {
            throw new Error("return data define error (Redirect)");
        }
        if (!$result instanceof ActionResult) {
            throw new Error("return data format error (ActionResult)");
        }

        # 判断要跳转的地址
        if (count($rule) > 2) {
            $url = $result->getResult()? $rule[1]: $rule[2];
        }
        else {
            $url = $rule[1];
        }

        # 地址处理
        if (substr($url, 0, 1) == '@') {
            $url = ($url == '@return_url')? get_return_url(): get_current_url(false);
        }

        # 跳转提示信息
        $message = $result->getMessage("；");

        # 跳转操作
        if ($message) {
            redirect($url, $result->getMessage("；"));
        }
        else {
            Yii::$app->getResponse()->redirect($url);
        }
        return null;
    }

    # 页面跳转规则处理
    private static function _processPageRedirectRule($rule, $action_result)
    {
        # 参数、数据检测
        if (count($rule) < 3) {
            throw new Error("return data define error (PageRedirect)");
        }
        if (!$action_result instanceof ActionResult) {
            throw new Error("return data format error (ActionResult)");
        }

        # 取得要显示的模板文件名
        $template = $rule[1];

        # 判断要跳转的地址
        if (count($rule) > 3) {
            $url = $action_result->getResult()? $rule[2]: $rule[3];
        }
        else {
            $url = $rule[2];
        }

        # 地址处理
        if (substr($url, 0, 1) == '@') {
            $url = ($url == '@return_url')? get_return_url(): get_current_url(false);
        }

        # 组织数据，调用模板
        $_data = array(
            'url'       => $url,
            'result'    => $action_result->getResult(),
            'code'      => $action_result->getLastCode(),
            'message'   => $action_result->getLastMessage(),
            'fullmsg'   => $action_result->getMessage(),
        );

        $_setting = array('template', $template);
        return self::_processTemplateRule($_setting, $_data, null);
    }

    # 模板显示规则处理
    private static function _processTemplateRule($rule, $result, $param)
    {
        if ($result instanceof ActionResult) {
            dump_msg($result->getMessage(), $result->getLastCode());
        }

        # 参数检测
        if (count($rule) < 2) {
            throw new Error("return data define error (Template)");
        }

        # 组织模板数据
        $data = is_null($result)? array(): $result;
        if (!is_array($data)) {
            throw new Error("return data format error (Array)");
        }
        $data['_param'] = $param;

        return Template::render($rule[1], $data);
    }

}


