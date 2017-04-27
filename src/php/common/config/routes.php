<?php

$router->add('/:params', [
    'controller' => 'index',
    'action'     => 'index'
]);

$router->add('/:controller/:action/:params', [
    'controller' => 1,
    'action'     => 2,
    'params'     => 3
]);

$router->add('/:controller/:action', [
    'controller' => 1,
    'action'     => 2,
]);

$router->add('/:controller', [
    'controller' => 1,
]);