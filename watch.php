<?php

$pid = null;
// sleep(99999);
function startApp() {
    global $pid;
    $p = new swoole_process(function ($p) {
        $p->exec('/usr/local/bin/php', ['run.php']);
    });
    $pid = $p->start();
}


// 子进程退出回收
swoole_process::signal(SIGCHLD, function ($sig) {
    global $pid;
    // 必须为false，非阻塞模式
    while ($ret = swoole_process::wait(false)) {
       if ($pid == $ret['pid']) {
           echo "进程已退出:$pid\n正在重启...\n";
           sleep(1);
           startApp();
       }
    }
});

startApp();

new Watcher(__DIR__, function () {
    global $pid;
    static $lasttime = 0;
    if (time() - $lasttime < 3) return;
    $lasttime = time();
    echo 'modified' . PHP_EOL;
    if ($pid) {
        swoole_process::kill($pid);
    }
    echo date('H:i:s') . ' 已发送kill信号' . PHP_EOL;
}, ['/temp', '/.idea', '/public']);

class Watcher
{

    protected $cb;

    protected $modified = false;

    protected $minTime = 3000;

    protected $ignored = [];

    public function __construct($dir, $cb, $ignore = [])
    {
        $this->cb = $cb;
        foreach ($ignore as $item) {
            $this->ignored[] = $dir . $item;
        }
        $this->watch($dir);
    }

    protected function watch($directory)
    {
        //创建一个inotify句柄
        $fd = inotify_init();

        //监听文件，仅监听修改操作，如果想要监听所有事件可以使用IN_ALL_EVENTS
        inotify_add_watch($fd, __DIR__, IN_MODIFY);
        foreach ($this->getAllDirs($directory) as $dir) {
            inotify_add_watch($fd, $dir, IN_MODIFY);
        }
        echo 'watch start' . PHP_EOL;
        //加入到swoole的事件循环中
        swoole_event_add($fd, function ($fd) {
            $events = inotify_read($fd);
            if ($events) {
                $this->modified = true;
            }
        });

        swoole_timer_tick($this->minTime, function () {
            if ($this->modified) {
                ($this->cb)();
                $this->modified = false;
            }
        });
    }

    protected function getAllDirs($base)
    {
        $files = scandir($base);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            $filename = $base . DIRECTORY_SEPARATOR . $file;
            if (in_array($filename, $this->ignored)) continue;
            if (is_dir($filename)) {
                yield $filename;
                yield from $this->getAllDirs($filename);
            }
        }

    }
}
