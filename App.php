<?php
namespace RocWorker;
use RocWorker\controllers\Controller;
use RocWorker\libs\FileMonitor;
use Workerman\Worker;
/**
 * Created by PhpStorm.
 * User: roc
 * Date: 2017/3/25
 * Time: 0:06
 */
require_once __DIR__."/Autoload.php";
class App
{
    protected static $config;
    protected static $userConfigFile;
    protected static $userConfig;
    
    protected static function onStart($http_worker){
        //引入配置文件
        self::$config = require_once (__DIR__."/config/main.php");
        //配置文件不存在
        if (!is_file(self::$userConfigFile)) {
            self::$userConfig = [];
        }else {
            self::$userConfig = require_once(self::$userConfigFile);
            if (isset(self::$userConfig["db"])){
                $GLOBALS["db"] = self::$userConfig["db"];
            }
        }
    }

    protected static function onConnect($connection){
    }

    protected static function onMessage($connection, $message){
        try{
            $config = self::$userConfig;
            $application = self::$config["application"];
            if (isset($config["location"])){
                foreach ($config as $key=>$value){
                    if ($value===$message['server']['SERVER_NAME'])
                        $application = $key;
                }
            }
            //将请求地址切分为数组（数组为目录和文件）
            $array = explode("/",explode('?',  $application."/controllers".$message['server']['REQUEST_URI'])[0]);
            //判断请求地址是否有后缀；有后缀做错误处理
            if(strstr($array[count($array)-1], '.')){
                throw new \Exception($message['server']['REQUEST_URI'].":Not Found",404);
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
            $file = __DIR__."/../../../".implode("/",$array).".php";
            //文件不存在
            if (!is_file($file)) {
                /**
                 * 第二种可能：请求地址只包含文件名称（自动添加index方法）
                 */
                array_push($array, $action);
                $action = "index";
                $array[count($array)-1] = ucfirst($array[count($array)-1]);
                $controller = implode("\\",$array);
                $file = __DIR__."/../../../".implode("/",$array).".php";
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

    protected static function onStop($http_worker){

    }

    /**
     * @param $protocol
     * @param int $port
     * @param int $worker_count
     * @param string $file
     */
    public static function run($protocol='http',$port=20001,$worker_count=4,$file=""){
        self::$userConfigFile = $file;
        if (!in_array($protocol,["http","websocket"])){
            echo "只能是http协议或者websocket协议";
            return;
        }
        // #### http worker ####
        $http_worker = new Worker("$protocol://0.0.0.0:$port");
        $http_worker->name = "$protocol Service";
        // 4 processes
        $http_worker->count = $worker_count;
        //初始化进程
        $http_worker->onWorkerStart = function ($http_worker){
            self::onStart($http_worker);
        };
        //建立连接
        $http_worker->onConnect = function ($connection){
            self::onConnect($connection);
        };

        // 接收消息
        $http_worker->onMessage = function($connection, $data)use($protocol){
            if ($protocol=="websocket"){
                $data = json_decode($data,true);
            }
            self::onMessage($connection, $data);
        };

        //进程终止
        $http_worker->onWorkerStop = function ($http_worker){
            self::onStop($http_worker);
        };

    }
}