<?php
namespace RocWorker\controllers;
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

    /** 直接返回消息
     * @param $data
     * @param bool $bool
     * @return mixed
     */
    public static function send($data,$bool=true){
        if ($bool){
            return self::$connection->send($data);
        }
        return self::$connection->close($data);
    }
    /**
     * 返回json数据
     * @param $data
     * @return mixed
     */
    public static function sendJson($data){
        Http::header("Content-Type:application/json; charset=UTF-8");
        Http::header("Access-Control-Allow-Origin:*");
        return self::$connection->send(json_encode($data,320));
    }

    /**
     * 返回view内容
     */
    public function sendView($view,$function_param=[]){
        foreach($function_param as $k=>$v){
            $$k = $v;
        }
        $controller = explode("controllers",self::$message["roc"]["controller"]);
        $file = __DIR__."/../../../../".str_replace("\\","/",$controller[0])."views".str_replace("\\","/",strtolower($controller[1]))."/$view.php";
        if (!is_file($file)){
            Http::header("HTTP/1.1 404 Not Found");
            self::$connection->close('<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found</h3></center></body></html>');
            return;
        }
        ini_set('display_errors', 'off');
        ob_start();
        // Try to include php file.
        try {
            // $_SERVER.
            $_SERVER['REMOTE_ADDR'] = self::$connection->getRemoteIp();
            $_SERVER['REMOTE_PORT'] = self::$connection->getRemotePort();
            include $file;
        } catch (\Exception $e) {
            // Jump_exit?
            if ($e->getMessage() != 'jump_exit') {
                echo $e;
            }
        }
        $content = ob_get_clean();
        ini_set('display_errors', 'on');
        if (strtolower($_SERVER['HTTP_CONNECTION']) === "keep-alive") {
//            self::$connection->send($content);
            self::send($content);
        } else {
            self::send($content,false);
//            self::$connection->close($content);
        }
        return ;
    }
    /*
     * 静态文件返回
     */
    public static function sendStatic($data){
        if ($data['code']==200){
            self::send($data['data']);
        }else{
            Http::header("HTTP/1.1 404 Not Found");
            self::send('<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found</h3></center></body></html>');
//            self::$connection->close('<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found</h3></center></body></html>');
        }
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
        return isset(self::$message['server'][$key])?self::$message['server'][$key]:null;
    }
}