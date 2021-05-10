<?php

namespace ESL\Call;

use EasySwoole\HttpClient\Bean\Response;
use EasySwoole\HttpClient\HttpClient;
use ESL\Exception\ErrorException;

class Curl extends HttpClient
{
    protected $is200 = true;

    /**
     * 设置请求头集合
     * @param array $header
     * @param bool $isMerge
     * @param bool $strToLower
     * @return HttpClient
     */
    public function setHeaders(array $header, $isMerge = true, $strToLower = false): HttpClient
    {
        $this->clientHandler->getRequest()->setHeaders($header, $isMerge, $strToLower);
        return $this;
    }

    public function get(array $headers = []): Response
    {
        $response = parent::get($headers);
        $this->isSuccess($response);
        if ($this->is200) {
            $this->is200($response);
        }

        return $response;
    }

    public function post($data = null, array $headers = []): Response
    {
        $response = parent::post($data, $headers);
        $this->isSuccess($response);
        if ($this->is200) {
            $this->is200($response);
        }

        return $response;
    }

    public function delete(array $headers = []): Response
    {
        $response = parent::delete($headers);
        $this->isSuccess($response);
        if ($this->is200) {
            $this->is200($response);
        }

        return $response;
    }

    public function put($data = null, array $headers = []): Response
    {
        $response = parent::put($data, $headers);
        $this->isSuccess($response);
        if ($this->is200) {
            $this->is200($response);
        }

        return $response;
    }

    private function isSuccess(Response $response)
    {
        $errCode = $response->getErrCode();
        if ($errCode !== 0) {
            throw new ErrorException(1021, '远程网络异常:' . $response->getErrMsg());
        }
    }

    private function is200(Response $response)
    {
        $code = $response->getStatusCode();
        if (200 != $code) {
            throw new ErrorException(1020, '远程网络连接失败 http_code:' . $code . ' ' . $response->getBody());
        }
    }

    /**
     * @param bool $is200
     */
    public function setIs200(bool $is200): void
    {
        $this->is200 = $is200;
    }
}