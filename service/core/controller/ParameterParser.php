<?php namespace service\core\controller;

use service\core\GlobalObject;
use service\core\SystemVariable;

class ParameterParser
{

    ############################################################
    # 外部调用操作

    # 解析指定的参数组
    public static function parseParam($param_list)
    {
        $data = array();
        foreach($param_list as $entry) {
            if (!$entry instanceof Parameter) {
                continue;
            }

            # 取得参数属性
            $key    = $entry->getParamName();
            $source = $entry->getDataSource();
            $format = $entry->getDataFormat();

            # 生成数据
            $value = null;
            if     ($source == 'value' ) {
                $value = $entry->getData();
            }
            elseif ($source == 'method') {
                $value = self::_call_param_method($entry->getData());
            }
            elseif ($source == 'invoke') {
                $value = self::_parse_param_invoke($entry->getData());
            }

            # 数据格式处理
            if ($format) {
                try {
                    $data[$key] = ParameterFormat::processParam($value, $format);
                }
                catch(ParameterError $e) {
                    throw new ParameterError($key);
                }
                catch(ParameterFormatError $e) {
                    throw new ParameterFormatError($key);
                }
            }
            else {
                $data[$key] = $value;
            }
        }
        return $data;
    }


    ############################################################
    # 内部调用操作

    # 调用参数调用方法
    private static function _call_param_method($method_string)
    {
        # 拆分出类名称、方法名称
        list($class_name, $method_name) = explode('@', $method_string);

        # 判断类对象
        if (!class_exists($class_name)) {
            throw new Error("can't found class: {$class_name}");
        }

        # 创建类对象
        $object = new $class_name;

        # 判断类方法
        if (!method_exists($object, $method_name)) {
            throw new Error("can't found method: {$class_name}->{$method_name}");
        }

        # 调用类方法，并返回结果
        return call_user_func(array($object, $method_name));
    }

    # 解析引用参数的值
    private static function _parse_param_invoke($item)
    {
        # 直接指定值时的处理
        if (!is_array($item)) return $item;

        # 拆分设置项
        $type    = $item[0];
        $keyname = $item[1];
        $default = $item[2];

        # 根据不同的参数类型取得对应的值
        $value = null;
        if ($type == 'global') {
            $value = GlobalObject::get($keyname, $default);
        }
        if ($type == 'server') {
            $value = SystemVariable::server($keyname, $default);
        }
        if ($type == 'get') {
            $value = SystemVariable::httpGet($keyname, $default);
        }
        if ($type == 'post') {
            $value = SystemVariable::httpPost($keyname, $default);
        }
        if ($type == 'rawpost') {
            $value = SystemVariable::httpRawPost();
        }
        if ($type == 'file') {
            $value = SystemVariable::httpFile($keyname, $default);
        }
        if ($type == 'session') {
            $value = SystemVariable::getSession($keyname, $default);
        }
        if ($type == 'cookie') {
            $value = SystemVariable::getCookie($keyname, $default);
        }
        return $value;
    }

}


