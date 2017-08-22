<?php

namespace App\Lib;

class Response extends \Phalcon\Http\Response
{
    const ERR_SERVICE = 'ERR_SERVICE';
    const ERR_NOT_FOUND = 'ERR_NOT_FOUND';
    const ERR_ALREADY_EXISTS = 'ERR_ALREADY_EXISTS';
    const ERR_BAD_PARAM = 'ERR_BAD_PARAM';
    const ERR_EMPTY_PARAM = 'ERR_EMPTY_PARAM';
    const ERR_NOT_ALLOWED = 'ERR_NOT_ALLOWED';
    const ERR_TOTP_DISABLED = 'ERR_TOTP_DISABLED';
    const ERR_BAD_SIGN = 'ERR_BAD_SIGN';
    const ERR_SMS_LIMIT = 'ERR_SMS_LIMIT';
    const ERR_NO_PHONE = 'ERR_NO_PHONE';
    const ERR_TFA_TOTP = 'ERR_TFA_TOTP';
    const ERR_TFA_SMS = 'ERR_TFA_SMS';

    //maintenance
    const ERR_MAINTENANCE = 'ERR_MAINTENANCE';

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