# RocWorker
# roc.xu
```php
require_once __DIR__."/vendor/autoload.php";
$config_file = __DIR__."/common/config/main.php";
//启动http进程
\Roc\App::run("http",20001,4,$config_file);
\Roc\App::run("websocket",20002,4,$config_file);
//启动文件监控（文件改动后自动reload）
$log = new \Roc\libs\FileMonitor();
$log->run($config_file);
//清除缓存
opcache_reset();
//启动进程
\Workerman\Worker::runAll();
```