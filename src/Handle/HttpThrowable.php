<?php

namespace ESL\Handle;

use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use ESL\Output\Json;

class HttpThrowable
{
    public static function run(\Throwable $throwable, Request $request, Response $response)
    {
        Json::fail($throwable, $throwable->getCode(), $throwable->getMessage());
    }
}