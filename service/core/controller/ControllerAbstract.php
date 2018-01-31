<?php namespace service\core\controller;


abstract class ControllerAbstract
{

    ############################################################
    # 静态方法

    # 如果指定的值存在则返回该值，否则返回默认值
    static public function val($param_data, $key_name, $default=null)
    {
        return $param_data[$key_name]? $param_data[$key_name]: $default;
    }

    # 检测开关项的值
    static public function isOn($value)  {return $value == 1;}
    static public function isOff($value) {return $value == -1;}

    # 创建操作结果对象
    static public function actionResult($result, $code=null, $message=null)
    {
        if ($result instanceof ActionResult) {
            $result->append($code, $message);
            return $result;
        }
        else {
            return new ActionResult($result, $code, $message);
        }
    }

    # 对给定的多个参数数据进行检测或转换
    static public function processParamList(&$param_data, $setting)
    {
        ParameterFormat::processParamList($param_data, $setting);
    }

    # 参数过滤器（注：参数不能以数字做为键名）
    static public function filterParamByKey($param_data, $key_map, $filter_null=true)
    {
        # 键名为字符串时的处理
        if (!is_array($key_map)) {
            $key_map = explode(',', $key_map);
        }

        # 参数过滤
        $param_keys  = array_keys($param_data);
        $return_data = array();
        foreach($key_map as $old_key => $new_key)
        {
            # 新旧键名相同时的处理
            if (is_int($old_key)) {
                $old_key = $new_key;
            }

            # 当指定的键名在参数数据中不存在时，忽略此数据项
            if (!in_array($old_key, $param_keys)) {
                continue;
            }

            # 如果指定了“$filter_null”，并且指定的数据值为“NULL”，则忽略此数据项
            if ($filter_null && is_null($param_data[$old_key])) {
                continue;
            }

            # 将此数据项添加至返回数据
            $return_data[$new_key] = $param_data[$old_key];
        }

        return $return_data;
    }

    /*
     * 生成数据库基础查询条件
     *
     * @param   array   $param_data
     * @param   array   $field_list
     *
     *  $field_list = array(
     *      'key_name',                     # 直接使用键名做为字段名
     *      'key_name' => 'field_name',     # 为键名指定相应的字段名
     *      'key_name' => array(            # 为键名指定相应的字段名和匹配模式
     *          'field_name',
     *          'pattern',                  # 匹配模式；“=”为相等（默认），“!=”或“<>”为不等，“<”为小于，“>”为大于，...
     *      ),
     *  )
     */
    static public function buildSearchCondition($param_data, $field_list)
    {
        # 处理字段设置
        if (!is_array($field_list)) {
            $field_list = explode(',', $field_list);
        }

        # 生成查询条件
        $where = "";
        $param = array();
        foreach($field_list as $key => $config)
        {
            # 解析字段设置
            if (is_array($config)) {
                $field   = $config[0];
                $pattern = $config[1];
            }
            else {
                $field   = $config;
                $pattern = '=';
            }
            if (is_int($key)) $key = $field;

            # 如果参数数据中该字段对应的值为空，则忽略
            if (is_null($param_data[$key]) || $param_data[$key] === '') {
                continue;
            }

            # 组合查询条件、查询参数
            $join   = $where? ' AND ': '';
            $where .= $join .'`'. $field .'` '. $pattern . ' :'. $field;
            $param[':'. $field] = $param_data[$key];
        }
        return array($where, $param);
    }

    /*
     *  连接数据库查询条件
     */
    static public function concatSearchCondition($old_condition, $new_condition, $relation='AND')
    {
        $join_symbol = $old_condition? " {$relation} ": '';
        return $old_condition . $join_symbol . $new_condition;
    }


}

