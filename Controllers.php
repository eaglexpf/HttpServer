<?php
/**
 * User: Roc.xu
 * Date: 2018/6/20
 * Time: 10:27
 */

namespace HttpServer;


use Workerman\Protocols\Http;

class Controllers
{
    public $connection = null;
    public $message = null;
    public $start_time = null;
    
    public function __construct($connection,$data)
    {
//        $this->start_time = microtime(true);
        $this->connection = $connection;
        $this->message = $data;
    }
    
    public function send($data,$bool=false){
        $runTime = round(microtime(true)-$this->start_time,5);
        Http::header("Run-Time:$runTime");
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