<?php
namespace Roc\libs;
use Workerman\Worker;
use Workerman\Lib\Timer;
/**
 * Created by PhpStorm.
 * User: roc
 * Date: 2017/3/26
 * Time: 1:04
 */
class FileMonitor{
    public static $time;
    public static $config_file;
    public function run($config_file){
        $worker = new Worker();
        $worker->name = 'FileMonitor';
        $worker->reloadable = false;
        self::$time = time();
        self::$config_file = $config_file;

        $worker->onWorkerStart = function(){
            //配置文件不存在
            if (!is_file(self::$config_file)) {
                $userConfig = ["FileMonitor"=>[__DIR__."/../../../../"]];
            }else {
                $userConfig = require_once(self::$config_file);
            }
            $dir_data = isset($userConfig["FileMonitor"])?$userConfig["FileMonitor"]:[];
            // chek mtime of files per second
            Timer::add(1, [$this, 'check_files_change'],[$dir_data]);
        };
    }

    public function check_files_change($dir_data){
        $last_mtime = self::$time;
        // recursive traversal directory
        foreach ($dir_data as $value){
            $dir_iterator = new \RecursiveDirectoryIterator($value);
            $iterator = new \RecursiveIteratorIterator($dir_iterator);
            foreach ($iterator as $file)
            {
                // only check php files
                if(pathinfo($file, PATHINFO_EXTENSION) != 'php')
                {
                    continue;
                }
                // check mtime
                if($last_mtime < $file->getMTime())
                {
                    echo $file." update and reload\n";
                    // send SIGUSR1 signal to master process for reload
                    posix_kill(posix_getppid(), SIGUSR1);
                    self::$time = $last_mtime = $file->getMTime();
                    break;
                }
            }
        }

    }
}