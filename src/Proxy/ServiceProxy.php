<?php

namespace ESL\Proxy;

use ESL\EsUtility;
use ESL\Constant\EsConst;
use EasySwoole\EasySwoole\Logger;

class ServiceProxy
{
    protected $service;

    function __construct($namespace)
    {
        $className = EsUtility::getControllerClassName($namespace);
        $moduleName = EsUtility::getControllerModuleName($namespace);

        $moduleDirName = EsConst::ES_DIRECTORY_MODULE_NAME;
        $namespace = "App\\{$moduleDirName}\\{$moduleName}\\Service\\{$className}Service";

        if ($moduleName == EsConst::ES_DIRECTORY_CONTROLLER_NAME) {
            return;
        }

        if (class_exists($namespace) && $moduleDirName != 'Controller') {
            $this->service = new $namespace();
        } else {
            if (!isProduction()) {
                $msg = 'service 加载失败 : ' . $namespace;
                Logger::getInstance()->console($msg, 3, 'proxy');
            }
        }
    }

    public function getService()
    {
        return $this->service;
    }
}
