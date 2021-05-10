<?php

namespace ESL;

use App\Constant\AppConst;
use App\Constant\EnvConst;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\Command\Utility;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\SysConst;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Log\LoggerInterface;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;
use EasySwoole\Pool\Exception\Exception;
use EasySwoole\Template\Render;
use ESL\AutoLoad\Config;
use ESL\AutoLoad\Crontab;
use ESL\AutoLoad\Process;
use ESL\AutoLoad\Queue;
use ESL\AutoLoad\Router;
use ESL\AutoLoad\Event;
use ESL\Exception\ErrorException;
use ESL\Handle\HttpThrowable;
use ESL\Output\Result;
use ESL\Template\Smarty;


class EasySwooleEvent
{
    /**
     * 全局初始化
     */
    public function initialize(): void
    {
        /** 设置时区 */
        date_default_timezone_set('Asia/Shanghai');

        /** 设置精度 */
        ini_set('serialize_precision', 14);

        /** 加载配置文件 */
        Config::getInstance()->autoLoad();

        /** 路由初始化 */
        Router::getInstance()->autoLoad();

        /** 配置控制器命名空间 */
        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_NAMESPACE, 'App\\Controller\\');

        /** 注入http异常处理 */
        Di::getInstance()->set(SysConst::HTTP_EXCEPTION_HANDLER, [HttpThrowable::class, 'run']);

        /** 事件注册 */
        Event::getInstance()->autoLoad();

        /** 目录不存在就创建 */
        is_dir(strtolower(EnvConst::PATH_LOG)) ? null : mkdir(strtolower(EnvConst::PATH_LOG), 0777, true);
        is_dir(strtolower(EnvConst::PATH_TEMP)) ? null : mkdir(strtolower(EnvConst::PATH_TEMP), 0777, true);
        is_dir(strtolower(EnvConst::PATH_LOCK)) ? null : mkdir(strtolower(EnvConst::PATH_LOCK), 0777, true);

        /** ORM  */
        $mysqlConf = config('mysql', true);
        if (!superEmpty($mysqlConf)) {
            echo Utility::displayItem('MysqlConf', json_encode($mysqlConf));
            echo "\n";
            $config = new \EasySwoole\ORM\Db\Config($mysqlConf);
            DbManager::getInstance()->addConnection(new Connection($config));
            DbManager::getInstance()->onQuery(function ($res, $builder, $start) {

                $nowDate = date('Y-m-d H:i:s', time());
                if (!isProduction()) {
                    /** 打印日志 */
                    echo "\n====================  {$nowDate} ====================\n";
                    echo $builder->getLastQuery() . "\n";
                    echo "==================== {$nowDate} ====================\n";
                }
                Logger::getInstance()->log($builder->getLastQuery(), LoggerInterface::LOG_LEVEL_INFO, 'query');
            });
        }
    }

    public function mainServerCreate(EventRegister $register): void
    {
        /** 初始化定时任务 */
        Crontab::getInstance()->autoLoad();

        /** 初始化自定义进程 */
        Process::getInstance()->autoLoad();

        /** 策略加载 */
        Di::getInstance()->set(AppConst::DI_POLICY, Policy::getInstance()->initialize());

        /** smarty */
        Render::getInstance()->getConfig()->setRender(new Smarty());
        Render::getInstance()->getConfig()->setTempDir(EASYSWOOLE_TEMP_DIR);
        Render::getInstance()->attachServer(ServerManager::getInstance()->getSwooleServer());

        /** 热加载 */
        if (isDev()) {
            $hotReloadOptions = new \EasySwoole\HotReload\HotReloadOptions;
            $hotReload = new \EasySwoole\HotReload\HotReload($hotReloadOptions);
            $hotReloadOptions->setMonitorFolder([EASYSWOOLE_ROOT . '/App']);

            $server = ServerManager::getInstance()->getSwooleServer();
            $hotReload->attachToServer($server);
        }

        /** 连接redis */
        $redisConf = config('redis', true);
        if (superEmpty(!$redisConf)) {
            try {
                echo Utility::displayItem('RedisConf', json_encode($redisConf));
                echo "\n";

                $redisConf = new \EasySwoole\Redis\Config\RedisConfig($redisConf);
                \EasySwoole\RedisPool\Redis::getInstance()->register(EnvConst::REDIS_KEY, $redisConf);
            } catch (Exception $e) {
                throw new ErrorException(1002, 'redis连接失败');
            }

            /** 初始化消息队列 */
            Queue::getInstance()->autoLoad();
        }
    }

    public static function onRequest(Request $request, Response $response)
    {
        Di::getInstance()->set(AppConst::DI_RESULT, Result::class);
        Di::getInstance()->set(AppConst::DI_REQUEST, $request);
        Di::getInstance()->set(AppConst::DI_RESPONSE, $response);

        /** 请求唯一标识  */
        Trace::createRequestId();

        /** 中间件 */
        Middleware::onRequest($request, $response);
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        /** 中间件 */
        Middleware::afterRequest($request, $response);
    }
}