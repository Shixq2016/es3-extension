<?php

namespace ESL;

use App\Constant\AppConst;
use EasySwoole\Component\Di;

/**
 * 配置自动加载
 * Class HttpRouter
 * @package Es3\Autoload
 */
class Trace
{
    /**
     * @return mixed
     * @return null
     * @throws \Throwable
     */
    public static function getRequestId()
    {
        if (!Di::getInstance()->get(AppConst::DI_TRACE_CODE)) {
            Trace::createRequestId();
        }

        return Di::getInstance()->get(AppConst::DI_TRACE_CODE);
    }

    /**
     *
     */
    public static function createRequestId(): void
    {
        Di::getInstance()->set(AppConst::DI_TRACE_CODE, md5(uniqid(microtime(true), true)));
    }
}
