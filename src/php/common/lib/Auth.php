<?php

namespace App\Lib;

class Auth
{
    const NONCE_PREFIX = 'nonce_';
    const SMS_PREFIX = 'sms_';
    const SMS_LIMIT_PREFIX = 'smsl_';

    const OTP_LENGTH = 6;
    const NONCE_TTL = 60;
    const SMS_TTL = 60 * 3;

    public static function createNonce($account_id)
    {
        $redis = \Phalcon\Di::getDefault()->getRedis();
        $nonce = bin2hex(random_bytes(16));
        $redis->set(self::NONCE_PREFIX . $nonce, $account_id);
        $redis->expire(self::NONCE_PREFIX . $nonce, self::NONCE_TTL);

        return $nonce;
    }

    public static function accountFromNonce($nonce)
    {
        $redis = \Phalcon\Di::getDefault()->getRedis();

        $account_id = $redis->get(self::NONCE_PREFIX . $nonce);
        $redis->delete(self::NONCE_PREFIX . $nonce);

        return $account_id;
    }

    public static function createOtp($account_id)
    {
        $redis = \Phalcon\Di::getDefault()->getRedis();

        $code = '';
        for ($i = 0; $i < self::OTP_LENGTH; $i++) {
            $code .= mt_rand(0, 9);
        }

        $redis->set(self::SMS_PREFIX . $code, $account_id);
        $redis->expire(self::SMS_PREFIX . $code, self::SMS_TTL);

        return $code;
    }

    public static function accountFromOtp($otp)
    {
        $redis = \Phalcon\Di::getDefault()->getRedis();
        $account_id = $redis->get(self::SMS_PREFIX . $otp);
        $redis->delete(self::SMS_PREFIX . $otp);
        
        return $account_id;
    }

    public static function incrSmsLimit($phone)
    {
        $config = \Phalcon\Di::getDefault()->getConfig();
        $redis = \Phalcon\Di::getDefault()->getRedis();

        $amount = $redis->incr(self::SMS_LIMIT_PREFIX . $phone);
        $redis->expire(self::SMS_LIMIT_PREFIX . $phone, $config->sms->limit_ttl);

        return $amount;
    }

    public static function clearSmsLimit($phone)
    {
        $redis = \Phalcon\Di::getDefault()->getRedis();

        return $redis->delete(self::SMS_LIMIT_PREFIX . $phone);
    }

    public static function isSmsAllowed($phone)
    {
        $redis = \Phalcon\Di::getDefault()->getRedis();
        $config = \Phalcon\Di::getDefault()->getConfig();

        $total_sent = $redis->get(self::SMS_LIMIT_PREFIX . $phone);
        $total_sent = intval($total_sent);

        return $total_sent < $config->sms->limit;
    }
}