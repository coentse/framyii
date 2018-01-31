<?php namespace service\core\db;
/*
 *
 *  # 查询记录
 *  $data = $obj->table('table_name')
 *      ->field('field_1, field_2, field_3,...')
 *      ->where('field_x = :field_x')
 *      ->group('field_y')
 *      ->order('field_z')
 *      ->limit($start, $end)
 *      ->param(array(':field_x' => 'balabala...'))
 *      ->select();
 *
 *  # 分页查询
 *  $data = $obj->table('table_name_1 as a')
 *      ->field('a.field_1, a.field_2, b.field_3, b.field_4')
 *      ->join('LEFT JOIN table_name_2 as b ON a.field_x = b.field_x')
 *      ->where('a.field_y = :field_y')
 *      ->order('a.field_z')
 *      ->param(array(':field_y' => 'balabala...'))
 *      ->pages($current_page, $page_limit);
 *
 *  # 添加记录
 *  $result = $obj->table('table_name')
 *      ->data(array(
 *          'field_1' => ':field_1',
 *          'field_2' => ':field_2',
 *          ...
 *      ))
 *      ->param(array(
 *          ':field_1' => 'value_1',
 *          ':field_2' => 'value_2',
 *          ...
 *      ))
 *      ->add();
 *
 *  # 更新记录
 *  $result = $obj->table('table_name')
 *      ->data(array(
 *          'field_1' => ':field_1',
 *          'field_2' => ':field_2',
 *          ...
 *      ))
 *      ->where('field_3 = :field_3')
 *      ->param(array(
 *          ':field_1' => 'value_1',
 *          ':field_2' => 'value_2',
 *          ':field_3' => 'value_3',
 *          ...
 *      ))
 *      ->save();
 *
 *  # 删除记录
 *  $result = $obj->table('table_name')
 *      ->where('field_1 = :field_1')
 *      ->order('field_2')
 *      ->limit($start, $end)
 *      ->param(array(':field_1' => 'value_1'))
 *      ->delete();
 *
 */

use yii;
use yii\db\Connection;

class DatabaseQuery
{

    ############################################################
    # 外部调用操作（静态方法）

    # 设置数据库别名映射数据（全局）
    public static function set_global_alias_map($data, $handle_key='default')
    {
        self::$_global_alias_map[$handle_key] = $data;
    }
    public static function get_global_alias_map($handle_key='default')
    {
        return self::$_global_alias_map[$handle_key];
    }


    ############################################################
    # 内部属性

    private static $_global_alias_map = [];
    private $_module_alias_map = [];

    private $_handle_key;
    private $_module;
    private $_table_alias;
    private $_do_dict = [
        'field'  => '__field',
        'table'  => '__table_name',
        'where'  => '__condition',
        'limit'  => '__limit',
        'order'  => '__order',
        'data'   => '__data',
        'param'  => '__param',
        'group'  => '__group',
        'having' => '__having',
        'join'   => '__join',
    ];

    private $_cluster = [];
    private $_protected = [];


    ############################################################
    # 内部调用操作

    public function __construct($tb_alias, $module, $handle_key='default')
    {
        $this->_table_alias   = $tb_alias;
        $this->_module     = $module;
        $this->_handle_key = $handle_key;
        $this->_reset();
    }

    # 参数重置
    private function _reset()
    {
        $this->_cluster   = array();
        $this->_protected = array(
            "__field" => '*',
        );
    }

    # 添加 SQL 参数
    private function _do($name, $value)
    {
        $value = is_string($value)? trim($value): $value;
        $this->_protected[$this->_do_dict[$name]] = $value;
        $this->_tracker($name);
    }

    # 添加参数跟踪
    private function _tracker($name)
    {
        if (!in_array($name, $this->_cluster)) {
            array_push($this->_cluster, $name);
        }
    }

