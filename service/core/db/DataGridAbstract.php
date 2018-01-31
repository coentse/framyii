<?php namespace service\core\db;


abstract class DataGridAbstract extends DatabaseAbstract
{

    protected $db_handle;           # 数据库句柄名称
    protected $table_alias;         # 数据表别名
    protected $table_title;         # 显示标题

    # 字段设置
    protected $field_pkey;          # 主键字段名
    protected $field_maps;          # 数据表字段-名称映射
    protected $field4list;          # 列表页显示字段列表
    protected $field4search;        # 可进行关键字查询字段列表

    # 排序相关参数
    protected $sort_field;          # string, 排序字段
    protected $sort_reverse;        # bool, 是否反向排序


    public function __construct($config_name)
    {
        $this->db = new DatabaseQuery($this->table_alias, $this->db_handle);
    }



}