<?php

namespace ESL\AutoLoad;

use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Command\Utility;

/**
 * 配置自动加载
 * Class HttpRouter
 * @package ESL\Autoload
 */
class Config
{
    use Singleton;

    /**
     * 自动加载配置文件
     */
    public function autoLoad()
    {
        try {
            $instance = \EasySwoole\EasySwoole\Config::getInstance();
            $path = EASYSWOOLE_ROOT . '/' . \ESL\Constant\EsConst::ES_DIRECTORY_CONF_NAME . '/';
            $files = scandir($path) ?? [];

            foreach ($files as $file) {

                $routerFile = $path . $file;
                if (!file_exists($routerFile) || ($file == '.' || $file == '..')) {
                    continue;
                }

                $data = require_once $routerFile ?? [];
                foreach ($data as $key => $conf) {
                    $instance->setConf(strtolower(basename($file, '.php')), (array)$data);
                }

                echo Utility::displayItem('Config', "{$path}{$file}");
                echo "\n";
            }
        } catch (\Throwable $throwable) {
            echo 'Config Initialize Fail :' . $throwable->getMessage();
        }
    }
}
