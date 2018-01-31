<?php namespace service\core\controller;
/*
 * 作用：保存一个应用的相关设置
 *
 * 使用方法：
 * $config = ApplicationConfig::make('module', 'action', 'request_method');
 *
 * 设置调用方法
 * $config->callMethod('ClassName@methodName');
 *
 * 设置参数
 * $config->parameters(array(
 *      ParameterContainer::make('参数名称'[, '数据格式'])->value(参数对应值),
 *      ParameterContainer::make('参数名称'[, '数据格式'])->invoke('数据来源对象', '数据来源键值'[, '默认值']),
 *      ParameterContainer::make('参数名称'[, '数据格式'])->method('ClassName@methodName'),
 *      ...
 * ));
 *
 * 不进行输出：
 * $config->outputMode('none');
 *
 * 直接输出字符串：
 * $config->outputMode('string'[, '数组的键名']);
 *
 * 将普通数据转换为 JSON 格式后输出：
 * $config->outputMode('json');
 *
 * 将 ActionResult 转换为 JSON 格式后输出
 * $config->outputMode('apijson');
 *
 * 使用模板输出数据：
 * $config->outputMode('template', 'path::name');
 *
 * 跳转至指定的网址：
 * $config->outputMode('redirect', '默认跳转地址'[, '失败跳转地址']);
 * $config->outputMode('redirect', '@return_url'[, '@current_url']);
 *
 * 使用一个模板页面进行跳转：
 * $config->outputMode('page_redirect', '模板名称/路径', '默认跳转地址'[, '失败跳转地址']);
 *
 * 使用指定的方法来输出数据：
 * $config->outputMethod('ClassName@methodName');
 *
 * 设置调用方法时维持Session写操作（默认会关闭写操作，只能进行读操作）
 * $config->keepSessionWrite();
 *
 * 设置 Debug：
 * $config->debug(-1|-2|-3|-4|...);
 *     '-1': 输出传入的参数
 *     '-2': 输出返回的结果
 *     '-3': 输出当前应用设置
 *     '-4': 输出所有应用设置
 *    other: 应用内部约定
 *
 * 将当前的配置对象添加到 ApplicationConfigContainer 中
 * $config->attach();
 */


class ActionConfig implements \ArrayAccess
{
    ############################################################
    # 外部调用方法

    public function __construct($module, $action, $request_method=null)
    {
        # 该设置项对应的模块、操作
        $this->_module = strtolower($module);
        $this->_action = strtolower($action);

        # 该设置项对应的 http 请求方法
        if (is_null($request_method)) return;
        $request_method = strtolower($request_method);
        if (!in_array($request_method, array('get', 'post', 'put', 'delete'))) {
            throw new Error("http_request_method is not allowed ({$request_method})");
        }
        $this->_request_method = $request_method;
    }

    # 创建一个 ApplicationConfig 对象
    public static function make($module, $action, $request_method=null)
    {
        return new self($module, $action, $request_method);
    }

    # 将当前的 ApplicationConfig 对象添加到 ApplicationConfigContainer 中
    public function attach()
    {
        ActionConfigContainer::addConfig($this);
        return $this;
    }

    # 调试输出
    public function dump()
    {
        echo "<pre>\n";
        echo $this->_module .'::'. $this->_action ."\n";
        echo "----------\n";

        # Method
        echo "method: ". $this->_container['method'] ."\n";

        # Parameter
        echo "parameter:\n";
        if (is_array($this->_container['param'])) {
            foreach($this->_container['param'] as $item) {
                if (!$item instanceof Parameter) {
                    continue;
                }
                $_name   = $item->getParamName();
                $_source = $item->getDataSource();
                $_value  = $item->getData();
                if ($_source == 'invoke') {
                    echo "  ". $_name ." = (". $_value[0] .", ". $_value[1] .")\n";
                }
                else {
                    echo "  ". $_name ." = ". $_value ."\n";
                }
            }
        }

        # Output
        if (is_array($this->_container['output'])) {
            echo "output: (". implode(", ", $this->_container['output']) .")\n";
        }
        else {
            echo 'output: '. $this->_container['output'];
        }
        echo "\n<pre>";
        exit();
    }

    #
    # 创建
    #

    # 设置对应的调用方法
    public function callMethod($method=null)
    {
        if ($method) {
            $this->_container['method'] = $method;
        }
        return $this;
    }

    # 设置参数
    public function parameters($param=null)
    {
        if ($param) {
            $this->_container['param'] = $param;
        }
        return $this;
    }

    # 设置输出模式
    public function outputMode($mode=null)
    {
        if ($mode) {
            $this->_container['output'] = func_get_args();
        }
        return $this;
    }

    # 设置输出的调用方法
    public function outputMethod($method=null)
    {
        if ($method) {
            $this->_container['output'] = $method;
        }
        return $this;
    }

    # 设置调用方法时保持Session写操作
    public function keepSessionWrite()
    {
        $this->_container['write_session'] = True;
        return $this;
    }

    # 设置 Debug 级别
    public function debug($level=null)
    {
        $this->_debug = $level;
        return $this;
    }

    #
    # 使用
    #

    # 取得该设置项对应的模块、操作
    public function getModuleAction()
    {
        return array($this->_module, $this->_action);
    }

    # 取得指定的 http 请求模式
    public function getRequestMethod()
    {
        return $this->_request_method;
    }

    # 检测是否需要保持Session写操作
    public function checkSessionWrite()
    {
        return $this->_container['write_session'];
    }

    # 取得 Debug 级别
    public function getDebugLevel()
    {
        return $this->_debug;
    }


    ############################################################
    # “数组式访问”接口实现

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_container[] = $value;
        }
        else {
            $this->_container[$offset] = $value;
        }
    }

    public function offsetGet($offset)
    {
        if (isset($this->_container[$offset])) {
            return $this->_container[$offset];
        }
        return null;
    }

    public function offsetExists($offset)
    {
        return isset($this->_container[$offset]);
    }

    public function offsetUnset($offset )
    {
        unset($this->_container[$offset]);
    }

    ############################################################
    # 内部属性

    private $_debug;
    private $_module;
    private $_action;
    private $_request_method;
    private $_container = array();

}


