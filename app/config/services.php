<?php

use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Url as UrlResolver;
//use Phalcon\Session\Adapter\Files as SessionAdapter;
use Phalcon\Http\Request;
use SWP\Services\RiakDBService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault();

$di->setShared('config', function () use ($config) {
	return $config;
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () use ($config) {
	$url = new UrlResolver();
	$url->setBaseUri($config->application->baseUri);
	return $url;
}, true);

$di->set("request", new Request());

$di->setShared('riakDB', function () use ($config) {
	$riak = new RiakDBService(
		       $config->database->riak->port,
        (array)$config->database->riak->hosts
	);
	return $riak->db;
});

$di->setShared('logger', function () use ($config) {
    $logger = new Logger('logger');
    $logger->pushHandler(new StreamHandler($config->log->path, $config->log->level));

    return $logger;
});

$app->setDI($di);