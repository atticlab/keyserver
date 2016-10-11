<?php
return new \Phalcon\Config(array(
    'database' => array(
        'riak' => array(
            'host' => 'riak',
            'username' => '',
            'password' => '',
            'port' => '8098',
            'clustersArray' => array('riak')
        )
    ),
    'application' => array(
        'controllersDir' => __DIR__ . '/../controllers/',
        'libraryDir' => __DIR__ . '/../library/',
        'modelsDir' => __DIR__ . '/../models/',
        'cacheDir' => __DIR__ . '/../cache/',
        'servicesDir' => __DIR__ . '/../services/',
        'validatorsDir' => __DIR__ . '/../validators/',
        'baseUri' => '/',
        'publicUrl' => 'http://http://stellar-wallet-php/'
    ),

    'mail' => array(
        'fromName' => '',
        'fromEmail' => '@',
        'smtp' => array(
            'server' => 'smtp.gmail.com',
            'port' => 465,
            'security' => 'ssl',
            'username' => 'testuser@gmail.com',
            'password' => '',
        )
    ),

    'accountsToDelete' => array(
        'admin',
        'bank',
        'merch',
        'alice',
        'bob',
        'charly'
    ),
    'log' => [
        'level' => getenv('LOG_LEVEL'),
        'path'  => __DIR__ . '/../../logs/monolog.log'
    ]
));

