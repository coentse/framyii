<?php namespace service\core\controller;


class ActionResult
{
    protected $result  = null;
    protected $codemsg = array();

    public function __construct($result, $ret_code=null, $ret_msg=null)
    {
        $this->result = $result;
        $this->append($ret_code, $ret_msg);
    }

    public function __toString()
    {
        return $this->getMessage();
    }

    # 追加返回代码、提示
    public function append($ret_code, $ret_msg)
    {
        if (!($ret_code || $ret_msg)) return;
        array_unshift($this->codemsg, array($ret_code, $ret_msg));
    }

    # 重设操作结果
    public function setResult($result)
    {
        $this->result = $result;
    }

    # 取得操作结果
    public function getResult()
    {
        return $this->result;
    }

    # 取得最后一条信息的代码
    public function getLastCode()
    {
        $count = count($this->codemsg);
        if (!$count) return null;
        return $this->codemsg[$count - 1][0];
    }

    # 取得最后一条信息的内容
    public function getLastMessage()
    {
        $count = count($this->codemsg);
        if (!$count) return null;
        return $this->codemsg[$count - 1][1];
    }

    # 取得提示信息
    public function getMessage($separator="\n")
    {
        if (!count($this->codemsg)) return '';

        $msg_list = array();
        foreach($this->codemsg as $error) {
            $msg_list[] =  $error[0] ? $error[0] .': '. $error[1] : $error[1];
        }
        return implode($separator, $msg_list);
    }

}


