<?php

namespace ESL\Exception;


class BaseException extends \Exception
{
    public function __construct(int $code, string $msg = '', \Throwable $previous = null)
    {
        parent::__construct($msg, $code, $this);
    }
}