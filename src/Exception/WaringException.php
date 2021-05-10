<?php

namespace ESL\Exception;


use EasySwoole\EasySwoole\Logger;

class WaringException extends BaseException
{
    public function __construct(int $code, string $msg = '', \Throwable $previous = null)
    {
        /** 录入日志 */
        $data = ['code' => $code, 'msg' => $msg];
        if (isHttp()) {
            $data['request'] = requestLog();
            $data['trace'] = $this->getTrace();
        }
        Logger::getInstance()->waring(json_encode($data), 'exception');
        parent::__construct($code, $msg, $previous);
    }
}