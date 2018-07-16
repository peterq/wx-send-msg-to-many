<?php

namespace App\Process;

use App\Cache;

class SharedProcess {

    protected $executor;

    protected $toTargetChannels;

    protected $toSiblingChannels;

    protected $isInTargetProcess = false;

    protected $callRemoteMap = [];

    public function __construct(callable $executor, $channelNumber = 4)
    {
        // 保存回调
        $this->executor = $executor;
        // 创建channel
        for ($i = 0; $i < $channelNumber; $i++) {
            $this->toTargetChannels[] = new \swoole_channel(1024 * 1024);
            $this->toSiblingChannels[] = new \swoole_channel(1024 * 1024);
        }
    }

    // 在master中执行, 创建目标进程(不启动)
    public function createTargetProcess()
    {
        $process = new \swoole_process(function (\swoole_process $process) {
            $this->isInTargetProcess = true;
            $object = ($this->executor)($this);
            swoole_timer_tick(1, function () use ($object) {
                $this->tryReadChannels($object);
            });
        });
        return [$process, new SharedProcessProxy($this)];
    }

    public function getChannelByIndex($index)
    {
        return [$this->toTargetChannels[$index], $this->toSiblingChannels[$index]];
    }

    // 目标进程检查是否有可读通道
    protected function tryReadChannels($handler)
    {
        foreach ($this->toTargetChannels as $index => $channel) {
            while ($string = $channel->pop()) {
                $data = unserialize($string);
                $type = $data['type'];
                if ($type == 'call') {
                    try {
                        $args = $data['args'] ?? [];
                        $args['_channel'] = $index;
                        $result = $handler->{$data['func']}($args);
                        $this->toSiblingChannels[$index]->push(serialize([
                            'result' => $result,
                            'type' => 'call_return',
                            'call_id' => $data['call_id']
                        ]));
                    } catch (\Throwable $exception) {
                        $this->toSiblingChannels[$index]->push(serialize([
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
            }
        }
    }

    protected function onResult($data)
    {
        $call_id = $data['call_id'];
        if (!isset($this->callWatcherMap[$call_id])) return;
        $this->callWatcherMap[$call_id]['result'] = $data['result'];
        \swoole_coroutine::resume($this->callWatcherMap[$call_id]['co_id']);
    }

    public function onError($data)
    {
        $call_id = $data['call_id'];
        if (!isset($this->callRemoteMap[$call_id])) return;
        $this->callRemoteMap[$call_id]['error'] = $data['error'];
        \swoole_coroutine::resume($this->callRemoteMap[$call_id]['co_id']);
    }

    public function callSiblingAsync($index, $func, $args)
    {
        $this->toSiblingChannels[$index]->push(serialize([
            'type' => 'call',
            'func' => $func,
            'args' => $args,
            'call_id' => 'ignore'
        ]));
    }

}
