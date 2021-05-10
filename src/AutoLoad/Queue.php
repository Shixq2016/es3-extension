<?php

namespace ESL\AutoLoad;

use App\Constant\EnvConst;
use ESL\Constant\EsConst;
use ESL\EsUtility;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Command\Utility;

class Queue
{
    use Singleton;

    public function autoLoad()
    {
        try {
            $path = EASYSWOOLE_ROOT . '/' . EsConst::ES_DIRECTORY_APP_NAME . '/' . EsConst::ES_DIRECTORY_MODULE_NAME . '/';
            $modules = EsUtility::sancDir($path);

            foreach ($modules as $module) {

                $queuePath = $path . $module . '/' . EsConst::ES_DIRECTORY_QUEUE_NAME . '/';
                $queueFiles = EsUtility::sancDir($queuePath);

                foreach ($queueFiles as $key => $processFile) {

                    $autoLoadFile = $queuePath . $processFile;
                    if (!file_exists($autoLoadFile)) {
                        continue;
                    }

                    /** 获取类名 */
                    $className = basename($autoLoadFile, '.php');

                    /** 加载定时任务 */
                    $class = "\\" . EsConst::ES_DIRECTORY_APP_NAME . "\\" . EsConst::ES_DIRECTORY_MODULE_NAME . "\\" . $module . "\\" . EsConst::ES_DIRECTORY_QUEUE_NAME . "\\" . $className;

                    if (class_exists($class)) {

                        $queueName = $className . '_' . $module;
                        $redisPool = \EasySwoole\RedisPool\Redis::getInstance()->get(EnvConst::REDIS_KEY);
                        $class::getInstance(new \EasySwoole\Queue\Driver\Redis($redisPool, $queueName));

                        echo Utility::displayItem('Queue', $class);
                        echo "\n";
                    }
                }
            }

        } catch (\Throwable $throwable) {
            echo 'Process Initialize Fail :' . $throwable->getMessage();
        }
    }
}