    # 生成 SQL 语句
    private function _build_sql($sql='', $queue=array())
    {
        if (!$this->_check('table')) {
            throw new DatabaseError("table name is not specified");
        }
        foreach($queue as $statement) {
            if ($this->_check('join')   && $statement == 'join') {
                $sql .= ' '. $this->_protected['__join'];
            }
            if ($this->_check('where')  && $statement == 'where') {
                $sql .= ' WHERE '. $this->_protected['__condition'];
            }
            if ($this->_check('order')  && $statement == 'order') {
                $sql .= ' ORDER BY '. $this->_protected['__order'];
            }
            if ($this->_check('limit')  && $statement == 'limit') {
                $sql .= ' LIMIT '. $this->_protected['__limit'];
            }
            if ($this->_check('group')  && $statement == 'group') {
                $sql .= ' GROUP BY '. $this->_protected['__group'];
            }
            if ($this->_check('having') && $statement == 'having') {
                $sql .= ' HAVING '. $this->_protected['__having'];
            }
            if ($this->_check('data')   && $statement == 'data:save') {
                $sets = '';
                foreach($this->_protected['__data'] as $key => $val) {
                    if (substr($val, 0, 1) == ':') {
                        $sets .= '`'. $key ."` = ". $val .", ";
                    }
                    else {
                        $sets .= '`'. $key ."` = '". $val ."', ";
                    }
                }
                $sets = rtrim(trim($sets), ',');

                $sql .= ' SET '. $sets;
            }
            if ($this->_check('data')   && $statement == 'data:add') {
                $sets   = '';
                $values = '';
                foreach($this->_protected['__data'] as $key => $val) {
                    $sets .= '`'. $key .'`, ';
                    if (substr($val, 0, 1) == ':') {
                        $values .= $val .", ";
                    }
                    else {
                        $values .= "'". $val ."', ";
                    }
                }
                $sets   = rtrim(trim($sets),   ',');
                $values = rtrim(trim($values), ',');

                $sql .= ' ('. $sets .')';
                $sql .= ' VALUES ('. $values .')';
            }
        }
        return $sql;
    }

    # 检测是否设置了指定参数
    private function _check($name)
    {
        return in_array($name, $this->_cluster);
    }

    # 获取、设置当前执行参数
    private function _get_current_param()
    {
        return array($this->_cluster, $this->_protected);
    }
    private function _set_current_param($data)
    {
        $this->_cluster   = $data[0];
        $this->_protected = $data[1];
    }


    ############################################################
    # 外部调用操作（实例方法）

    # 取得数据库句柄键名
    public function get_handle_key()
    {
        return $this->_handle_key;
    }

    # 取得当前的模块名称
    public function get_current_module()
    {
        return $this->_module;
    }

    # 取得当前的数据表别名
    public function get_current_alias()
    {
        return $this->_table_alias;
    }

    #
    # 据库别名映射数据操作
    #

    # 设置数据库别名映射数据（模块）
    public function set_module_alias_map($data)
    {
        $this->_module_alias_map = $data;
    }

    # 根据指定的数据表别名取得真实表名称
    public function get_table_name_by_alias($alias, $module=null)
    {
        # 检查模块设置
        $module = $module? $module: $this->_module;
        if (!$module) {
            throw new DatabaseError("not set table map module");
        }

        # 在“模块映射表”中查找别名对应的数据表名称
        if ($this->_module_alias_map && array_key_exists($alias, $this->_module_alias_map)) {
            return $this->_module_alias_map[$alias];
        }

        # 在“全局映射表”中查找别名对应的数据表名称
        $maps = self::$_global_alias_map[$this->_handle_key];

        if (!is_array($maps)) {
            throw new DatabaseError("cat't found database alias map data");
        }
        if (!array_key_exists($module, $maps)) {
            throw new DatabaseError("cat't found module \"{$module}\" in database alias map data");
        }
        if (!array_key_exists($alias, $maps[$module])) {
            throw new DatabaseError("cat't found alias \"{$module}/{$alias}\" in database alias map data");
        }
        return $maps[$module][$alias];
    }

    # 取得当前的数据表真实名称
    public function get_current_table_name()
    {
        $alias = $this->get_current_alias();
        return $this->get_table_name_by_alias($alias);
    }


    #
    # SQL 语句参数设置操作
    #

    public function table($table_name = null)
    {
        if (!$table_name) return $this;
        $this->_do("table", $table_name);
        return $this;
    }

    # 根据别名设置数据表名称
    public function alias($table_alias = null)
    {
        if (!$table_alias) return $this;
        $table_name = $this->get_table_name_by_alias($table_alias);
        $this->_do("table", $table_name);
        return $this;
    }

