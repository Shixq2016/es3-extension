<?php

namespace ESL;

use App\Constant\AppConst;
use App\Constant\LoggerConst;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Http\Message\Status;
use EasySwoole\Http\Request;
use \EasySwoole\Http\Response;
use EasySwoole\Log\LoggerInterface;

class Middleware
{
    public static function onRequest(Request $request, Response $response)
    {
        $request->withAttribute('access_log', microtime(true));

        $self = new self();

        /** 空参数过滤 */
        $self->clearEmptyParams($request, $response);

        /** 写入请求日主 */
        $self->access($request, $response);
    }

    public static function afterRequest(Request $request, Response $response)
    {
        $self = new self();

        /** 跨域注入 */
        $self->crossDomain($request, $response);

        /**slog */
        $self->slog($request, $response);
    }

    private function crossDomain(Request $request, $response)
    {
        // 任何环境都不做限制
        $headers = 'Content-Type, Authorization, X-Requested-With, token, identity';
        $allowAccessControl = AppConst::HEADER_ALLOW_ACCESS_CONTROL;
        if (!superEmpty($allowAccessControl)) {
            $headers = $headers . ' , ' . implode($allowAccessControl, ' , ');
        }

        /** 生产情况的跨域 由 运维处理 */
        if (!isProduction()) {

            $origin = current($request->getHeader('origin') ?? null) ?? '';
            $origin = rtrim($origin, '/');

            $response->withHeader('Access-Control-Allow-Origin', superEmpty($origin) ? '*' : $origin);
            $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, PUT, OPTIONS');
            $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        $response->withHeader('Access-Control-Allow-Headers', $headers);
        if ($request->getMethod() === 'OPTIONS') {
            $response->withStatus(Status::CODE_OK);
            $response->end();
            return true;
        }

    }

    private function clearEmptyParams(Request $request, $response): void
    {
        if ($request->getMethod() != 'GET') {
            return;
        }

        $params = $request->getQueryParams() ?? [];
        foreach ($params as $key => $val) {
            if (superEmpty($val)) {
                unset($params[$key]);
            }
        }

        $request->withQueryParams($params);
    }

    private function slog(Request $request, $response)
    {
        /** 从请求里获取之前增加的时间戳 */
        $reqTime = $request->getAttribute(LoggerConst::LOG_NAME_ACCESS);

        /** 计算一下运行时间  */
        $runTime = round(microtime(true) - $reqTime, 5);

        /** 拼接一个简单的日志 */
        $runTime = round(floatval($runTime * 1000), 0);
        $accessLog = $request->getUri() . ' | ' . clientIp() . ' | ' . $runTime . ' ms | ' . $request->getHeader('user-agent')[0];

        /** 正常日志 */
        $log = Logger::getInstance()->log($accessLog, LoggerInterface::LOG_LEVEL_INFO, LoggerConst::LOG_NAME_ACCESS);

        /** 慢日志 */
        if ($runTime > round(LoggerConst::LOG_SLOG_SECONDS * 1000, 0)) {
            $log = Logger::getInstance()->log($accessLog, LoggerInterface::LOG_LEVEL_WARNING, LoggerConst::LOG_NAME_SLOG);
        }
    }

    private function access(Request $request, $response)
    {
        $accessLog = $request->getUri() . ' | ' . clientIp() . ' | ' . $request->getHeader('user-agent')[0];
        Logger::getInstance()->log($accessLog, LoggerInterface::LOG_LEVEL_INFO, LoggerConst::LOG_NAME_ACCESS);
    }
}
