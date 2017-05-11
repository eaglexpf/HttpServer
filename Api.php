<?php
/**
 * User: Roc.xu
 * Date: 2017/5/11
 * Time: 9:59
 */

namespace RocWorker;


use RocWorker\controllers\App;
use RocWorker\libs\FileMonitor;
use Workerman\Worker;


class Api{
    /*
     * 启动方法
     */
    public static function run($protocol='http',$port=21001,$name='http_worker',$count=4,$file=''){
        if (!in_array($protocol,["http","websocket"])){
            echo "只能是http协议或者websocket协议";
            return;
        }
        //启动http进程
        $http_worker = new Worker($protocol.'://0.0.0.0:'.$port);
        $http_worker->name = $name;
        $http_worker->count = $count;
        $http_worker->onWorkerStart = function ($worker)use($file){
            App::onStart($worker,$file);
        };
        $http_worker->onConnect = function ($connection){
            App::onConnect($connection);
        };
        $http_worker->onMessage = function ($connection,$data)use($protocol){
            if ($protocol=="websocket"){
                $data = json_decode($data,true);
            }
            App::onMessage($connection,$data);
        };
        $http_worker->onClose = function ($connection){
            App::onCLose($connection);
        };
        $http_worker->onError = function ($worker){
            App::onError($worker);
        };
        $http_worker->onWorkerStop = function ($worker){
            App::onStop($worker);
        };
        $http_worker->onWorkerReload = function ($worker){
            App::onReload($worker);
        };
        FileMonitor::run();

        Worker::runAll();
    }
    
}