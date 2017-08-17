<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;
use App\Lib\RiakMonologHandler;

# Logger
$di->setShared('logger', function () use ($config, $di) {
    $format = new Monolog\Formatter\LineFormatter("[%datetime%] %level_name%: %message% %context% [%extra.class%: %extra.line%]\n");
    $stream = new StreamHandler(ini_get('error_log'), Logger::DEBUG);
    $stream->setFormatter($format);

    $riak_handler = new RiakMonologHandler('logs', Logger::DEBUG); // use Logger::WARNING for production
    $riak_handler->setFormatter($format);

    $log = new Logger(__FUNCTION__);
    $log->pushProcessor(new IntrospectionProcessor());
    $log->pushHandler($stream);
    $log->pushHandler($riak_handler);

    return $log;
});

$di->setShared('crypt', function () use ($config) {
    $crypt = new \Phalcon\Crypt();
    $crypt->setMode(MCRYPT_MODE_CFB);

    return $crypt;
});

# Session
$di->setShared('session', function () use ($config) {
    $params = [];

    if (!empty($config->project->sess_prefix)) {
        $params['uniqueId'] = $config->project->sess_prefix;
    }

    $session = new \Phalcon\Session\Adapter\Files($params);
    $session->start();

    return $session;
});

# Cookies
$di->setShared('cookies', function () {
    $cookies = new \Phalcon\Http\Response\Cookies();
    $cookies->useEncryption(false);

    return $cookies;
});

# Config
$di->setShared('config', $config);

# Mailer (requires composer component)
$di->setShared('mailer', function () use ($config) {
    $mailer = new \App\Lib\Mailer([
        'templates' => APP_PATH . 'common/emails/',
        'host'      => $config->smtp->host,
        'port'      => $config->smtp->port,
        'username'  => $config->smtp->username,
        'password'  => $config->smtp->password,
        'security'  => $config->smtp->security
    ]);

    if (!empty($config->project->admin_email) && !empty($config->project->admin_name)) {
        $mailer->setFrom($config->project->admin_email, $config->project->admin_name);
    }

    return $mailer;
});

$di->setShared('riak', function () use ($config) {
    $conn = new \App\Lib\Riak(
        $config->riak->host
    );

    return $conn;
});

$di->setShared('riak_cli', function () use ($config) {
    $conn = new \Atticlab\RiakLite\Riak(
        getenv('RIAK_HOST'),
        getenv('SSL_PATH'),
        getenv('SSL_PASS')
    );

    return $conn;
});

//$di->setShared('memcached', function () use ($config) {
//    $m = new \Memcached();
//    $m->addServer('memcached', 11211);
//
//    if (empty($m->getStats())) {
//        throw new Exception('Cannot connect to memcached');
//    }
//
//    return $m;
//});

$di->setShared('sms', function () use ($config) {
    $soap = new \attics\Lib\Soap\Client($config->sms->wsdl_url);
    $soap->setVersion(1);

    return $soap->getHandler();
});

$di->setShared('redis', function () use ($config) {
    $redis = new Redis();

    if (!$redis->connect($config->redis->host)) {
        throw new Exception('Cannot connect to Redis. Host: ' . $config->redis->host);
    }

    return $redis;
});