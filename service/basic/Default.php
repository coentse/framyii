<?php namespace service\basic;

use service\db\DefaultSer as DBDefaultSer;

class DefaultSer extends ControllerAbstract
{
    private static $_instance;
    public static function getInstance()
    {
        if(is_null(self::$_instance)) self::$_instance = new self;
        return self::$_instance;
    }

    /*
     *  获取数据列表
     *
     *  array(
     *      'param1'     => int
     *      'param2'     => text
     *      'keyword'    => text
     *  )
     *  Return: array(
     *      'list'  => Array
     *  )
     */
    public function getDataList($p, $debug)
    {
        # 参数处理
        self::processParamList($p, [
			'param1'     => int,
			'param2'     => 'text',
			'keyword'    => 'text',
        ]);

        # 接收参数
        $page   = self::val($p, 'page',   1);
        $limit  = self::val($p, 'limit',  20);
        $fields = self::val($p, 'fields', '*');

        # 组合查询条件
        list($where, $param) = self::buildSearchCondition($p, 'param1, param2');
        if ($p['keyword']) {
            //todo...
        }

        # 排序
        $orderby  = self::val($p, 'orderby', 'id DESC');
        $orderby .= ($p['orderby'] && self::isOn($p['reverse']))? " DESC": '';

        # 查询数据库
        $DBDefault = $DBDefaultSer::getInstance();
        if ($limit < 0) {
            $dataList = $DBDefault->db->alias('defaultTable')
                ->field($fields)
                ->where($where)
                ->param($param)
                ->order($orderby)
                ->select($debug==1);
        }
        else {
            $dataList = $DBDefaultSer->db->alias('defaultTable')
                ->field($fields)
                ->where($where)
                ->param($param)
                ->order($orderby)
                ->pages($page, $limit, $debug==1);
        }

        return ['dataList' => $dataList];
    }

   

}

