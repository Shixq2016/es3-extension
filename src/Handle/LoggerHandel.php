<?php

namespace ESL\Handle;

use ESL\Utility\File;
use ESL\Trace;
use App\Constant\EnvConst;
use EasySwoole\Log\LoggerInterface;

class LoggerHandel implements LoggerInterface
{
    private $logDir;

    function __construct(string $logDir = null)
    {
        if (empty($logDir)) {
            $logDir = getcwd();
        }
        $this->logDir = strtolower($logDir);
    }

    function log(?string $msg, int $logLevel = self::LOG_LEVEL_INFO, string $category = 'debug'): string
    {
        $date = date('Y-m-d H:i:s');
        $category = strtolower($category);
        $project = strtolower(EnvConst::SERVICE_NAME);
        $levelStr = strtolower($this->levelMap($logLevel));
        $logPath = "{$this->logDir}/{$category}/{$levelStr}";

        clearstatcache();
        is_dir($logPath) ? null : File::createDirectory($logPath, 0777);

        $fileDate = date('Ymd', time());
        $filePath = "{$logPath}/{$fileDate}.log";

        /** 是否传递分类的特殊处理 */
        $traceCode = Trace::getRequestId();
        $str = "[{$project}][{$date}][{$traceCode}][{$category}][{$levelStr}] : [{$msg}]\n";

        file_put_contents($filePath, "{$str}", FILE_APPEND | LOCK_EX);
        return $str;
    }

    function console(?string $msg, int $logLevel = self::LOG_LEVEL_INFO, string $category = 'console')
    {
        $date = date('Y-m-d H:i:s');
        $levelStr = $this->levelMap($logLevel);
        $temp = "[{$date}][{$category}][{$levelStr}]:[{$msg}]\n";
        fwrite(STDOUT, $temp);
    }

    private function levelMap(int $level): string
    {
        switch ($level) {
            case self::LOG_LEVEL_INFO:
                return 'info';
            case self::LOG_LEVEL_NOTICE:
                return 'notice';
            case self::LOG_LEVEL_WARNING:
                return 'warning';
            case self::LOG_LEVEL_ERROR:
                return 'error';
            default:
                return 'unknown';
        }
    }
}