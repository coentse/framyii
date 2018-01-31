<?php namespace service\core\controller;


class ParameterFormat
{
    static private $_check_param_list = array(
        'alphabet', 'number', 'base', 'on-off', 'date', 'time',
        'datetime', 'ip', 'domain', 'email', 'array'
    );

    /*
     *  对给定的多个参数数据进行检测或转换
     *
     *  @param  array   $param_data     参数数据
     *  @param  array   $setting        参数格式设置
     *
     *  $param_data = array(
     *      参数名称 => 参数值,
     *      参数名称 => 参数值,
     *      参数名称 => 参数值,
     *      ...
     *  );
     *  $setting = array(
     *      ParamName => 'DataFormat',      # 一般数据格式处理、检测
     *      ParamName => array('DataFormat', 是否为必要参数),
     *                                      # 必要数据格式处理、检测
     *      ParamName => array(             # 判断指定的值是否在允许值之中
     *          'choose'
     *          array(允许值1, 允许值2, 允许值3, ...),
     *          是否为必要参数
     *      ),
     *      ...
     *  );
     *
     *  “必要参数”可选项：“true|1”不能为NULL或空字符串；“2”可以为NULL，但不能为空字符串
     *  可以选择的“数据格式”：
     *      对数据保持原样: raw
     *      对数据进行格式转换: rawstr, int, bool, float, idlist
     *      对数据进行格式检测: alphabet, number, base, on-off, array, choose
     *                        date, time, datetime, ip, domain, email
     *  默认情况下参数检查器允许数据为空（即数据为 NULL 或空字符串时不进行检测）
     */
    static public function processParamList(&$param_data, $setting)
    {
        $key_list = array_keys($setting);
        foreach($key_list as $key) {
            if (!$setting[$key]) continue;

            # 解析参数格式设置项
            list($need_level, $value_format, $choose_list) = self::_parse_setting_entry($setting[$key]);

            # 必要参数检测
            if (is_null($param_data[$key]) || $param_data[$key] === '') {
                if (($need_level === true || $need_level == 1) ||
                    ($need_level == 2 && $param_data[$key] === '')) {
                    throw new ParameterError($key);
                }
                continue;
            }

            # 剔除参数值前后的空格
            if (is_string($param_data[$key]) && !in_array($value_format, array('raw', 'rawstr', 'array'))) {
                $param_data[$key] = trim($param_data[$key]);
            }

            # 检测类型为“choose”（指定允许值）时的处理
            if ($value_format == 'choose' && !in_array($param_data[$key], $choose_list)) {
                throw new ParameterFormatError($key);
            }
            # 检测参数格式
            elseif (in_array($value_format, self::$_check_param_list)) {
                if (!self::checkValueFormat($param_data[$key], $value_format)) {
                    throw new ParameterFormatError($key);
                }
            }
            # 参数值过滤、转换
            else {
                $param_data[$key] = self::convertValueFormat($param_data[$key], $value_format);
            }
        }
    }

    /*
     *  对给定的单个参数数据进行检测或转换
     *
     *   可以选择的“数据格式”：
     *      对数据保持原样: raw
     *      对数据进行格式转换: rawstr, int, bool, float, idlist
     *      对数据进行格式检测: alphabet, number, base, on-off, array
     *                        date, time, datetime, ip, domain, email
     *
     *   默认情况下参数检查器允许数据为空（即数据为 NULL 或空字符串时不进行检测），如果需要强制数据不能为空，
     *   则需要在数据格式前加上一个叹号“!”，例如“!int”、“!base”
     */
    static public function processParam($value, $format)
    {
        # 必要参数检测
        if (substr($format, 0, 1) == '!') {
            if (is_null($value) || $value === '') throw new ParameterError();
            $value_format = strtolower(substr($format, 1));
        }
        # 空参数直接返回
        elseif (is_null($value) || $value === '') {
            return $value;
        }
        else {
            $value_format = strtolower($format);
        }

        # 剔除参数值前后的空格
        if (!in_array($value_format, array('raw', 'rawstr', 'array'))) {
            $value = trim($value);
        }

        # 检测参数格式
        if (in_array($value_format, self::$_check_param_list)) {
            if (!self::checkValueFormat($value, $value_format)) {
                throw new ParameterFormatError();
            }
        }
        # 参数值过滤、转换
        else {
            $value = self::convertValueFormat($value, $value_format);
        }

        return $value;
    }

