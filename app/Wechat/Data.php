<?php

namespace App\Wechat;

class Data implements \ArrayAccess {

    protected $client;

    protected $data = [
        'status' => 'waiting-init',
        'qrcode' => '',
        'contacts' => []
    ];

    protected $notifyIgnore = ['_contacts'];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
        if (!in_array($offset, $this->notifyIgnore))
            $this->client->notify('wx.data.change', ['key' => $offset, 'value' => $value]);
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
