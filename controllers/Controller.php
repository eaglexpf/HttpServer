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

    /**
     * 返回json数据
     * @param $data
     * @return mixed
     */
    public static function sendJson($data){
        Http::header("Content-type: application/json");
        Http::header("Access-Control-Allow-Origin:*");
        return self::$connection->close(json_encode($data,320));
    }

    /**
     * 返回html内容
     */
    public function sendHtml($view,$data){
        $controller = explode("controllers",self::$message["roc"]["controller"]);
        $file = __DIR__."/../../../../".str_replace("\\","/",$controller[0])."views".str_replace("\\","/",$controller[1])."/$view.php";
//        $content = file_get_contents($file);
        ob_start();
        include $file;
        $content = ob_get_clean();var_dump($content);
        return self::$connection->close($content);
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

    public static function server($key){
        return self::$message['server'][$key];
    }
}