<?php
/**
 * User: Roc.xu
 * Date: 2017/5/11
 * Time: 13:37
 */

namespace RocWorker\controllers;

use Workerman\Protocols\Http;

require_once __DIR__.'/Autoload.php';

class App{
    /*
     * 配置内容
     */
    protected static $config;
    /*
     * 检测配置
     */
    protected static function checkConfig($file){
        $roc_config = [
            'application' => 'backend',
            'statics' => 'statics'
        ];
        if (is_file($file)){//判断文件是否存在
            $config = require_once $file;
            if (is_array($config)){//判断是否是数组
                if (isset($config['application'])){//项目地址
                    $roc_config['application'] = $config['application'];
                }
                if (isset($config['statics'])){//静态资源地址
                    $roc_config['statics'] = $config['statics'];
                }
                self::$config = $config;
            }
        }
        self::$config = $roc_config;
    }
    /*
     * 程序启动
     */
    public static function onStart($worker,$file){
        self::checkConfig($file);
    }
    /*
     * 建立连接
     */
    public static function onConnect($connection){

    }
    /*
     * 抓取静态文件返回
     */
    protected static function getFile($connection,$message,$type){
        $file = __DIR__.'/../../../../'.self::$config['statics'].$message['server']['REQUEST_URI'];
        $baseController = new Controller($connection,$message);
        if (!is_file($file)){
            $baseController->sendStatic(["code"=>404,"message"=>$message['server']['REQUEST_URI'].":Not Found"]);
            return;
        }
        if (in_array($type,['jpg','png','gif'])){
            Http::header("Content-Type: image/".$type.';charset=utf-8');
        }else{
            Http::header("Content-Type: text/".$type.';charset=utf-8');
        }

        $baseController->sendStatic(['code'=>200,'data'=>file_get_contents($file)]);
    }
    /*
     * 接收消息
     */
    public static function onMessage($connection,$message){
        try{

            //将请求地址切分为数组（数组为目录和文件）
            $array = explode("/",explode('?',  self::$config['application']."/controllers".$message['server']['REQUEST_URI'])[0]);
            //判断请求地址是否有后缀；有后缀且后缀不是php的抓取静态文件返回
            if(strstr($array[count($array)-1], '.')){
                $file_data = explode('.',$array[count($array)-1]);
                if ($file_data[1]!=='php'){
                    self::getFile($connection,$message,$file_data[1]);
                    return;
                }
                $array[count($array)-1] = $file_data[0];
//                throw new \Exception($message['server']['REQUEST_URI'].":Not Found",404);
            }
            //没有请求路径时设置默认首页index/index
            if (empty($array[2])&&count($array)==3){
                $array[2] = "index";
                array_push($array, 'index');
            }
            //请求路径只有一个时；设置默认方法index
            if (count($array)==3){
                array_push($array, 'index');
            }
            /**
             * 第一种可能；请求地址包含文件名称和方法名称
             */
            //请求地址的绝对路径（去掉方法名称）
            $action = $array[count($array)-1];
            array_pop($array);
            $array[count($array)-1] = ucfirst($array[count($array)-1]);
            $controller = implode("\\",$array);
            $file = __DIR__."/../../../../".implode("/",$array).".php";
            //文件不存在
            if (!is_file($file)) {
                /**
                 * 第二种可能：请求地址只包含文件名称（自动添加index方法）
                 */
                array_push($array, $action);
                $action = "index";
                $array[count($array)-1] = ucfirst($array[count($array)-1]);
                $controller = implode("\\",$array);
                $file = __DIR__."/../../../../".implode("/",$array).".php";
                if (!is_file($file)) {
                    throw new \Exception("Class:$controller Not Found", 404);
                }
            }
            $message["roc"] = [
                "controller" => $controller,
                "action" => $action
            ];
            //初始化文件
            $model = new $controller($connection,$message);
            //方法不存在
            if (!method_exists($model, $action)) {
                throw new \Exception("Action:$action Not Found",404);
            }
            $result = $model->$action();
        }catch (\Exception $e){
            $errorCode = $e->getCode()?$e->getCode():500;
            $baseController = new Controller($connection,$message);
            $baseController->sendJson(["code"=>$errorCode,"message"=>$e->getMessage()]);
        }catch (\Error $e){
            $errorCode = $e->getCode()?$e->getCode():500;
            $baseController = new Controller($connection,$message);
            $baseController->sendJson(["code"=>$errorCode,"message"=>$e->getMessage()]);
        }
    }
    /*
     * 连接关闭
     */
    public static function onCLose($connection){

    }
    /*
     * 进程遇到错误
     */
    public static function onError($worker){

    }
    /*
     * 进程停止
     */
    public static function onStop($worker){

    }
    /*
     * 进程重启
     */
    public static function onReload($worker){

    }
}