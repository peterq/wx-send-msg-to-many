<?php

namespace App\Wechat;

use Hanson\Vbot\Message\Image;
use Hanson\Vbot\Message\Text;
use Hanson\Vbot\Message\Voice;

class Task implements \JsonSerializable
{

    protected $client;

    protected $messages = [];

    protected $receivers;

    protected $waiting;

    protected $sent = [];

    protected $failed = [];

    protected $paused = true;

    protected $taskId;

    public function __construct($taskId, Client $client, $messages, $receivers)
    {
        $this->taskId = $taskId;
        $this->client = $client;
        $this->receivers = $receivers;
        $this->waiting = new \SplQueue();
        foreach ($receivers as $receiver)
            $this->waiting->push($receiver);
        $messagesInServer = $this->client->getMsgHandler()->getMessages();
        foreach ($messages as $message) {
            $this->messages[] = isset($message['uid']) ? $messagesInServer[$message['uid']] : $message;
        }
        $client->notify('task.created', $this->toArray());
    }


    public function run($recursive = false)
    {
        if ($this->waiting->isEmpty())
            return;
        if (!$this->paused && !$recursive)
            return;
        $this->paused = false;
        swoole_timer_after(1, function () {
            go(function () {
                try {
                    $this->sendNext($receiver = $this->waiting->dequeue());
                } catch (\Throwable $exception) {
                    $this->failed[] = $receiver;
                    $this->client->notify('sending.error.' . $this->taskId, $exception->getMessage() . $exception->getTraceAsString());
                }
                if (!$this->waiting->isEmpty())
                    $this->run(true);
            });
        });
    }

    protected function sendNext($receiver)
    {
        $friends = $this->client->getWxData(['key' => 'contacts'])['friends'];
        if (!isset($friends[$receiver])) return;
        $receiver = $friends[$receiver];
        $this->client->notify('task.sending', [
            'taskId' => $this->taskId,
            'username' => $receiver['UserName'],
            'successCount' => count($this->sent),
            'failCount' => count($this->failed)
        ]);
        foreach ($this->messages as $message) {
            if (in_array($message['type'], ['raw-text', 'text'])) {
                $map = [
                    'remark' => $receiver['RemarkName'] ? $receiver['RemarkName'] : $receiver['NickName'],
                    'nickname' => $receiver['NickName'],
                ];
                foreach ($map as $key => $value) {
                    $message['content'] = str_replace(':' . $key, $value, $message['content']);
                }
                Text::send($receiver['UserName'], $message['content']);
            } else {

                $cls = 'Hanson\Vbot\Message\\' . ucfirst($message['type']);
                if (!class_exists($cls)) continue;
                if (method_exists($cls, 'download')) {
                    call_user_func([$cls, 'download'], $message);
                }
                method_exists($cls, 'send') and call_user_func([$cls, 'send'], $receiver['UserName'], $message);
            }
        }
        $this->sent[] = $receiver;
        $this->client->notify('task.sent', [
            'taskId' => $this->taskId,
            'username' => $receiver['UserName'],
            'successCount' => count($this->sent),
            'failCount' => count($this->failed)
        ]);
    }

    public function pause()
    {
        $this->paused = true;
    }

    public function toArray() {
        return [
            'taskId' => $this->taskId,
            'paused' => $this->paused,
            'receivers' => $this->receivers,
            'messages'  => $this->messages,
            'successCount' => count($this->sent),
            'failCount' => count($this->failed),
        ];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
