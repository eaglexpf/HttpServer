<?php
/**
 * User: Roc.xu
 * Date: 2017/5/11
 * Time: 9:59
 */

namespace HttpServer;


use HttpServer\controllers\Events;
use HttpServer\libs\FileMonitor;
use Workerman\Worker;

require_once __DIR__.'/Autoload.php';

class Api{
    /*
     * 启动方法
     */
    public static function run($port=21001,$name='http_worker',$count=4,$file=''){
        //启动http进程
        $http_worker = new Worker('http://0.0.0.0:'.$port);
        $http_worker->name = $name;
        $http_worker->count = $count;
        $http_worker->onWorkerStart = function ($worker)use($file){
            Events::onStart($worker,$file);
        };
        $http_worker->onConnect = function ($connection){
            Events::onConnect($connection);
        };
        $http_worker->onMessage = function ($connection,$data){
            Events::onMessage($connection,$data);
        };
        $http_worker->onClose = function ($connection){
            Events::onCLose($connection);
        };
        $http_worker->onError = function ($worker){
            Events::onError($worker);
        };
        $http_worker->onWorkerStop = function ($worker){
            Events::onStop($worker);
        };
        $http_worker->onWorkerReload = function ($worker)use($file){
            Events::onReload($worker,$file);
        };
        FileMonitor::run();

        Worker::runAll();
    }
    
}