    public function field($field = null)
    {
        if (!$field) return $this;
        $this->_do("field", $field);
        return $this;
    }

    public function where($condition = null)
    {
        if (!$condition) return $this;
        $this->_do("where", $condition);
        return $this;
    }

    public function order($order_type = null)
    {
        if (!$order_type) return $this;
        $this->_do("order", $order_type);
        return $this;
    }

    public function limit($start = null, $end = null)
    {
        if ($end) {
            if (is_null($start)) return $this;
            $limit = $start .', '. $end;
        } else {
            if (!$start) return $this;
            $limit = $start;
        }
        $this->_do("limit", $limit);
        return $this;
    }

    public function param($param = null)
    {
        if (!$param) return $this;
        $this->_do("param", $param);
        return $this;
    }

    public function data($data = null)
    {
        if (!$data) return $this;
        $this->_do("data", $data);
        return $this;
    }

    public function group($group_type = null)
    {
        if (!$group_type) return $this;
        $this->_do("group", $group_type);
        return $this;
    }

    public function having($condition = null)
    {
        if (!$condition) return $this;
        $this->_do("having", $condition);
        return $this;
    }

    public function join($condition = null)
    {
        if (!$condition) return $this;
        $this->_do("join", $condition);
        return $this;
    }

    # 追加 SQL 片断（“where”关键字）
    public function prepend($name, $value)
    {
        $this->_protected[$this->_do_dict[$name]] = $value .' AND '. $this->_protected[$this->_do_dict[$name]];
        return $this;
    }


    #
    # SQL 语句执行操作
    #

    ##### 查询记录

    # 基本查询操作
    public function query($sql)
    {
        /* @var $connection Connection */
        $connection = Yii::$app->get('db_' . $this->_handle_key);

        # 执行SQL语句
        if ($this->_check('param')) {
            $command = $connection->createCommand($sql, $this->_protected['__param']);
        }
        else {
            $command = $connection->createCommand($sql);
        }

        # 重置查询器参数
        $this->_reset();

        # 返回所有查询结果
        return $command->queryAll();
    }

    # 查询，并返回多条记录
    public function select($cheat=false)
    {
        # 设置默认操作数据表
        if (!$this->_check('table')) $this->alias($this->_table_alias);

        # 组合SQL语句
        $sql = 'SELECT '. $this->_protected['__field'] .' FROM '. $this->_protected['__table_name'];
        $sql = $this->_build_sql($sql, array("join", "where", "group", "having", "order", "limit"));
        if ($cheat) dump_sql($sql, $this->_protected['__param']);
        return $this->query($sql);
    }

    # 查询，并返回第一条记录
    public function find($cheat=false)
    {
        $array = $this->limit(1)->select($cheat);
        return $array[0];
    }

    # 惰性查询，返回一个数据读取对象（yii\db\DataReader）
    public function lazy_query($cheat=false)
    {
        # 设置默认操作数据表
        if (!$this->_check('table')) $this->alias($this->_table_alias);

        # 组合SQL语句
        $sql = 'SELECT '. $this->_protected['__field'] .' FROM '. $this->_protected['__table_name'];
        $sql = $this->_build_sql($sql, array("join", "where", "group", "having", "order", "limit"));
        if ($cheat) dump_sql($sql, $this->_protected['__param']);

        # 取得数据库连接
        /* @var $connection Connection */
        $connection = Yii::$app->get('db_' . $this->_handle_key);

        # 执行SQL语句
        if ($this->_check('param')) {
            $command = $connection->createCommand($sql, $this->_protected['__param']);
        }
        else {
            $command = $connection->createCommand($sql);
        }

        # 重置查询器参数
        $this->_reset();

        # 返回数据读取对象
        return $command->query();
    }

