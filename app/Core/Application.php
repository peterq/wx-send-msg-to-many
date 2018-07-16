<?php
namespace App\Core;

use App\Process\SharedProcess;
use WsRpc\Server;

class Application {

    /**
     * @var static
     */
    protected static $instance = null;

    /**
     * @var Server;
     */
    protected $server;


    protected function __construct() {
        config(require realpath('.') . '/' . 'config.php');
    }

    public function start()
    {
        static $started = false;
        if ($started) return;
        $started = true;
        $this->server = $server = new Server(config('rpc'));
        // 创建管理进程
        $sharedProcess = new SharedProcess(function (SharedProcess $sharedProcess) {
            swoole_set_process_name(config('app-name') . ' clients manager process');
            return new Manager($sharedProcess);
        }, config('rpc.ws-server.worker_num'));
        list($process, $proxy) = $sharedProcess->createTargetProcess();
        // 创建controller
        $controller = new Controller($proxy, $server);
        $server->addController('default', $controller);
        $server->setCb('http-request', [$controller, 'onHttpRequest']);
        // 添加管理进程到swoole服务器
        $server->getRealServer()->addProcess($process);
        // 启动服务器
        $server->bootstrap();
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance))
            static::$instance = new static();
        return static::$instance;
    }
}