    /*
     * 检测所给定的值的类型是否为指定的格式
     *
     * @param   string  $value      需要检测的值
     * @param   string  $format     值的类型
     * @return  bool
     */
    static public function checkValueFormat($value, $format)
    {
        switch($format)
        {
            case 'alphabet':
                return self::check_alphabet_format($value);
            case 'integer':
                return self::check_integer_format($value);
            case 'number':
                return self::check_number_format($value);
            case 'base':
                return self::check_base_format($value);
            case 'on-off':
                return self::check_on_off_format($value);
            case 'date':
                return self::check_date_format($value);
            case 'time':
                return self::check_time_format($value);
            case 'datetime':
                return self::check_datetime_format($value);
            case 'ip':
                return self::check_ip_format($value);
            case 'domain':
                return self::check_domain_format($value);
            case 'email':
                return self::check_email_format($value);
            case 'array':
                return is_array($value);
            default :
                return true;
        }
    }

    /*
     * 将所给定值转换为指定的格式
     * 如果没有匹配的格式，则将值转换为 HTML 安全字符串
     *
     * @param   string  $value      需要转换的值
     * @param   string  $format     值的类型
     * @return  bool
     */
    static public function convertValueFormat($value, $format)
    {
        switch($format) {
            case 'rawstr' : return (string )$value;
            case 'int'    : return (integer)$value;
            case 'bool'   : return (boolean)$value;
            case 'float'  : return (float  )$value;
            case 'idlist' : return self::filter_id_list($value);
        }
        return htmlspecialchars(strval($value));
    }


    ############################################################
    # 数据格式检测操作

    # 字母格式检测
    static public function check_alphabet_format($value)
    {
        return preg_match('/^[a-zA-Z]+$/', $value);
    }

    # 整型数字检测（123、-123）
    static public function check_integer_format($value)
    {
        return preg_match('/^(-*)(\d+)$/', $value);
    }

    # 数字格式检测（123、-123、123.234、+123、-123.234）
    static public function check_number_format($value)
    {
        return preg_match('/^[+-]?[0-9]+(\.[0-9]+)*$/', $value);
    }

    # 基本字符串检测
    static public function check_base_format($value)
    {
        return preg_match('/^[a-zA-Z0-9_\-.*@]+$/', $value);
    }

    # 开/关值检测（1、-1）
    static public function check_on_off_format($value)
    {
        return in_array($value, array(1, -1));
    }

    # 日期格式检测
    static public function check_date_format($value)
    {
        return preg_match("/^\d{4}-\d{1,2}-\d{1,2}$/", $value);
    }

    # 时间格式检测
    static public function check_time_format($value)
    {
        if (count(explode(':', $value)) == 3) {
            $result = preg_match("/^\d{1,2}:\d{1,2}\:\d{1,2}$/", $value);
        }
        else {
            $result = preg_match("/^\d{1,2}:\d{1,2}$/", $value);
        }
        return $result;
    }

    # 日期时间格式检测
    static public function check_datetime_format($value)
    {
        list($_date, $_time) = explode(' ', $value);
        if (!self::check_date_format($_date)) return 0;
        if (!self::check_time_format($_time)) return 0;
        return 1;
    }

    # IP格式检测
    static public function check_ip_format($value)
    {
        return preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $value);
    }

    # 域名格式检测
    static public function check_domain_format($value)
    {
        return preg_match("/^[a-z0-9]+([-.][a-z0-9]+)*\.[a-z0-9]+$/i", $value, $match);
    }

    # 邮件地址格式检测
    static public function check_email_format($value)
    {
        return preg_match("/^[a-z0-9]+([-_+.][a-z0-9]+)*@[a-z0-9]+([-.][a-z0-9]+)*\.[a-z0-9]+$/i", $value, $match);
    }

    # 手机号码格式检测
    static public function check_mobile_format($value)
    {
        return preg_match("/^1\d{10}$/", $value, $match);
    }

    # 身份证号码格式检测
    static public function check_idnumber_format($value)
    {
        return preg_match("/^[1-9]\d{16}[1-9x]$/", $value, $match);
    }


    ############################################################
    # 数据格式过滤操作

    # ID列表过滤器
    static public function filter_id_list($raw_list, $return='string')
    {
        # 来源数据处理
        if (is_array($raw_list)) {
            $check_list = $raw_list;
        } else {
            $check_list = explode(',', $raw_list);
        }

        # 数据过滤筛选
        $id_list = array();
        foreach($check_list as $item) {
            if (!self::check_integer_format($item)) continue;
            array_push($id_list, intval($item));
        }
        if ($return == 'array') return $id_list;
        return implode(',', $id_list);
    }


    ############################################################
    # 内部调用操作

    # 解析参数格式设置项
    static private function _parse_setting_entry($set_entry)
    {
        if (!is_array($set_entry)) {
            return array(0, strtolower($set_entry), NULL);
        }
        if ($set_entry[0] == 'choose') {
            return array($set_entry[2], 'choose', $set_entry[1]);
        }
        return array($set_entry[1], strtolower($set_entry[0]), NULL);
    }

}


