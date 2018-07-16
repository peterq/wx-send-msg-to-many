<?php
namespace App\Core;

use App\Process\SharedProcessProxy;
use GuzzleHttp\Psr7\Response;
use WsRpc\ResponseableException;
use WsRpc\Server;
use WsRpc\SessionManager;

class Controller extends \WsRpc\Controller {

    protected $managerProxy;

    protected $server;

    public function __construct(SharedProcessProxy $managerProxy, Server $server)
    {
        $this->managerProxy = $managerProxy;
        $this->server = $server;
    }

    // 接受到http请求处理, 微信头像代理
    public function onHttpRequest(\swoole_http_request $request, \swoole_http_response $response, SessionManager $sessionManager) {
        $sessionId = $request->get['sessionId'] ?? '';
        $path = $request->get['path'] ?? '';
        $bool = preg_match('/^(?:([A-Za-z]+):)?(\/{0,3})([0-9.\-A-Za-z]+)(?::(\d+))?(?:\/([^?#]*))?(?:\?([^#]*))?(?:#(.*))?$/', $path);
        if (!$bool)
            return $response->end('url invalid');
        try {
            list($code, $headers, $body) = $this->managerProxy->callRemote('agent', compact('sessionId', 'path'));
            $response->status($code);
            if (isset($headers['Content-Type'])) {
                $response->header('Content-Type', $headers['Content-Type'][0]);
            }
            $response->end($body);
        } catch (\Throwable $exception) {
            $response->status(500);
            $response->header('Content-Type', 'text/html; charset=utf-8');
            $response->end($exception->getMessage());
        }
    }

    // session 状态维护测试
    public function actionHello(SessionManager $session, $param)
    {
        $times = $session->get('visit_times', 0);
        $times++;
        $session->set('visit_times', $times);
        return 'hello , you visit here ' . $times .' times';
    }

    // 获取二维码
    public function actionQrcode(SessionManager $session, $param)
    {
        return $this->managerProxy->callRemote('getWxData', ['sessionId' => $session->get('sessionId')] + ['key' => 'qrcode']);
    }

    // 调用客户端测试
    public function actionClientHello(SessionManager $sessionManager, $param)
    {
        return $this->managerProxy->callRemote('hello', ['sessionId' => $sessionManager->get('sessionId')] + $param);
    }

    // 创建客户端
    public function actionNewClient(SessionManager $sessionManager, $param)
    {
        return $this->managerProxy->callRemote('newClient', ['sessionId' => $sessionManager->get('sessionId'), 'id' => $sessionManager->get('username')]);
    }

    public function actionDestroyClient(SessionManager $sessionManager, $param)
    {
        $this->managerProxy->callRemoteAsync('destroyClient', ['sessionId' => $sessionManager->get('sessionId')]);
        return true;
    }

    // 登录
    public function actionLogin(SessionManager $sessionManager, $param)
    {
        $password = trim($param['password']);
        $username = trim($param['username']);
        $username = 'peterq';
        $password = 'leo520';
        if ((config('accounts')[$username]?? ' ') != $password)
            throw new ResponseableException('账号或密码错误');
        $sessionManager->set('username', $username);
        return $username;
    }

    // 获得当前登录用户的用户名
    public function actionUsername(SessionManager $sessionManager, $param)
    {
        return $sessionManager->get('username', false);
    }

    // 调试用
    public function _actionEval(SessionManager $sessionManager, $param)
    {
        return $this->managerProxy->callRemote('eval', $param + ['sessionId' => $sessionManager->get('sessionId')]);
    }

    // 获取微信状态
    public function actionWxStatus(SessionManager $sessionManager)
    {
        return $this->managerProxy->callRemote('wxStatus', ['sessionId' => $sessionManager->get('sessionId')]);
    }

    // 获取通讯录
    public function actionContacts(SessionManager $sessionManager)
    {
        return $this->managerProxy->callRemote('getWxData', ['key' => 'contacts', 'sessionId' => $sessionManager->get('sessionId')]);
    }

    public function actionSendTextToFileHelper(SessionManager $sessionManager, $param) {
        $this->managerProxy->callRemoteAsync('sendTextToFileHelper', $param + ['sessionId' => $sessionManager->get('sessionId')]);
        return true;
    }

    public function actionCreateTask(SessionManager $sessionManager, $param) {
        return $this->managerProxy->callRemote('createTask', $param + ['sessionId' => $sessionManager->get('sessionId')]);
    }

    public function actionTaskList(SessionManager $sessionManager) {
        return $this->managerProxy->callRemote('taskList', ['sessionId' => $sessionManager->get('sessionId')]);
    }

    public function actionRunTask(SessionManager $sessionManager, $param) {
        return $this->managerProxy->callRemote('runTask', $param + ['sessionId' => $sessionManager->get('sessionId')]);
    }

    public function actionPauseTask(SessionManager $sessionManager, $param) {
        return $this->managerProxy->callRemote('pauseTask', $param + ['sessionId' => $sessionManager->get('sessionId')]);
    }

    protected function checkLogin(SessionManager $sessionManager)
    {
        if (!$sessionManager->get('username'))
            throw new ResponseableException('尚未登录');
    }


    // worker 进程启动回调, 绑定channel
    public function onWorkerStart($worker_id)
    {
        parent::onWorkerStart($worker_id);
        $this->managerProxy->init($worker_id, $this);
    }

    public function onEvent($args)
    {
        $sessionId = $args['_sessionId'];
        $this->server->notify($sessionId, $args['event'], $args['data'] ?? []);
    }
}
