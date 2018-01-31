<?php

if (!defined('YAR_OPT_CONNECT_TIMEOUT')) define('YAR_OPT_CONNECT_TIMEOUT', 0);
if (!defined('YAR_OPT_PACKAGER'))        define('YAR_OPT_PACKAGER', 0);

if (!class_exists('Yar_Client')) {
    class Yar_Client {
        public function SetOpt($name, $value) {}
        public function call($method, $parameter) {
            if (!$method)    return false;
            if (!$parameter) return false;
            return array();
        }
    }
}

if (!class_exists('Yar_Server_Exception')) {
    class Yar_Server_Exception extends \Exception{
        public function getType() {
            return '';
        }
    }
}



