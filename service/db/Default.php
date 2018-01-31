<?php namespace service\db;

use service\core\db\DatabaseQuery;
use service\core\db\DatabaseAbstract;

class Company extends DatabaseAbstract
{
    private static $_instance;
    public static function getInstance()
    {
        if(is_null(self::$_instance)) self::$_instance = new self;
        return self::$_instance;
    }

    public function __construct()
    {
        $this->db = new DatabaseQuery('defaultTable', 'defaultTable', 'app');
    }


}

