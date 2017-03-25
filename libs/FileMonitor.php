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
    public function run(){
        $worker = new Worker();
        $worker->name = 'FileMonitor';
        $worker->reloadable = false;
        self::$time = time();

        $worker->onWorkerStart = function(){
            $userConfig = require_once (__DIR__."/../../../../common/config/main.php");
            $dir_data = isset($userConfig["FileMonitor"])?$userConfig["FileMonitor"]:[];
            // chek mtime of files per second
            Timer::add(1, [$this, 'check_files_change'],[$dir_data]);
        };
        Worker::runAll();
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