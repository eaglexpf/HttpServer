<?php
/**
 * User: Roc.xu
 * Date: 2017/7/3
 * Time: 10:15
 */

namespace HttpServer\libs;
use Exception;

class Mredis extends \Redis
{
    protected static $instance = [];

    /*
     * 获取redis链接
     */
    public static function instance($config_name){
        if (!isset($GLOBALS['config']['redis'][$config_name])) {
            echo "$config_name not set\n";
            throw new Exception("$config_name not set\n");
        }

        if (!empty(self::$instance[$config_name])) {
            if (self::$instance[$config_name]->ping()!='+PONG'){
                var_dump('repet connect');
                self::connectToRedis($config_name);
            }
        }else{
            self::connectToRedis($config_name);
        }
        return self::$instance[$config_name];
    }
    /*
     * 建立redis链接
     */
    protected static function connectToRedis($config_name){
        $config                       = $GLOBALS['config']['redis'][$config_name];
        self::$instance[$config_name] = new self();
        $host = isset($config['host'])?$config['host']:'127.0.0.1';
        $port = isset($config['port'])?$config['port']:'6379';
        self::$instance[$config_name]->connect($host,$port);
        $password = isset($config['password'])?$config['password']:'';
        if ($password){
            self::$instance[$config_name]->auth($password);
        }
        $db = isset($config['db'])?$config['db']:0;
        if (!self::$instance[$config_name]->select($db)){
            echo "redis db is can't connect\r\n";
        }
    }

    /*
     * 将一个数据设置缓存为key
     */
    public function setArrayToKey($key,$data=[],$time=60){
        $this->set($key,json_encode($data,320));
        $this->expire($key,$time);
        return true;
    }
    /*
     * 从key中获取缓存的数组
     */
    public function getArrayForKey($key){
        $data = $this->get($key);
        if ($data){
            $data = json_decode($data,true);
        }
        return $data;
    }

    //普通set操作
    public function setStrKey($key,$str,$time=0){
        $this->set($key,$str);
        if ($time){
            $this->expire($key,$time);
        }
        return true;
    }
    //普通get操作
    public function getStrKey($key){
        return $this->get($key);
    }

    //添加集合
    public function addOnlyList($key,$value,$time=0){

        $bool = $this->sAdd($key,$value);
        if ($time){
            $this->expire($key,$time);
        }
        return $bool;
    }
}