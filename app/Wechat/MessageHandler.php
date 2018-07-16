<?php

namespace App\Wechat;

use Hanson\Vbot\Message\Text;
use Illuminate\Support\Collection;

class MessageHandler {

    protected $client;

    protected $messages = [];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function handle(Collection $message)
    {
        if ($message['username'] != 'filehelper') return;
        $cls = 'Hanson\Vbot\Message\\' . ucfirst($message['type']);
        $uid = uniqid('message_');
        $message['uid'] = $uid;
        if (method_exists($cls, 'send')) {
            $message['sendAble'] = true;
            $this->messages[$uid] = $message;
        }
        $this->client->notify('wx.filehelper.message', $message);
    }

    public function getMessages()
    {
        return $this->messages;
    }
}
