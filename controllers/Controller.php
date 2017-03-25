<?php
namespace Roc\controllers;
use Workerman\Protocols\Http;
/**
 * Created by PhpStorm.
 * User: roc
 * Date: 2017/3/25
 * Time: 16:10
 */
class Controller{
    public static $connection;
    public static $message;
    public function __construct($connection,$message){
        self::$connection = $connection;
        self::$message = $message;
    }

    public static function sendJson($data){
        Http::header("Content-type: application/json");
        Http::header("Access-Control-Allow-Origin:*");
        self::$connection->close(json_encode($data,320));
    }

    /**
     * @param $key 参数名称
     * @param bool $power 是否必传参数（默认必传参数）
     * @param null $value 默认值
     * @return null
     * @throws \Exception
     */
    public static function get($key,$power=true,$value=null){
        if ($power){
            if (isset(self::$message['get'][$key])){
                return self::$message['get'][$key];
            }
            throw new \Exception("GET：缺少参数$key",400);
        }else{
            if (isset(self::$message['get'][$key])){
                return self::$message['get'][$key];
            }else{
                return $value;
            }
        }
    }

    /**
     * @param $key
     * @param bool $power
     * @param null $value
     * @return null
     * @throws \Exception
     */
    public static function post($key,$power=true,$value=null){
        if ($power){
            if (isset(self::$message['post'][$key])){
                return self::$message['post'][$key];
            }
            throw new \Exception("POST：缺少参数$key",400);
        }else{
            if (isset(self::$message['post'][$key])){
                return self::$message['post'][$key];
            }else{
                return $value;
            }
        }
    }
}