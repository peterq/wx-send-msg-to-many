<?php

require __DIR__ . '/../vendor/autoload.php';

$client = new \GuzzleHttp\Client(['handler' => function ($request, array $options) {
    $handler = new \App\Util\GuzzleCoroutineHandler();
    return $handler($request, $options);
}]);

go(function () use ($client) {
    $resp = $client->get('http://api.jiebei.local/sleep.php');
//    $resp = $client->request('GET', 'https://wx.qq.com/');
    dump($resp->getBody()->getContents());
    dump('result above');
});
swoole_timer_tick(1000, function () {
   dump('hello');
});