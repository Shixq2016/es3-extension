<?php

namespace ESL\AutoLoad;

use App\Constant\EnvConst;
use ESL\Constant\EsConst;
use ESL\EsUtility;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Command\Utility;

class Process
{
    use Singleton;

    public function autoLoad()
    {
        try {
            $path = EASYSWOOLE_ROOT . '/' . EsConst::ES_DIRECTORY_APP_NAME . '/' . EsConst::ES_DIRECTORY_MODULE_NAME . '/';
            $modules = EsUtility::sancDir($path);

            foreach ($modules as $module) {

                $processPath = $path . $module . '/' . EsConst::ES_DIRECTORY_PROCESS_NAME . '/';
                $processFiles = EsUtility::sancDir($processPath);

                foreach ($processFiles as $key => $processFile) {

                    $autoLooadFile = $processPath . $processFile;
                    if (!file_exists($autoLooadFile)) {
                        continue;
                    }

                    /** 获取类名 */
                    $className = basename($autoLooadFile, '.php');

                    /** 加载定时任务 */
                    $class = "\\" . EsConst::ES_DIRECTORY_APP_NAME . "\\" . EsConst::ES_DIRECTORY_MODULE_NAME . "\\" . $module . "\\" . EsConst::ES_DIRECTORY_PROCESS_NAME . "\\" . $className;
                    if (class_exists($class)) {

                        $conf = $class::getConf();

                        $processName = EnvConst::SERVICE_NAME . ".{$className}";
                        $processGroup = EnvConst::SERVICE_NAME . ".{$module}";

                        $conf->setProcessName($processName);
                        $conf->setProcessGroup($processGroup);
                        
                        \EasySwoole\Component\Process\Manager::getInstance()->addProcess(new $class($conf));

                        echo  Utility::displayItem('Process',$class);
                        echo "\n";
                    }
                }
            }

        } catch (\Throwable $throwable) {
            echo 'Process Initialize Fail :' . $throwable->getMessage();
        }
    }
}