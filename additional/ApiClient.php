<?php

class ApiClient
{

    # 构造函数
    public function __construct($server_address, $secret_key)
    {
        $this->_server_address  = $server_address;
        $this->_secret_key      = $secret_key;
    }

    # 取得分页数据
    public function getPageData()
    {
        return $this->_page_data;
    }


}


