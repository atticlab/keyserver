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
	$clustersArray = $config->toArray()['database']['riak']['clustersArray'];
	$riak = new RiakDBService(
		$config->database->riak->port,
		$clustersArray
	);
	return $riak->db;
});

$di->setShared('logger', function () use ($config) {
    $logger = new Logger('logger');
    $logger->pushHandler(new StreamHandler($config->log->path, $config->log->level));

    return $logger;
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
/*$di->set('db', function () use ($config) {
	$adapter = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
		'host'     => $config->database->mysql->host,
		'username' => $config->database->mysql->username,
		'password' => $config->database->mysql->password,
		'dbname'   => $config->database->mysql->dbname,
		"charset"  => $config->database->mysql->charset
	));
	$adapter->getInternalHandler()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    return $adapter;
});
*/

/**
 * Start the session the first time some component request the session service
 */
/*$di->set('session', function () use ($config) {
	$session = new SessionAdapter();
	$session->start();

	return $session;
});
*/

/**
 * Mail service
 */
/*$di->set('mail', function () use ($config) {
	return new Mail($config);
});*/

$app->setDI($di);