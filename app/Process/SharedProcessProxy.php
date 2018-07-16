<?php

namespace App\Process;

use App\Cache;
use Swoole\Process;
use WsRpc\ResponseableException;

class SharedProcessProxy {

    protected $sharedProcess;

    protected $toTarget;

    protected $fromTarget;

    protected $callRemoteMap = [];

    public function __construct(SharedProcess $sharedProcess)
    {
        $this->sharedProcess = $sharedProcess;
    }

    // 在兄弟进程中执行, 用来绑定channel
    public function init($number, $handler)
    {
        static $inited = false;
        if ($inited) return false;
        $inited = true;
        list($toTarget, $fromTarget) = $this->sharedProcess->getChannelByIndex($number);
        $this->toTarget = $toTarget;
        $this->fromTarget = $fromTarget;
        swoole_timer_tick(1, function () use ($handler) {
            go(function () use ($handler) {
                while ($string = $this->fromTarget->pop()) {
                    $data = unserialize($string);
                    $type = $data['type'];
                    if ($type == 'call') {
                        try {
                            $result = $handler->{$data['func']}($data['args'] ?? []);
                            $this->toTarget->push(serialize([
                                'result' => $result,
                                'type' => 'call_return',
                                'call_id' => $data['call_id']
                            ]));
                        } catch (\Throwable $exception) {
                            $this->toTarget->push(serialize([
                                'error' => $exception,
                                'type' => 'call_error',
                                'call_id' => $data['call_id']
                            ]));
                        }
                    }
                    if ($type == 'call_return') {
                        $this->onResult($data);
                    }
                    if ($type == 'call_error') {
                        $this->onError($data);
                    }
                }
            });
        });
    }

    protected function onResult($data)
    {
        $call_id = $data['call_id'];
        if (!isset($this->callRemoteMap[$call_id])) return;
        $this->callRemoteMap[$call_id]['result'] = $data['result'];
        \swoole_coroutine::resume($this->callRemoteMap[$call_id]['co_id']);
    }

    public function onError($data)
    {
        $call_id = $data['call_id'];
        if (!isset($this->callRemoteMap[$call_id])) return;
        $this->callRemoteMap[$call_id]['error'] = $data['error'];
        \swoole_coroutine::resume($this->callRemoteMap[$call_id]['co_id']);
    }

    // 必须在协程环境下调用
    public function callRemote($func, $args = [])
    {
        $call_id = \str_random();
        $co_id = \swoole_coroutine::getuid();
        $this->toTarget->push(serialize([
            'type' => 'call',
            'func' => $func,
            'args' => $args,
            'call_id' => $call_id
        ]));
        $this->callRemoteMap[$call_id] = compact('co_id');
        \swoole_coroutine::suspend($co_id);
        $error = $this->callRemoteMap[$call_id]['error'] ?? null;
        $result = $this->callRemoteMap[$call_id]['result'] ?? null;
        unset($this->callRemoteMap[$call_id]);
        if ($error instanceof \Throwable) throw $error;
        else if ($error) throw new ResponseableException($error);
        return $result;
    }

    // 可以不在协程中调用
    public function callRemoteAsync($func, $args = [])
    {
        $call_id = \str_random();
        return $this->toTarget->push(serialize([
            'type' => 'call',
            'func' => $func,
            'args' => $args,
            'call_id' => $call_id
        ]));
    }

}
