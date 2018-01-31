<?php namespace service\core;


class SystemVariable
{
    ############################################################
    # 服务器环境预定义变量操作

    public static function server($key, $default=NULL)
    {
        if (!array_key_exists($key, $_SERVER)) return $default;
        return $_SERVER[$key];
    }

    public static function env($key, $default=NULL)
    {
        if (!array_key_exists($key, $_ENV)) return $default;
        return $_ENV[$key];
    }


    ############################################################
    # HTTP 预定义变量相关操作

    public static function httpGet($key, $default=NULL)
    {
        if (!array_key_exists($key, $_GET)) return $default;
        return $_GET[$key];
    }

    public static function httpPost($key, $default=NULL)
    {
        if (!array_key_exists($key, $_POST)) return $default;
        return $_POST[$key];
    }

    public static function httpRawPost()
    {
        return file_get_contents("php://input");
    }

    public static function httpFile($key, $default=NULL)
    {
        if (!array_key_exists($key, $_FILES)) return $default;
        return $_FILES[$key];
    }


    ############################################################
    # Session 相关操作

    #
    # 全局 Session 操作
    #

    public static function getGlobalSession($key, $default=NULL)
    {
        if (!session_id()) return $default;

        # 生成完整键值，并判断该值是否存在
        $key_full = self::_generate_and_check_session_key($key);
        if (!$key_full) return $default;

        # 取得该值（指针）
        $obj = null;
        $cmd = '$obj = $_SESSION'. $key_full .';';
        eval($cmd);
        return $obj;
    }

    public static function setGlobalSession($key, $val)
    {
        if (!session_id()) return false;

        # 生成完整键值
        $key_list = explode('::', $key);
        $key_full = "['". implode("']['", $key_list) ."']";

        # 设置 SESSION 对象
        $cmd = '$obj = &$_SESSION'. $key_full .';';
        eval($cmd);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $obj = $val;
        return true;
    }

    #
    # Application Session 操作
    #

    private static $session_id = NULL;

    public static function setSessionID($id)
    {
        self::$session_id = strtolower($id);
    }
    public static function getSessionID()
    {
        return self::$session_id;
    }

    public static function getSession($key, $default=NULL)
    {
        if (self::$session_id) $key = self::$session_id .'::'. $key;
        return self::getGlobalSession($key, $default);
    }

    public static function setSession($key, $val)
    {
        if (self::$session_id) $key = self::$session_id .'::'. $key;
        return self::setGlobalSession($key, $val);
    }

    public static function cleanSession()
    {
        if (!session_id()) return false;
        if (!self::$session_id) return true;
        unset($_SESSION[self::$session_id]);
        return true;
    }

    private static function _generate_and_check_session_key($keys)
    {
        # 对键名进行处理“parent::children”
        $key_list = explode('::', $keys);

        # 依次处理每个键名
        $key_full = '';
        $key_cnt  = count($key_list) - 1;
        for($i=0; $i<= $key_cnt; $i++)
        {
            $result = true;

            # 检测上一级是否为数组
            $cmd = '$result = is_array($_SESSION'. $key_full .');';
            eval($cmd); if (!$result) return false;

            # 检测当前级别是否存在
            $cmd = '$result = array_key_exists("'. $key_list[$i] .'", $_SESSION'. $key_full .');';
            eval($cmd); if (!$result) return false;

            # 组合键名
            $key_full .= "['". $key_list[$i] ."']";
        }

        return $key_full;
    }


    ############################################################
    # Cookie 相关操作

    public static function getCookie($key, $default=NULL)
    {
        if (!array_key_exists($key, $_COOKIE)) return $default;
        return $_COOKIE[$key];
    }

    public static function setCookie($key, $val, $lifetime=0)
    {
        if ($lifetime) $lifetime = time() + $lifetime;
        setcookie($key, $val, $lifetime, '/');
        return true;
    }

}


