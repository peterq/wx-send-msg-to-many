<?php

namespace App\Process;

use App\Core\Cache;

class IpcProxy {

    protected $process;

    protected $callRemoteMap = [];

    protected $target;

    protected $extra;

    public function __construct(\swoole_process $process, $target, $extra = '')
    {
        $this->process = $process;
        $this->target = $target;
        $this->extra = $extra;
        $this->init();
    }

    protected function init()
    {
        swoole_event_add($this->process->pipe, function ($pipe) {
            \swoole_coroutine::create(function ()  {
                $data = $this->process->read();
                $data = $this->unserialize($data);
                $type = $data['type'];
                if ($type == 'call') {
                    try {
                        $args = $data['args'] ?? [];
                        $args['_extra'] = $this->extra;
                        $result = $this->target->{$data['func']}($args);
                        $this->process->write($this->serialize([
                            'result' => $result,
                            'type' => 'call_return',
                            'call_id' => $data['call_id']
                        ]));
                    } catch (\Throwable $exception) {
                        $this->process->write($this->serialize([
                            'error' => $exception->getMessage(),
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
                if ($type == 'exit') exit(0);
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
        $this->process->write($this->serialize([
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
        if ($error) throw $error;
        return $result;
    }

    // 可以不在协程中调用
    public function callRemoteAsync($func, $args = [])
    {
        $call_id = \str_random();
        return $this->process->write($this->serialize([
            'type' => 'call',
            'func' => $func,
            'args' => $args,
            'call_id' => $call_id
        ]));
    }

    protected function serialize($data)
    {
        $str = serialize($data);
        if (strlen($str) > 1024) {
            $cache_key = \str_random();
            Cache::getInstance()->forever($cache_key, $str);
            $str = serialize([
                'type' => 'cache',
                'cache_key' => $cache_key,
            ]);
        }
        return $str;
    }

    protected function unserialize($data)
    {
        $data = unserialize($data);
        if ($data['type'] == 'cache') {
            $str = Cache::getInstance()->get($data['cache_key']);
            Cache::getInstance()->del($data['cache_key']);
            $data = unserialize($str);
        }
        return $data;
    }
}
