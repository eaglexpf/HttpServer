<?php
/**
 * User: Roc.xu
 * Date: 2018/7/11
 * Time: 16:21
 */

namespace HttpServer;


use Workerman\Protocols\HttpCache;

class Log
{
    public static function saveLog($runTime,$sendData){
        $log_dir = isset($GLOBALS['config']['log'])?$GLOBALS['config']['log']:__DIR__.'/../..';
        $log_dir = str_replace('\\', DIRECTORY_SEPARATOR, $log_dir);
        $log_dir = str_replace('/', DIRECTORY_SEPARATOR, $log_dir);
        $log_dir = $log_dir.DIRECTORY_SEPARATOR.date('Y-m',time()).DIRECTORY_SEPARATOR.date('d',time()).DIRECTORY_SEPARATOR;
        if (!is_dir($log_dir)){
            mkdir($log_dir,777,true);
        }
        $count = count(glob($log_dir.'*.log'));
        $count = $count>0?$count:1;
        $file_name = date('Ymd',time()).'-'.str_pad($count,3,"0",STR_PAD_LEFT).'.log';


//        $cut = ' ';
//        $zhengze = '%{DATA:year}-%{DATA:month}-%{DATA:day}\ %{TIME:time}\ %{DATA:ip}\ %{HOSTPORT:host}\ %{DATA:method}\ %{DATA:url}\ %{DATA:status}\ %{DATA:runTime}\ %{DATA:request}\ %{DATA:response}\ %{QS:agent}';

//        $format = date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']);

        $header = isset(HttpCache::$header['Http-Code'])?HttpCache::$header['Http-Code']:'HTTP/1.1 200 OK';
        list(, $status, ) = explode(' ',$header);
        if (isset($_SERVER['response_status'])){
            $status = $_SERVER['response_status'];
        }
        if (!isset(HttpCache::$header['Content-Type'])||strpos(HttpCache::$header['Content-Type'],'json')===false){
            $sendData = '';
        }
        if (strlen($sendData)>10240){
            $sendData = '';
        }

        $request = $GLOBALS['HTTP_RAW_REQUEST_DATA'];
        if (strlen($request)>10240){
            $request = '';
        }
//        $log_data = $format.$cut.$_SERVER['REMOTE_ADDR'].$cut.$_SERVER['HTTP_HOST'].$cut.$_SERVER['REQUEST_METHOD'].$cut.$_SERVER['REQUEST_URI'].
//            $cut.$status.$cut.$runTime.$cut.$GLOBALS['HTTP_RAW_REQUEST_DATA'].$cut.$sendData.$cut."'".$_SERVER['HTTP_USER_AGENT']."'\r\n";
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_REAL_IP'])){
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        $log_data = json_encode([
            'timestamp' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),
            'ip' => $ip,
            'host' => $_SERVER['HTTP_HOST'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'url' => $_SERVER['REQUEST_URI'],
            'status' => $status,
            'run-time' => $runTime,
            'request' => $request,
            'response' => $sendData,
            'user-agent' => $_SERVER['HTTP_USER_AGENT']
        ],320)."\r\n";


        if (is_file($log_dir.$file_name)){
            if (filesize($log_dir.$file_name)>1024*1024*10){
                $file_name = date('Ymd',time()).'-'.str_pad($count+1,3,"0",STR_PAD_LEFT).'.log';
            }
        }
        file_put_contents($log_dir.$file_name,$log_data,FILE_APPEND | LOCK_EX);
    }

}