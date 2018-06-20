# HttpServer
# roc.xu

### composer require eaglexpf/http-server @dev

## 代码示例
```php
start.php  启动文件


require_once __DIR__.'/vendor/autoload.php';

$http = new \HttpServer\HttpServer("http://0.0.0.0:21001");
//进程名字
$http->name = 'HttpServer';
//进程数量
$http->count = 4;
//配置文件地址
$http->config_file = __DIR__.'/config.php';

\Workerman\Worker::runAll();


config.php配置文件
return [
    'domain' => [
        'localhost' => [                            //域名
            'root' => __DIR__,                      //start.php启动文件的目录的绝对路径
            'controller' => '/backend/controllers', //项目文件根目录
            'statics' => '/backend/web',            //静态资源根目录
        ]
    ],

    'db' => [
        'HttpServer' => [
            'host'	=>	'127.0.0.1',
            'port'	=>	3306,
            'user'	=>	'root',
            'password'	=>	'123456',
            'dbname'	=>	'HttpServer',
            'charset'	=>	'utf8mb4',
        ],
    ],

    'redis' => [
        'HttpServer' => [
            'host' => '127.0.0.1',
            'port' => '6379',
            'password' => '123456',
            'db' => 1
        ]
    ]
];
```