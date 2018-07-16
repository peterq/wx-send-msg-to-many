<?php

include 'vendor/autoload.php';

function println($msg)
{
    echo  '---------' . posix_getpid() . '---------' . PHP_EOL . $msg . PHP_EOL;
}

class Foo {
    public function hello($args)
    {
        return 'hello ' . $args['name'];
    }
}
class Bar {
    public function hello($args)
    {
        return 'hello ' . $args['name'];
    }
}

$shared = function () {
    return new Foo();
};

$sibling = function (\App\Process\SharedProcessProxy $proxy, $number) {
    swoole_coroutine::create(function () use ($proxy, $number) {
        $proxy->init($number, new Bar());$time = microtime(1);
        foreach (range(0, 10) as $i) {

            println($proxy->callRemote('hello', ['name' => '#' . $i]));

        }println(microtime(1) - $time);
    });
};

function start($shared, $sibling) {
    $sharedProcess = new \App\Process\SharedProcess($shared);
    list($process, $proxy) = $sharedProcess->createTargetProcess();

    $serv = new swoole_server('0.0.0.0', 9501, SWOOLE_BASE, SWOOLE_SOCK_TCP);
    $serv->set(array(
        'worker_num' => 4,
        'daemonize' => false,
        'backlog' => 128,
    ));
    $serv->addProcess($process);

    $serv->on('Receive', function () {

    });

    $serv->on('WorkerStart', function ($serv, $worker_id) use ($proxy, $sibling) {
        $sibling($proxy, $worker_id);
    });

    $serv->start();
};
// start($shared, $sibling);


