<?php

$loader = new \Phalcon\Loader();

$loader->registerNamespaces(
    array(
        "SWP\Services" => $config->application->servicesDir,
        "SWP\Models" => $config->application->modelsDir,
        "SWP\Validators" => $config->application->validatorsDir
    )
);

$loader->registerDirs(
    array(
        $config->application->controllersDir,
    )
);

$loader->register();