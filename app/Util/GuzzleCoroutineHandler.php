<?php

namespace App\Util;

use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;
use Swoole\Coroutine\Http\Client;
use function GuzzleHttp\Promise\promise_for;


class GuzzleCoroutineHandler
{

    /**
     * Sends an HTTP request.
     *
     * @param RequestInterface $request Request to send.
     * @param array $options Request transfer options.
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $uri = Psr7\uri_for($request->getUri());
        $ssl = $uri->getScheme() === 'https';
        $port = $uri->getPort() ? $uri->getPort() : ($ssl ? 443 : 80);

        // 转换 header 格式
        $headers = $request->getHeaders();
        foreach ($headers as $k => $v) {
            $headers[$k] = $v[0] ?? '';
        }
        $client = new Client($uri->getHost(), $port, $ssl);
        $client->set(['timeout' => $options['timeout'] ?? 10]);
        $client->setMethod($request->getMethod());
        $client->setHeaders($headers);
        $client->setData($request->getBody()->getContents());
        $client->execute($request->getRequestTarget());


        $headers = [];
        foreach ($client->headers as $k => $v) {
            $words = explode('-', $k);
            $words = array_map('ucfirst', $words);
            $headers[implode('-', $words)] = [$v];
        }
        if (!empty($client->set_cookie_headers)) {
            $headers['Set-Cookie'] = array_values($client->set_cookie_headers);
        }

        return promise_for(new Psr7\Response(
            $client->statusCode,
            $headers,
            $client->body
        ));
    }


    protected function sendByGuzzle(RequestInterface $request, array $options)
    {
        $handler = new StreamHandler();
        return $handler($request, $options);
    }
}
