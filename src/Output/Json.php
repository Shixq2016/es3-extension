<?php

namespace ESL\Output;

use App\Constant\AppConst;
use App\Constant\LoggerConst;
use App\Constant\ResultConst;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Log\LoggerInterface;

class Json
{
    /**
     * HTTP 返回成功
     * @param int $code
     * @param string $msg
     * @throws \Throwable
     */
    public static function success(int $code = ResultConst::SUCCESS_CODE, string $msg = ResultConst::SUCCESS_MSG): void
    {
        Di::getInstance()->get(AppConst::DI_RESULT)->setTrace(debug_backtrace());
        Json::setBody($code, $msg, true);
    }

    /**
     * HTTP 返回失败
     * @param \Throwable $throwable
     * @param int $code
     * @param string $msg
     * @throws \Throwable
     */
    public static function fail(\Throwable $throwable, int $code = ResultConst::FAIL_CODE, string $msg = ResultConst::FAIL_MSG): void
    {
        Di::getInstance()->get(AppConst::DI_RESULT)->setTrace($throwable->getTrace());
        Json::setBody($code, $msg, false);
    }

    /**
     * HTTP 返回结构
     * @param int $code
     * @param string $msg
     * @param bool $isSuccess
     * @throws \Throwable
     */
    private static function setBody(int $code, string $msg = '', bool $isSuccess): void
    {
        $response = Di::getInstance()->get(AppConst::DI_RESPONSE);
        $result = Di::getInstance()->get(AppConst::DI_RESULT);

        /** 返回数据定制*/
        $code = $isSuccess ? (empty($code) ? ResultConst::SUCCESS_CODE : $code) : (empty($code) ? 0 : $code);

        /** 写入返回信息 */
        $result->setMsg(strval($msg));
        $result->setCode(intval($code));

        $data = $result->toArray();

        /** 记录请求log */
        $save = [
            'request' => requestLog(),
            'response' => ['response_code' => $code, 'response_msg' => $msg]
        ];

        Logger::getInstance()->log(json_encode($save), LoggerInterface::LOG_LEVEL_INFO, LoggerConst::LOG_NAME_REQUEST_RESPONSE);
        
        $response->withHeader('Content-type', 'application/json;charset=utf-8');
        $response->write(json_encode($data));
        $response->end();
    }
}
