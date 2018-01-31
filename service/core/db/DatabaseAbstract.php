<?php namespace service\core\db;

use service\core\controller\ParameterFormat;

/**
 *
 * @property DatabaseQuery|\service\core\db\DatabaseQuery $db 数据库查询器对象实例
 *
 */
abstract class DatabaseAbstract
{
    /** @var DatabaseQuery $db */
    public $db;

    ############################################################
    # 查询操作

    # 取得指定条件的单条数据
    public function getDataByWhere($where, $param, $fields='*', $debug=false)
    {
        return $this->db->field($fields)->where($where)->param($param)->find($debug);
    }

    # 取得指定 ID 的单个数据
    public function getDataByKey($key_name, $key_value, $fields='*', $debug=false)
    {
        $where = "{$key_name} = :_find_id";
        $param[':_find_id'] = $key_value;
        return $this->getDataByWhere($where, $param, $fields, $debug);
    }

    # 取得指定 ID 的单个数据指定字段的值
    public function getValueByKey($key_name, $key_value, $return_field, $debug=false)
    {
        $array = $this->getDataByKey($key_name, $key_value, $return_field, $debug);
        return $array[$return_field];
    }

    # 检测指定 ID 的数据是否存在
    public function checkDataExistByKey($key_name, $key_value, $debug=false)
    {
        $where = "{$key_name} = :_find_id";
        $param[':_find_id'] = $key_value;
        return $this->checkDataExistByWhere($key_name, $where, $param, $debug);
    }

    # 检测指定条件的数据是否存在
    public function checkDataExistByWhere($primary_key, $where, $param, $debug=false)
    {
        $data = $this->getDataByWhere($where, $param, $primary_key, $debug);
        return $data? $data[$primary_key]: 0;
    }

    # 检测指定条件的数据是否重复
    public function checkDataDuplicate($primary_key, $where, Array $param, $exclude_id=null, $debug=false)
    {
        # 组合查询条件
        if ($exclude_id) {
            $where .= " AND ". $primary_key ." <> :exclude_id";
            $param[':exclude_id'] = $exclude_id;
        }

        # 取得数据
        $data = $this->getDataByWhere($where, $param, $primary_key, $debug);
        return $data? $data[$primary_key]: false;
    }

    # 取得指定条件的多条数据
    public function getDataListByWhere($where, $param, $fields='*', $debug=false)
    {
        return $this->db->field($fields)->where($where)->param($param)->select($debug);
    }

    # 取得指定 Key 的多条数据
    public function getDataListByKey($key_name, $key_value, $fields='*', $debug=false)
    {
        $where = $key_name ." = :_key_val";
        $param[':_key_val'] = $key_value;
        return $this->getDataListByWhere($where, $param, $fields, $debug);
    }

    # 取得指定 Key 列表的多条数据
    public function getDataListByKeyList($key_name, $value_list, $fields='*', $debug=false)
    {
        list($where, $param) = $this->_build_key_list_condition($key_name, $value_list);
        if (!$where) return array();
        return $this->getDataListByWhere($where, $param, $fields, $debug);
    }

    /*
     * 根据指定的数组和数组 Key 获得一个 Key 列表，根据 Key 列表取得其对应的多条数据
     *
     *  (数据表别名, 数组对象, 数组键名&字段名, 返回数据字段列表)
     *  (数据表别名, 数组对象, array(数组键名, 字段名), 返回数据字段列表)
     */
    public function getDataListByArrayKey($array, $keys, $fields='*', $debug=false)
    {
        # 取得字段名
        if (is_array($keys)) {
            list($array_key, $key_name) = $keys;
        }
        else {
            $array_key = $key_name = $keys;
        }

        # 从数组中取得指定 Key 对应的值列表
        $value_list = get_array_unique_value($array, $array_key);

        # 根据值列表取得其对应的多条数据
        return $this->getDataListByKeyList($key_name, $value_list, $fields, $debug);
    }

    # 根据指定的条件生成一个映射数据
    public function createDataMapByWhere($where, $param, $map_key, $map_val, $debug=false)
    {
        $fields = $map_key .','. $map_val;
        $array  = $this->getDataListByWhere($where, $param, $fields, $debug);
        return array_column($array, $map_val, $map_key);
    }

    # 根据指定 Key 生成一个映射数据
    public function createDataMapByKey($key_name, $key_value, $map_key, $map_val, $debug=false)
    {
        $fields = $map_key .','. $map_val;
        $array  = $this->getDataListByKey($key_name, $key_value, $fields, $debug);
        return array_column($array, $map_val, $map_key);
    }

