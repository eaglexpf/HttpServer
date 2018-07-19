<?php
/**
 * User: Roc.xu
 * Date: 2018/6/20
 * Time: 10:27
 */

namespace HttpServer;


use Workerman\Protocols\Http;
use Workerman\Protocols\HttpCache;

class Controllers
{
    public $connection = null;
    public $message = null;
    public $start_time = null;

    protected $local_time = null;
    protected $runTime = null;
    protected $sendData = null;
    
    public function __construct($connection,$data)
    {
        $this->start_time = microtime(true);
        $this->connection = $connection;
        $this->message = $data;
    }
//    public function aaa()
//    {
//
//        $log_dir = isset($GLOBALS['config']['log'])?$GLOBALS['config']['log']:__DIR__.'/../..';
//        $log_dir = str_replace('\\', DIRECTORY_SEPARATOR, $log_dir);
//        $log_dir = str_replace('/', DIRECTORY_SEPARATOR, $log_dir);
//        $log_dir = $log_dir.DIRECTORY_SEPARATOR.date('Y-m',time()).DIRECTORY_SEPARATOR.date('d',time()).DIRECTORY_SEPARATOR;
//        if (!is_dir($log_dir)){
//            mkdir($log_dir,777,true);
//        }
//        $count = count(glob($log_dir.'*.log'));
//        $count = $count>0?$count:1;
//        $file_name = date('Ymd',time()).'-'.str_pad($count,3,"0",STR_PAD_LEFT).'.log';
//
//
//        $cut = ' ';
//        $zhengze = '%{DATA:year}-%{DATA:month}-%{DATA:day}\ %{TIME:time}\ %{DATA:ip}\ %{HOSTPORT:host}\ %{DATA:method}\ %{DATA:url}\ %{DATA:status}\ %{DATA:runTime}\ %{DATA:request}\ %{DATA:response}\ %{QS:agent}';
//        $format = date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']);
//        $log_data = $format.$cut.$_SERVER['REMOTE_ADDR'].$cut.$_SERVER['HTTP_HOST'].$cut.$_SERVER['REQUEST_METHOD'].$cut.$_SERVER['REQUEST_URI'].
//            $cut.(isset(HttpCache::$header['Http-Code'])?HttpCache::$header['Http-Code']:200).$cut.$this->runTime.$cut.
//            $GLOBALS['HTTP_RAW_REQUEST_DATA'].$cut.$this->sendData.$cut."'".$_SERVER['HTTP_USER_AGENT']."'\r\n";
//        if (is_file($log_dir.$file_name)){
//            if (filesize($log_dir.$file_name)>1024*1024*10){
//                $file_name = date('Ymd',time()).'-'.str_pad($count+1,3,"0",STR_PAD_LEFT).'.log';
//            }
//        }
//        file_put_contents($log_dir.$file_name,$log_data,FILE_APPEND | LOCK_EX);
//    }


    public function send($data,$bool=false){
        $this->runTime = round(microtime(true)-$this->start_time,5);
        Http::header("Run-Time:{$this->runTime}");
        $this->sendData = $data;
        Log::saveLog($this->runTime,$data);
        if ($bool){
            return $this->connection->send($data);
        }
        return $this->connection->close($data);
    }

    public function sendJson($data,$bool=false){
        Http::header("Content-Type:application/json; charset=UTF-8");
        $json = json_encode($data,320);
        return $this->send($json,$bool);
    }

    public function get($key,$bool=true,$value=null){
        if (!$key){
            return $this->message['get'];
        }
        if ($bool){
            if (isset($this->message['get'][$key])&&!empty($this->message['get'][$key])){
                return $this->message['get'][$key];
            }
            throw new \Exception("GET：缺少参数$key",400);
        }else{
            if (isset($this->message['get'][$key])){
                return $this->message['get'][$key];
            }else{
                return $value;
            }
        }
    }

    public function post($key=false,$bool=true,$value=null){
        if (!$key){
            return $this->message['post'];
        }
        if ($bool){
            if (isset($this->message['post'][$key])&&!empty($this->message['post'][$key])){
                return $this->message['post'][$key];
            }
            throw new \Exception("POST：缺少参数$key",400);
        }else{
            if (isset($this->message['post'][$key])){
                return $this->message['post'][$key];
            }else{
                return $value;
            }
        }
    }
    public function files(){
        return $this->message['files'];
    }
    public function request($key=false,$power=true,$value=null){
        if (!$key){
            return $this->message;
        }
        if (isset($this->message['get'][$key])){
            if ($power){
                if (!empty($this->message['get'][$key])){
                    return $this->message['get'][$key];
                }else{
                    throw new \Exception("缺少参数$key",400);
                }
            }
            return $this->message['get'][$key];
        }elseif (isset($this->message['post'][$key])){
            if ($power){
                if (!empty($this->message['post'][$key])){
                    return $this->message['post'][$key];
                }else{
                    throw new \Exception("缺少参数$key",400);
                }
            }
            return $this->message['post'][$key];
        }elseif($power){
            if (is_null($value)){
                throw new \Exception("缺少参数$key",400);
            }
            return $value;
        }else{
            return $value;
        }
    }
    public function header($key=false,$power=true,$value=null){
        if (!$key){
            return $this->message['server'];
        }
        if (isset($this->message['server']['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])){
            throw new \Exception("正在验证HEADER是否符合标准",200);
        }
        if ($power){
            if (isset($this->message['server'][strtoupper($key)])&&!empty($this->message['server'][strtoupper($key)])){
                return $this->message['server'][strtoupper($key)];
            }elseif(isset($this->message['server']['HTTP_'.strtoupper($key)])&&!empty($this->message['server']['HTTP_'.strtoupper($key)])){
                return $this->message['server']['HTTP_'.strtoupper($key)];
            }else if (!is_null($value)){
                return $value;
            }else{
                throw new \Exception("HEADER:缺少参数$key",400);
            }
        }else{
            if (isset($this->message['server'][strtoupper($key)])){
                return $this->message['server'][strtoupper($key)];
            }else if(isset($this->message['server']['HTTP_'.strtoupper($key)])){
                return $this->message['server']['HTTP_'.strtoupper($key)];
            }else{
                return $value;
            }
        }
    }
}