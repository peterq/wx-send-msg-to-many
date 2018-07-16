<?php
namespace App\Core;

use App\Process\IpcProxy;
use App\Process\SharedProcess;
use App\Wechat\Client;
use WsRpc\ResponseableException;

class Manager {

    protected $clientMap = [];

    protected $sharedProcess;

    public function __construct(SharedProcess $sharedProcess)
    {
        $this->sharedProcess = $sharedProcess;
    }

    public function wxStatus($args)
    {
        $sessionId = $args['sessionId'];
        if (!isset($this->clientMap[$sessionId]))
            return 'not-created';
        return $this->getWxData($args + ['key' => 'status']);
    }

    // 开启一个新进程运行微信客户端
    public function newClient($args)
    {
        $sessionId = $args['sessionId'];
        if (isset($this->clientMap[$sessionId])) return $this->fail('该会话已有一个客户端在运行');
        $process = new \swoole_process(function (\swoole_process $process) use ($sessionId, $args) {
            swoole_set_process_name('client for ' . $sessionId);
            new Client($process, config('wx')($args['id']));
        });
        $this->clientMap[$sessionId] = new IpcProxy($process, new class($this) {
            protected $manager;
            public function __construct($manager) {$this->manager = $manager;}
            public function notify($args) {return $this->manager->onEvent($args);}
            public function onWillExit($args) {return $this->manager->onWillExit($args);}
        }, [$sessionId, $args['_channel']]);
        $process->start();
        return true;
    }

    public function __call($name, $arguments)
    {
        if (!method_exists(Client::class, $name))
            throw new ResponseableException('方法不存在');
        if (empty($arguments)) return;
        $args = $arguments[0];
        $sessionId = $args['sessionId'];
        if (!isset($this->clientMap[$sessionId]))
            throw new ResponseableException('没有在运行中的客户端');
        $clientProxy = $this->clientMap[$sessionId];
        $ret = $clientProxy->callRemote($name, $args);
        return $ret;
    }

    public function onWillExit($args)
    {
        list($sessionId, $channelIndex) = $args['_extra'];
        unset($this->clientMap[$sessionId]);
    }

    public function onEvent($args)
    {
        list($sessionId, $channelIndex) = $args['_extra'];
        $args['_sessionId'] = $sessionId;
        $this->sharedProcess->callSiblingAsync($channelIndex, 'onEvent', $args);
    }

}