    # 根据指定 Key 的值列表生成一个映射数据
    public function createDataMapByKeyList($key_name, $value_list, $map_key, $map_val, $debug=false)
    {
        $fields = $map_key .','. $map_val;
        $array  = $this->getDataListByKeyList($key_name, $value_list, $fields, $debug);
        return array_column($array, $map_val, $map_key);
    }

    /*
     * 根据指定的数组和数组 Key 获得一个 Key 列表，根据 Key 列表生成一个映射数据
     *
     *  (数据表别名, 数组对象, 数组键名&字段名, 映射键名Key, 映射键值Key)
     *  (数据表别名, 数组对象, array(数组键名, 字段名), 映射键名Key, 映射键值Key)
     */
    public function createDataMapByArrayKey($array, $keys, $map_key, $map_val, $debug=false)
    {
        # 取得字段名
        if (is_array($keys)) {
            list($array_key, $key_name) = $keys;
        }
        else {
            $array_key = $key_name = $keys;
        }

        # 从数组中取得指定 Key 对应的值列表
        $value_list = get_array_unique_value($array, $array_key);

        # 根据值列表生成一个映射数据
        return $this->createDataMapByKeyList($key_name, $value_list, $map_key, $map_val, $debug);
    }


    ############################################################
    # 添加操作

    # 添加数据到指定的表
    public function addData($data, $debug=false)
    {
        list($add_data, $add_param) = $this->_build_sql_param($data);
        unset($data);
        return $this->db->data($add_data)->param($add_param)->add($debug, false);
    }

    # 添加数据到指定的表并获得自增ID
    public function addDataGetId($data, $debug=false)
    {
        list($add_data, $add_param) = $this->_build_sql_param($data);
        unset($data);
        return $this->db->data($add_data)->param($add_param)->add($debug, true);
    }


    ############################################################
    # 更新操作

    # 根据指定的 Key 更新数据
    public function updateDataByKey($key_name, $key_value, $data, $debug=false)
    {
        $where  = "{$key_name} = :_update_id";
        $param  = array(':_update_id' => $key_value);
        return $this->updateDataByWhere($where, $param, $data, $debug);
    }

    # 根据指定的 Key 列表更新数据
    public function updateDataByKeyList($key_name, $value_list, $data, $debug=false)
    {
        list($where, $param) = $this->_build_key_list_condition($key_name, $value_list);
        if (!$where) return array();
        return $this->updateDataByWhere($where, $param, $data, $debug);
    }

    # 根据指定的条件更新表数据
    public function updateDataByWhere($where, $param, $data, $debug=false)
    {
        # 处理更新数据
        list($update_data, $update_param) = $this->_build_sql_param($data);
        unset($data);

        # 处理条件参数
        if ($param) {
            foreach($param as $key => $val) {
                $update_param[$key] = $val;
            }
            unset($param);
        }

        # 更新数据
        return $this->db->where($where)->data($update_data)->param($update_param)->save($debug);
    }


    ############################################################
    # 删除操作

    # 删除指定 Key 对应的数据
    public function deleteDataByKey($key_name, $key_value, $debug=false)
    {
        $where  = "{$key_name} = :_update_id";
        $param  = array(':_update_id' => $key_value);
        return $this->deleteDataByWhere($where, $param, $debug);
    }

    # 删除指定 Key 列表对应的数据
    public function deleteDataByKeyList($key_name, $value_list, $debug=false)
    {
        list($where, $param) = $this->_build_key_list_condition($key_name, $value_list);
        if (!$where) return array();
        return $this->deleteDataByWhere($where, $param, $debug);
    }

    # 删除指定条件对应的数据
    public function deleteDataByWhere($where, $param, $debug=false)
    {
        return $this->db->where($where)->param($param)->delete($debug);
    }


    ############################################################
    # 内部调用操作

    # 根据 Key 列表生成查询条件、参数
    private function _build_key_list_condition($key_name, $value_list)
    {
        # id 处理
        $value_list = ParameterFormat::filter_id_list($value_list);
        if (!$value_list) return array();

        # 设置条件
        if (strpos($value_list, ',')) {
            $where = $key_name ." IN (". $value_list .")";
            $param = null;
        }
        else {
            $where = "{$key_name} = :_id_val";
            $param = array(':_id_val' => $value_list);
        }

        return array($where, $param);
    }

    # 生成 SQL 语句变量绑定参数
    private function _build_sql_param($original)
    {
        $maps = $values = array();
        foreach($original as $key => $val) {
            $maps[$key] = ':'. $key;
            $values[':'. $key] = $val;
        }
        return array($maps, $values);
    }

}