    # 分页查询记录
    public function pages($current_page=1, $list_rows=20, $cheat=false)
    {
        $_p = $this->_get_current_param();

        # 记录总数
        $count = $this->count();

        # 总页数
        $pages = ceil($count / $list_rows);
        if ($pages == 0) $pages = 1;

        # 当前页码
        if ($current_page < 1) $current_page = 1;

        # 当前页显示记录的开始、结束偏移量
        $start = ($current_page - 1) * $list_rows;
        $end = $list_rows;

        # 上一页、下一页页码
        $previous_page = ($current_page > 1)? $current_page - 1: 1;
        $next_page = ($current_page < $pages)? $current_page + 1: $pages;

        # 返回结果
        $this->_set_current_param($_p);
        $result = array(
            'list' => $this->limit($start, $end)->select($cheat),
            'page' => array(
                'prev'    => $previous_page,
                'next'    => $next_page,
                'current' => $current_page,
                'pages'   => $pages,
                'total'   => $count,
            ),
        );
        return $result;
    }

    # 统计记录数量
    public function count($cheat=false)
    {
        # 设置默认操作数据表
        if (!$this->_check('table')) $this->alias($this->_table_alias);

        # 组合SQL
        $sql = "SELECT COUNT(*) " . "FROM _TABLE_NAME_";
        $sql = str_replace('_TABLE_NAME_', $this->_protected['__table_name'], $sql);
        $sql = $this->_build_sql($sql, array("join", "where", "group", "having"));
        if ($cheat) dump_sql($sql, $this->_protected['__param']);

        # 统计数量
        if (preg_match('/(GROUP|HAVING)/i', $sql)) {
            return count($this->query($sql));
        }
        else {
            $array = $this->query($sql);
            return $array[0]['COUNT(*)'];
        }
    }

    # 计算指定字段的总和
    public function sum($field, $cheat=false)
    {
        # 设置默认操作数据表
        if (!$this->_check('table')) $this->alias($this->_table_alias);

        # 组合SQL语句
        $sql = "SELECT SUM(". $field .") FROM ". $this->_protected['__table_name'];
        $sql = $this->_build_sql($sql, array('where'));
        if ($cheat) dump_sql($sql, $this->_protected['__param']);
        $array = $this->query($sql);
        return $array[0]['SUM('. $field .')'];
    }


    ##### 基本执行操作

    public function execute($sql)
    {
        /* @var $connection Connection */
        $connection = Yii::$app->get('db_' . $this->_handle_key);

        # 创建命令对象
        if ($this->_check('param')) {
            $command = $connection->createCommand($sql, $this->_protected['__param']);
        }
        else {
            $command = $connection->createCommand($sql);
        }

        # 重置查询器参数
        $this->_reset();

        # 返回执行结果
        try {
            $command->execute();
        }
        catch(\Exception $e) {
            return false;
        }
        return true;
    }


    ##### 添加记录

    public function add($cheat=false, $getid=false)
    {
        # 设置默认操作数据表
        if (!$this->_check('table')) $this->alias($this->_table_alias);

        # 组合SQL并执行
        $sql = 'INSERT ' . ' INTO '. $this->_protected['__table_name'];
        $sql = $this->_build_sql($sql, array('data:add'));
        if ($cheat)  dump_sql($sql, $this->_protected['__param']);
        if (!$this->execute($sql)) return false;
        if (!$getid) return true;       # 不需要获取自增 ID 时直接返回 true

        # 取得记录ID
        /* @var $connection Connection */
        $connection = Yii::$app->get('db_' . $this->_handle_key);
        return $connection->getLastInsertID();
    }


    ##### 修改记录

    public function save($cheat=false)
    {
        # 设置默认操作数据表
        if (!$this->_check('table')) $this->alias($this->_table_alias);

        # 组合SQL语句
        $sql = 'UPDATE '. $this->_protected['__table_name'];
        $sql = $this->_build_sql($sql, array('data:save', 'where'));
        if ($cheat) dump_sql($sql, $this->_protected['__param']);
        return $this->execute($sql);
    }


    ##### 删除记录

    public function delete($cheat=false)
    {
        # 设置默认操作数据表
        if (!$this->_check('table')) $this->alias($this->_table_alias);

        # 组合SQL语句
        $sql = 'DELETE FROM _TABLE_NAME_';
        $sql = str_replace('_TABLE_NAME_', $this->_protected['__table_name'], $sql);
        $sql = $this->_build_sql($sql, array('where', 'order', 'limit'));
        if ($cheat) dump_sql($sql, $this->_protected['__param']);
        return $this->execute($sql);
    }

}


