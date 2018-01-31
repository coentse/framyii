<?php namespace service\core\controller;
/**
 * 作用：保存一个参数的相关设置
 *
 * 使用方法：
 * $pc = Parameter::make('参数名称'[, '数据格式']);
 *
 *   可选“数据格式”：
 *   raw | rawstr, int, bool, float, idlist | base, number, on-off, email, domain, ip, date, time, datetime, array
 *   默认情况下参数检查器允许数据为空（即数据为 NULL 或空字符串时不进行检测），如果需要强制数据不能为空，
 *   则需要在数据格式前加上一个叹号“!”，例如“!int”、“!base”
 *
 * 直接设置参数对应值
 * $pc->value(参数对应值);
 *
 * 设置引用参数
 * $pc->invoke('数据来源对象', '数据来源键值'[, '默认值']);
 *
 *   可选“数据来源对象”：
 *    global: “GlobalObject”中的对象
 *    server: “$_SERVER”中的对象
 *   session: “$_SESSION”中的对象
 *    cookie: “$_COOKIE”中的对象
 *       get: “$_GET”中的对象
 *      post: “$_POST”中的对象
 *   rawpost: “php://input”中的对象
 *      file: “$_FILES”中的对象
 *
 * 设置调用方法
 * $pc->method('ClassName@methodName');
 */

class Parameter
{
    ############################################################
    # 外部调用方法

    public function __construct($name, $format=null)
    {
        $this->_name = $name;
        if (!is_null($format)) {
            $this->_format = $format;
        }
    }

    #
    # 创建
    #

    # 创建一个参数容器
    public static function make($name, $format=null)
    {
        return new Parameter($name, $format);
    }

    # 设置-直接赋值
    public function value($value)
    {
        $this->_source = 'value';
        $this->_data = $value;
        return $this;
    }

    # 设置-调用方法
    public function method($method)
    {
        $this->_source = 'method';
        $this->_data = $method;
        return $this;
    }

    # 设置-参数引用
    public function invoke($source, $key=null, $default=null)
    {
        $this->_source = 'invoke';
        if (is_null($default)) {
            $this->_data = array($source, $key);
        }
        else {
            $this->_data = array($source, $key, $default);
        }
        return $this;
    }

    #
    # 使用
    #

    # 取得参数名称
    public function getParamName()
    {
        return $this->_name;
    }

    # 取得数据格式
    public function getDataFormat()
    {
        return $this->_format;
    }

    # 取得数据来源
    public function getDataSource()
    {
        return $this->_source;
    }

    # 取得参数数据
    public function getData()
    {
        return $this->_data;
    }


    ############################################################
    # 内部属性

    private $_name;
    private $_source;
    private $_data;
    private $_format;

}


