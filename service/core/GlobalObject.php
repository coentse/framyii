<?php namespace service\core;

use yii\base\Object;

class GlobalObject extends Object implements \ArrayAccess
{
    ############################################################
    # 外部调用方法

    /*
     * 设置全局对象
     *
     * GlobalObject::set('key', 'val');
     * GlobalObject::set(array(
     *      'key1' => 'val1',
     *      'key2' => 'val2',
     *      ...
     * ));
     */
    public static function set()
    {
        # 设置数组数据
        if (func_num_args() < 2) {
            $data = func_get_arg(0);
            if (!is_array($data)) {
                throw new \Exception('set global object error (array)');
            }
            foreach($data as $key => $val) {
                self::$_container[$key] = $val;
            }
            return;
        }

        # 设置 key/val 数据
        self::$_container[func_get_arg(0)] = func_get_arg(1);
    }

    # 获取全局对象
    public static function get($key, $default=NULL)
    {
        if (!array_key_exists($key, self::$_container)) return $default;
        return self::$_container[$key];
    }

    # 检测全局对象是否存在
    public static function check($key)
    {
        return isset(self::$_container[$key]);
    }

    # 销毁全局对象
    public static function destroy($key)
    {
        unset(self::$_container[$key]);
    }

    # 调试输出
    public static function dump()
    {
        echo "<pre>\n";
        echo "*** Key list ***\n";
        foreach(self::$_container as $key => $val) {
            echo "  {$key}\n";
        }
        echo "</pre>\n";
        exit();
    }

    ############################################################
    # 内部属性

    private static $_container = array();


    ############################################################
    # “数组式访问”接口实现

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            self::$_container[] = $value;
        }
        else {
            self::$_container[$offset] = $value;
        }
    }

    public function offsetGet($offset)
    {
        if (isset(self::$_container[$offset])) {
            return self::$_container[$offset];
        }
        return null;
    }

    public function offsetExists($offset)
    {
        return isset(self::$_container[$offset]);
    }

    public function offsetUnset($offset )
    {
        unset(self::$_container[$offset]);
    }

}


