<?php

namespace App\Lib;

class Response extends \Phalcon\Http\Response
{
    const ERR_SERVICE = 'ERR_SERVICE';
    const ERR_NOT_FOUND = 'ERR_NOT_FOUND';
    const ERR_NOT_ALLOWED = 'ERR_NOT_ALLOWED';
    const ERR_ALREADY_EXISTS = 'ERR_ALREADY_EXISTS';
    const ERR_BAD_PARAM = 'ERR_BAD_PARAM';
    const ERR_EMPTY_PARAM = 'ERR_EMPTY_PARAM';
    const ERR_NOT_ACTIVATED = 'ERR_NOT_ACTIVATED';
    const ERR_TFA_AUTH = 'ERR_TFA_AUTH';
    const ERR_NO_PHONE = 'ERR_NO_PHONE';

    public function error($err_code, $msg = '')
    {
        if (!defined('self::' . $err_code)) {
            throw new \Exception($err_code . ' - Unknown error code');
        }

        $this->setStatusCode(400);

        $this->setJsonContent([
            'error'   => $err_code,
            'message' => $msg,
        ])->send();

        exit;
    }

    public function json($data)
    {
        return $this->setJsonContent($data);
    }
}