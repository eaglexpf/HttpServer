<?php
/**
 * User: Roc.xu
 * Date: 2018/6/19
 * Time: 13:34
 */

namespace HttpServer;


use Workerman\Protocols\Http;
use Workerman\Worker;
require_once __DIR__.'/Autoload.php';
class HttpServer extends Worker
{
    protected $serverRoot = [];
    protected static $mimeTypeMap = [];
    protected $_onWorkerStart = null;
    public $config_file = null;

    public function __construct($socket_name, $context_option = []){
        list(, $address) = explode(':', $socket_name, 2);
        parent::__construct('http:' . $address, $context_option);
        $this->name = 'HttpServer';
    }
    public function run(){
        $this->initConfig();
        $this->_onWorkerStart = $this->onWorkerStart;
        $this->onWorkerStart  = array($this, 'onWorkerStart');
        $this->onConnect = array($this,'onConnect');
        $this->onClose = array($this,'onClose');
        $this->onError = array($this,'onError');
        $this->onMessage      = array($this, 'onMessage');
        parent::run();
    }

    public function onConnect($connection){
        var_dump('this is connect ,connection id is '.$connection->id);
    }
    public function onClose($connection){
        var_dump('this is close , connection id is '.$connection->id);
    }
    public function onError(){
        var_dump('this is error,');
    }

    protected function initConfig(){
        if (!is_file($this->config_file)){
            Worker::safeEcho(new \Exception('config_file not set, please use string to set config_file'));
            exit(250);
        }
        $config = require_once $this->config_file;
        if (!is_array($config)){
            Worker::safeEcho(new \Exception('config not set, please use array to set '.$this->config_file));
            exit(250);
        }
        if (!isset($config['domain'])){
            Worker::safeEcho(new \Exception('domain not set, please use array to set '.$this->config_file));
            exit(250);
        }
        $GLOBALS['config'] = $config;
        foreach ($config['domain'] as $k=>$value){
            $this->addRoot($k,$value);
        }
    }

    protected function addRoot($domain,$config){
        if (is_array($config)&&isset($config['root'])&&isset($config['controller'])){
            $this->serverRoot[$domain] = $config;
            return;
        }
        Worker::safeEcho(new \Exception('server root not set, please use array to set server root path'));
        exit(250);
    }

    public function initMimeTypeMap(){
        $mime_file = Http::getMimeTypesFile();
        if (!is_file($mime_file)) {
            $this->log("$mime_file mime.type file not fond");
            return;
        }
        $items = file($mime_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($items)) {
            $this->log("get $mime_file mime.type content fail");
            return;
        }
        foreach ($items as $content) {
            if (preg_match("/\s*(\S+)\s+(\S.+)/", $content, $match)) {
                $mime_type                      = $match[1];
                $workerman_file_extension_var   = $match[2];
                $workerman_file_extension_array = explode(' ', substr($workerman_file_extension_var, 0, -1));
                foreach ($workerman_file_extension_array as $workerman_file_extension) {
                    self::$mimeTypeMap[$workerman_file_extension] = $mime_type;
                }
            }
        }
    }

    public function onWorkerStart($worker){
        mt_srand($worker->id);
        if (empty($this->serverRoot)) {
            Worker::safeEcho(new \Exception('server root not set, please use WebServer::addRoot($domain, $root_path) to set server root path'));
            exit(250);
        }

        // Init mimeMap.
        $this->initMimeTypeMap();

        // Try to emit onWorkerStart callback.
        if ($this->_onWorkerStart) {
            try {
                call_user_func($this->_onWorkerStart, $this);
            } catch (\Exception $e) {
                self::log($e);
                exit(250);
            } catch (\Error $e) {
                self::log($e);
                exit(250);
            }
        }
    }


