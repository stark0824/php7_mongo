<?php
# +----------------------------------------------------------------------
# | Author:Stark
# +----------------------------------------------------------------------
# | Date:2022/7/27
# +----------------------------------------------------------------------
# | Desc: MongoDB类
# +----------------------------------------------------------------------
require_once __DIR__ . "/MongoDB/vendor/autoload.php";
class Lib_MongoDB
{
    private $_client = null;
    private static $_obj = null;
    private $_url = "mongodb://127.0.0.1/";

    public function __construct()
    {
        $mongodbArr = Lib_Config::getConfig("mongodb");
        $this->_url = $mongodbArr["url"] ?? $this->_url;
        $options = $mongodbArr["options"] ?? [];
        $this->_client = new MongoDB\Client($this->_url, $options);
    }

    public static function getInstance()
    {
        if (is_null(self::$_obj)) {
            self::$_obj = new Lib_MongoDB();
        }
        return self::$_obj->_client;
    }
}