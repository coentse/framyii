<?php namespace service\application\api;

use service\core\controller\ControllerAbstract;
use service\db\person\Person as DBPerson;
use service\db\company\Company as DBCompany;

class AbstractAction extends ControllerAbstract
{

    # 默认func
    protected function test($name, $debug=false)
    {
        //...
    }


}

