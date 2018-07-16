<?php

namespace App\Wechat;

use App\Process\IpcProxy;
use Hanson\Vbot\Foundation\Vbot;
use Hanson\Vbot\Message\Text;
use WsRpc\ResponseableException;

class Client {

    protected $managerProxy;

    protected $started = false;

    protected $config;
    /**
     * @var Vbot
     */
    protected $vbot;

    protected $msgHandler;

    protected $wxData;

    protected $taskMap;

    public function __construct(\swoole_process $process, $config)
    {
        $this->wxData = new Data($this);
        $this->managerProxy = new IpcProxy($process, $this);
        $this->notify('client.created');
        $this->init($config);
    }

    public function eval($args)
    {
        try {
            return eval($args['code']);
        } catch (\Throwable $exception) {
            dump($exception);
            return false;
        }
    }

    public function agent($args) {
        $resp =  $this->vbot->http->getClient()->get($args['path']);
        return [$resp->getStatusCode(), $resp->getHeaders(), $resp->getBody()->getContents()];
    }

    protected function init($config)
    {
        register_shutdown_function(function () {
            $this->notify('client.exit');
            $this->managerProxy->callRemoteAsync('onWillExit');
        });
        $this->wxData['status'] = 'login';
        $this->config = $config;
        $this->vbot = $vbot = new Vbot($this->config);
        $this->msgHandler = new MessageHandler($this);
        $vbot->observer->setQrCodeObserver(function($qrCodeUrl) {
            $qrCodeUrl = str_replace('https://login.weixin.qq.com/', 'https://login.weixin.qq.com/qrcode/', $qrCodeUrl);
            println('二维码', $qrCodeUrl);
            $this->wxData['status'] = 'waiting-scan';
            $this->wxData['qrcode'] = $qrCodeUrl;
        });
        $vbot->messageHandler->setHandler([$this->msgHandler, 'handle']);
        $vbot->observer->setLoginSuccessObserver(function() {
            $this->wxData['status'] = 'login_success';
        });
        $vbot->observer->setReLoginSuccessObserver(function(){
            $this->wxData['status'] = 're_login';
        });
        $vbot->observer->setExitObserver(function(){
            $this->notify('client.exit');
            $this->managerProxy->callRemote('onWillExit');
            println('wx client exit');
            exit(0);
        });
        $vbot->observer->setFetchContactObserver(function(array $contacts){
            $this->wxData['_contacts'] = $contacts;
            $arr = [];
            foreach ($contacts as $k => $v) {
                $arr[$k] = $v->toArray();
            }
            $this->wxData['status'] = 'ok';
            $this->wxData['contacts'] = $arr;
        });
        go(function () use ($vbot) {
            $vbot->server->serve();
        });
    }

    public function hello($args)
    {
        $args['msg'] = 'hello world';
        throw new \Exception('im not happy');
        throw new ResponseableException('are you ok?');
        return $args;
    }

    public function getWxData($args)
    {
        return $this->wxData[$args['key']];
    }

    public function createTask($args)
    {
        $taskId = uniqid('task_');
        $task = new Task($taskId, $this, $args['messages'], $args['receivers']);
        $this->taskMap[$taskId] = $task;
        return true;
    }

    public function taskList()
    {
        return json_decode(json_encode($this->taskMap));
    }

    public function runTask($args)
    {
        if (!isset($this->taskMap[$args['taskId']]))
            throw new ResponseableException('任务不存在');
        $this->taskMap[$args['taskId']]->run();
        return true;
    }

    public function pauseTask($args)
    {
        if (!isset($this->taskMap[$args['taskId']]))
            throw new ResponseableException('任务不存在');
        $this->taskMap[$args['taskId']]->pause();
        return true;
    }

    public function exit()
    {
        $this->notify('client.exit');
        $this->managerProxy->callRemote('onWillExit');
        println('wx client exit');
        exit(0);
    }

    /**
     * @return mixed
     */
    public function getMsgHandler()
    {
        return $this->msgHandler;
    }

    public function sendTextToFileHelper($args)
    {
        Text::send('filehelper', $args['text']);
    }

    public function notify($event, $data = [])
    {
        return $this->managerProxy->callRemoteAsync('notify', compact('event', 'data'));
    }

}
