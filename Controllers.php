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
    protected $connection = null;
    protected $message = null;
    protected $start_time = null;
    
    public function __construct($connection,$data)
    {
        $this->start_time = microtime(true);
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
        $this->send($json,$bool);
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

}