<?php

namespace ESL\AutoLoad;

use ESL\Constant\EsConst;
use ESL\EsUtility;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Command\Utility;

class Crontab
{
    use Singleton;

    public function autoLoad()
    {
        try {
            $path = EASYSWOOLE_ROOT . '/' . EsConst::ES_DIRECTORY_APP_NAME . '/' . EsConst::ES_DIRECTORY_MODULE_NAME . '/';
            $modules = EsUtility::sancDir($path);

            foreach ($modules as $module) {

                $crontabPath = $path . $module . '/' . EsConst::ES_DIRECTORY_CRONTAB_NAME . '/';
                $crontabFiles = EsUtility::sancDir($crontabPath);

                foreach ($crontabFiles as $key => $crontabFile) {

                    $autoLooadFile = $crontabPath . $crontabFile;
                    if (!file_exists($autoLooadFile)) {
                        continue;
                    }

                    /** 获取类名 */
                    $className = basename($autoLooadFile, '.php');

                    /** 加载定时任务 */
                    $class = "\\" . EsConst::ES_DIRECTORY_APP_NAME . "\\" . EsConst::ES_DIRECTORY_MODULE_NAME . "\\" . $module . "\\" . EsConst::ES_DIRECTORY_CRONTAB_NAME . "\\" . $className;

                    if (class_exists($class)) {
                        \EasySwoole\EasySwoole\Crontab\Crontab::getInstance()->addTask($class);
                        echo Utility::displayItem('Crontab', $class);
                        echo "\n";
                    }
                }
            }

        } catch (\Throwable $throwable) {
            echo 'Crontab Initialize Fail :' . $throwable->getMessage();
        }
    }
}
