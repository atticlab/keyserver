<?php

use \SWP\Services\ResponseService;

class KdfparamsController extends \Phalcon\Mvc\Controller
{
    public function showAction()
    {
        return ResponseService::prepareResponse(json_encode([
            'algorithm' => 'scrypt',
            'bits'      => 256,
            'n'         => pow(2,12),
            'r'         => 8,
            'p'         => 1
        ]));
    }
}