    public function onMessage($connection,$data){
        //查询域名配置
        $http_siteConfig = isset($this->serverRoot[$_SERVER['SERVER_NAME']]) ? $this->serverRoot[$_SERVER['SERVER_NAME']] : current($this->serverRoot);

        $http_url_info = parse_url($_SERVER['REQUEST_URI']);
        $http_path = isset($http_url_info['path']) ? $http_url_info['path'] : '/';
        $http_path_info      = pathinfo($http_path);
        $http_file_extension = isset($http_path_info['extension']) ? $http_path_info['extension'] : '';
        //接口请求
        if ($http_file_extension===''||$http_file_extension==='php'){
            if ($http_path==='/'){
                $http_path = '/Index/index';
                $http_path_info = pathinfo($http_path);
            }
            //第一种；请求路径包含类和方法
            $model_name = str_replace('/','\\',$http_siteConfig['controller'].$http_path_info['dirname']);
            $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $http_siteConfig['root'].$model_name.'.php');
            if (is_file($class_path)){
                $func_name = !empty($http_path_info['basename'])?$http_path_info['basename']:'index';
                if (class_exists($model_name)){
                    if (method_exists($model_name,$func_name)){
                        $connection->start_time = microtime(true);
                        try{
                            $model = new $model_name($connection,$data);
                            $model->start_time = $connection->start_time;
                            $model->$func_name();
                            return;
                        }catch (\Exception $e){
                            $msg = json_encode(['code'=>$e->getCode(),'msg'=>$e->getMessage()],320);
                        }catch (\Error $e){
                            $msg = json_encode(['code'=>$e->getCode(),'msg'=>$e->getMessage()],320);
                        }
                        Http::header("Content-Type:application/json; charset=UTF-8");
                        $runTime = round(microtime(true)-$connection->start_time,5);
                        Http::header("Run-Time:$runTime");
                        $connection->send($msg);
                        return;
                    }
                }
            }
            //第二种；请求路径只包含类
            $model_name = str_replace('/','\\',$http_siteConfig['controller'].$http_path);
            $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $http_siteConfig['root'].$model_name.'.php');
            if (is_file($class_path)){
                $func_name = 'index';
                if (class_exists($model_name)){
                    if (method_exists($model_name,$func_name)){
                        $connection->start_time = microtime(true);
                        try{
                            $model = new $model_name($connection,$data);
                            $model->start_time = $connection->start_time;
                            $model->$func_name();
                            return;
                        }catch (\Exception $e){
                            $msg = json_encode(['code'=>$e->getCode(),'msg'=>$e->getMessage()],320);
                        }catch (\Error $e){
                            $msg = json_encode(['code'=>$e->getCode(),'msg'=>$e->getMessage()],320);
                        }
                        Http::header("Content-Type:application/json; charset=UTF-8");
                        $connection->send($msg);
                        return;
                    }
                }
            }
            // 404
            Http::header("HTTP/1.1 404 Not Found");
            if(isset($http_siteConfig['custom404']) && file_exists($http_siteConfig['custom404'])){
                $html404 = file_get_contents($http_siteConfig['custom404']);
            }else{
                $html404 = '<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found</h3></center></body></html>';
            }
            $connection->close($html404);
            return;
        }
        //静态资源
        $http_file = isset($http_siteConfig['statics'])?$http_siteConfig['statics']."/$http_path":$http_path;
        if (is_file($http_siteConfig['root'].$http_file)){
            self::sendFile($connection,$http_siteConfig['root'].$http_file);
        }else{
            // 404
            Http::header("HTTP/1.1 404 Not Found");
            if(isset($http_siteConfig['custom404']) && file_exists($http_siteConfig['custom404'])){
                $html404 = file_get_contents($http_siteConfig['custom404']);
            }else{
                $html404 = '<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found</h3></center></body></html>';
            }
            $connection->close($html404);
            return;
        }
    }

    public static function sendFile($connection,$file_path){
        // Check 304.
        $info = stat($file_path);
        $modified_time = $info ? date('D, d M Y H:i:s', $info['mtime']) . ' ' . date_default_timezone_get() : '';
        if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $info) {
            // Http 304.
            if ($modified_time === $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
                // 304
                Http::header('HTTP/1.1 304 Not Modified');
                // Send nothing but http headers..
                $connection->close('');
                return;
            }
        }

        // Http header.
        if ($modified_time) {
            $modified_time = "Last-Modified: $modified_time\r\n";
        }
        $file_size = filesize($file_path);
        $file_info = pathinfo($file_path);
        $extension = isset($file_info['extension']) ? $file_info['extension'] : '';
        $file_name = isset($file_info['filename']) ? $file_info['filename'] : '';
        $header = "HTTP/1.1 200 OK\r\n";
        if (isset(self::$mimeTypeMap[$extension])) {
            $header .= "Content-Type: " . self::$mimeTypeMap[$extension] . "\r\n";
        } else {
            $header .= "Content-Type: application/octet-stream\r\n";
            $header .= "Content-Disposition: attachment; filename=\"$file_name\"\r\n";
        }
        $header .= "Connection: keep-alive\r\n";
        $header .= $modified_time;
        $header .= "Content-Length: $file_size\r\n\r\n";
        $trunk_limit_size = 1024*1024;
        if ($file_size < $trunk_limit_size) {
            return $connection->send($header.file_get_contents($file_path), true);
        }
        $connection->send($header, true);

        // Read file content from disk piece by piece and send to client.
        $connection->fileHandler = fopen($file_path, 'r');
        $do_write = function()use($connection)
        {
            // Send buffer not full.
            while(empty($connection->bufferFull))
            {
                // Read from disk.
                $buffer = fread($connection->fileHandler, 8192);
                // Read eof.
                if($buffer === '' || $buffer === false)
                {
                    return;
                }
                $connection->send($buffer, true);
            }
        };
        // Send buffer full.
        $connection->onBufferFull = function($connection)
        {
            $connection->bufferFull = true;
        };
        // Send buffer drain.
        $connection->onBufferDrain = function($connection)use($do_write)
        {
            $connection->bufferFull = false;
            $do_write();
        };
        $do_write();
    }


}