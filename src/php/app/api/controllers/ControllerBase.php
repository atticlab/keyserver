<?php

namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Response;
use Smartmoney\Stellar\Account;

class ControllerBase extends \Phalcon\Mvc\Controller
{
    protected $payload;

    public function beforeExecuteRoute()
    {
        $this->payload = json_decode(file_get_contents('php://input'));

        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            $request_url = parse_url($_SERVER['HTTP_ORIGIN']);
            // if (in_array($request_url['host'], (array)$this->config->project->allowed_referrers)) {
                $this->response->setHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN']);
                $this->response->setHeader('Access-Control-Allow-Credentials', 'true');

                if ($this->request->isOptions()) {
                    $this->response->setHeader('Access-Control-Allow-Headers',
                        'Nonce, Signature, Origin, X-CSRF-Token, X-Requested-With, X-HTTP-Method-Override, Content-Range, Content-Disposition, Content-Type, Authorization');
                    $this->response->setHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, DELETE');
                    $this->response->sendHeaders();
                    exit;
                }
            //}
        }
    }

    public function buildUrl($path = null)
    {
        if (!empty($path)) {
            $path = '/' . trim($path, '/');
        }

        return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $path;
    }

    public function checkSignature($cb)
    {
        $nonce = $this->request->getHeader('Nonce');
        $signature = $this->request->getHeader('Signature');

        if (empty($nonce)) {
            return $this->response->error(Response::ERR_BAD_SIGN);
        }

        if (empty($signature)) {
            return $this->response->error(Response::ERR_BAD_SIGN);
        }

        $account_id = Auth::accountFromNonce($nonce);
        if (empty($account_id)) {
            return $this->response->error(Response::ERR_BAD_SIGN);
        }


        $public_key = Account::getPublicKeyFromAccountId($account_id);

        $request_data = $nonce . $this->request->getURI() . $this->request->getRawBody();
        if (!ed25519_sign_open($request_data, $public_key, base64_decode($signature))) {
            return $this->response->error(Response::ERR_BAD_SIGN);
        }

        return $cb($account_id);
    }
}
