<?php

use App\Constant\AppConst;
use EasySwoole\Component\Di;
use EasySwoole\Http\Request;
use ESL\Trace;

function isProduction(): bool
{
    return env() === strtolower('PRODUCTION') ? true : false;
}

function isDev(): bool
{
    return env() === strtolower('PRODUCTION') ? false : true;
}

function config($keyPath = '', $env = false)
{
    // 获取当前开发环境
    if ($env) {
        $keyPath = $keyPath . "." . env();
    }
    return EasySwoole\EasySwoole\Config::getInstance()->getConf($keyPath);
}

function isHttp()
{
    $workId = \EasySwoole\EasySwoole\ServerManager::getInstance()->getSwooleServer()->worker_id;

    if ($workId < 0) {
        return false;
    }

    return true;
}

/**
 * 获取当前环境
 * @return string
 */
function env(): string
{
    return strtolower(config('ENV'));
}


/**
 * 判断一个变量是否为空，但不包括 0 和 '0'.
 *
 * @param $value
 *
 * @return bool 返回true 说明为空 返回false 说明不为空
 */
function superEmpty($value): bool
{
    // 如果是一个数组
    if (is_array($value)) {
        if (count($value) == 1 && isset($value[0]) && $value[0] !== 0 && $value[0] !== '0' && empty($value[0])) {
            unset($value[0]);
        }
        return empty($value) ? true : false;
    }

    // 如果是一个对象
    if (is_object($value)) {
        if (empty((array)$value)) {
            return true;
        }
        return false;
//        return empty($value->id) ? true : false;
    }

    // 如果是其它
    if (empty($value)) {
        if (is_int($value) && 0 === $value) {
            return false;
        }

        if (is_string($value) && '0' === $value) {
            return false;
        }

        return true;
    }

    return false;
}

function nowDate(string $format = 'Y-m-d H:i:s'): string
{
    return date($format, time());
}

/**
 * 保留数组中部分元素
 * @param array $array
 * @param array $keys
 * @return array
 */
function array_save(array $array, array $keys = []): array
{
    $nList = [];
    foreach ($array as $item => $value) {
        if (in_array($item, $keys)) {
            $nList[$item] = $array[$item];
        }
    }

    return $nList;
}

function clientIp(): ?string
{
    $request = Di::getInstance()->get(AppConst::DI_REQUEST);
    if (!($request instanceof Request)) {
        return null;
    }

    $ip = \EasySwoole\EasySwoole\ServerManager::getInstance()->getSwooleServer()->connection_info($request->getSwooleRequest()->fd)['remote_ip'] ?? null;
    return $ip;

    //header 地址，例如经过nginx proxy后
    //    $ip2 = $request->getHeaders();
    //    var_dump($ip2);
}

function requestLog(): ?array
{
    if (isHttp()) {
        $request = Di::getInstance()->get(AppConst::DI_REQUEST);

        $swooleRequest = (array)$request->getSwooleRequest();
        $raw = $request->getBody()->__toString();

        $headerServer1 = array_merge($swooleRequest['header'] ?? [], $swooleRequest['server'] ?? []);
        $headerServer2 = [
            'fd' => $swooleRequest['fd'] ?? null,
            'request' => $swooleRequest['request'] ?? null,
            'cookie' => $swooleRequest['cookie'] ?? null,
            'get_params' => $swooleRequest['get'] ?? null,
            'post_params' => $swooleRequest['post'] ?? null,
            'raw' => $raw,
            'files_params' => $swooleRequest['files'] ?? null,
            'tmpfiles' => $swooleRequest['tmpfiles'] ?? null,
        ];
        $headerServer = array_merge($headerServer1, $headerServer2);
        $headerServer['trace_code'] = Trace::getRequestId();

        return $headerServer;
    }

    return null;
}

function setIdentity($identity): void
{
    Di::getInstance()->set(AppConst::HEADER_AUTH, $identity);
}

function identity()
{
    return Di::getInstance()->get(AppConst::HEADER_AUTH);
}

function setAppCode($appCode): void
{
    $ref = new \ReflectionClass(AppConst::class);
    $headerAppCode = $ref->getConstant('HEADER_APP_CODE');

    if (superEmpty($headerAppCode)) {
        throw new \Es3\Exception\InfoException(1036, "App\Constant\AppConst常量中缺少 HEADER_APP_CODE 常量");
    }

    Di::getInstance()->set($headerAppCode, $appCode);
}

function appCode()
{
    $ref = new \ReflectionClass(AppConst::class);
    $headerAppCode = $ref->getConstant('HEADER_APP_CODE');

    if (superEmpty($headerAppCode)) {
        throw new \Es3\Exception\InfoException(1035, "App\Constant\AppConst常量中缺少 HEADER_APP_CODE 常量");
    }

    return Di::getInstance()->get($headerAppCode);
}

function redisKey(string ...$key): string
{
    if (superEmpty($key)) {
        throw new \Es3\Exception\InfoException(1301, '请传递redis key');
    }

    $key = implode('_', $key);
    return strtolower(\App\Constant\EnvConst::SERVICE_NAME . '_' . \App\Constant\EnvConst::SERVER_PORT . '_' . $key);
}

function headers(): array
{
    if (!isHttp()) {
        return [];
    }

    $request = Di::getInstance()->get(AppConst::DI_REQUEST);
    $swooleRequest = (array)$request->getSwooleRequest();
    return $swooleRequest['header'] ?? [];
}

function traceCode(): string
{
    $traceCode = Trace::getRequestId();
    return $traceCode;
}

function createUserCode()
{
    return identity()[AppConst::IDENTITY_USER_CODE] ?? null;
}

function createUserName()
{
    return identity()[AppConst::IDENTITY_USER_NAME] ?? null;
}