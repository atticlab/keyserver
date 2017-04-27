<?php

define('PHONE_NUM_LENGH', 10);

return new \Phalcon\Config([

    'modules' => ['api', 'admin'],

    'riak' => [
        'host' => getenv('RIAK_HOST')
    ],

    'kdfparams' => [
        'algorithm'          => 'scrypt',
        'bits'               => 256,
        'n'                  => pow(2, 12),
        'r'                  => 8,
        'p'                  => 1,
        'password_algorithm' => 'sha256',
        'password_rounds'    => pow(2, 12),
    ],

    'redis' => [
        'host' => 'redis'
    ],

    'sms' => [
        'limit'     => 3,
        'limit_ttl' => 60 * 60 * 3,
        'wsdl_url'  => 'http://turbosms.in.ua/api/wsdl.html',
        'sender'    => getenv("TURBOSMS_SENDER"),
        'login'     => getenv("TURBOSMS_LOGIN"),
        'password'  => getenv("TURBOSMS_PASS"),
    ]

//    'smtp' => [
//        'host'     => getenv("SMTP_HOST"),
//        'port'     => getenv("SMTP_PORT"),
//        'security' => getenv("SMTP_SECURITY"),
//        'username' => getenv("SMTP_USER"),
//        'password' => getenv("SMTP_PASS"),
//    ],
]);
