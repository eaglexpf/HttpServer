<?php
namespace HttpServer\controllers;
use Workerman\Lib\Timer;
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
     * 日志收集
     */
    public static function collect_log($data){
        $time = round(microtime(true)-self::$connection->request_time,5);//从接收消息到发送日志，之间过程中程序的执行时间

        //判断$data是否是json格式
        json_decode($data);
        if(json_last_error() == JSON_ERROR_NONE){
            //是json格式
        }else{
            //不是json格式
            $data = '非json数据';
        }

        $msg = json_encode([
            'TIME' => $time,
            'URL' => urldecode("http://".self::$message['server']['HTTP_HOST'].self::$message['server']['REQUEST_URI']),
            'PARAM' => json_encode(['method'=>self::$message['server']['REQUEST_METHOD'],'get'=>self::$message['get'],'post'=>self::$message['post']],320),
            'CODE' => 200,
            'MSG' => $data
        ],320);
//        var_dump($data);
    }

    /** 直接返回消息
     * @param $data
     * @param bool $bool
     * @return mixed
     */
    public static function send($data,$bool=true){
        if ($bool){
            self::$connection->send($data);
            self::collect_log($data);//收集日志
            return ;
        }
        self::$connection->close($data);
        self::collect_log($data);//收集日志
        return ;
    }
    /**
     * 返回json数据
     * @param $data
     * @return mixed
     */
    public static function sendJson($data,$bool=true){
        Http::header("Content-Type:application/json; charset=UTF-8");
        Http::header("Access-Control-Allow-Origin:*");
        Http::header("Access-Control-Allow-Headers:GUID,nonceStr,timeStamp,Token,Version");
        Http::header("Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE,PUT");
//        Http::header("Access-Control-Request-Methods:POST,GET,OPTIONS,DELETE");
        return self::send(json_encode($data,320),$bool);
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
    public static function sendStatic($data,$bool=true){
        if ($data['code']==200){
            self::send($data['data'],$bool);
        }else{
            Http::header("HTTP/1.1 404 Not Found");
            self::send('<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found</h3></center></body></html>',$bool);
        }
    }

    /**
     * @param $key 参数名称
     * @param bool $power 是否必传参数（默认必传参数）
     * @param null $value 默认值
     * @return null
     * @throws \Exception
     */
    public static function get($key=false,$power=true,$value=null){
        if (!$key){
            return self::$message['get'];
        }
        if ($power){
            if (isset(self::$message['get'][$key])&&!empty(self::$message['get'][$key])){
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
    public static function post($key=false,$power=true,$value=null){
        if (!$key){
            return self::$message['post'];
        }
        if ($power){
            if (isset(self::$message['post'][$key])&&!empty(self::$message['post'][$key])){
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

    public static function header($key,$power=true){
        if (isset(self::$message['server'][strtoupper($key)])){
            return self::$message['server'][strtoupper($key)];
        }
        $key = 'HTTP_'.strtoupper($key);
        if (isset(self::$message['server'][$key])){
            return self::$message['server'][$key];
        }
        if ($power){
            throw new \Exception("缺少header参数：$key");
        }else{
            return null;
        }

    }

    public static function server($key=false){
        if (!$key){
            return self::$message['server'];
        }
        return isset(self::$message['server'][$key])?self::$message['server'][$key]:null;
    }

    public static function getController(){
        return self::$message['roc']['controller'];
    }
    public static function getAction(){
        return self::$message['roc']['action'];
    }

    public static function message(){
        return self::$message;
    }
}