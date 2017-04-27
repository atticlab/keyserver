<?php
namespace App\Controllers;

use Smartmoney\Stellar\Account;
use App\Lib\Response;

class IndexController extends ControllerBase
{
    public function indexAction()
    {
        return $this->response->json(['Keyserver']);
    }

    public function getkdfAction()
    {
        return $this->response->json($this->config->kdfparams);
    }
}
