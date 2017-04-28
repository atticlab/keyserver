<?php
namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Response;
use App\Models\Wallets;
use Smartmoney\Stellar\Account;
use OTPHP\TOTP;

class AuthController extends ControllerBase
{
    public function createNonceAction()
    {
        $account_id = $this->payload->account_id ?? null;
        if (empty($account_id)) {
            return $this->response->error(Response::ERR_EMPTY_PARAM, 'account_id');
        }

        if (!Account::isValidAccountId($account_id)) {
            return $this->response->error(Response::ERR_BAD_PARAM, 'account_id');
        }

        return $this->response->json(Auth::createNonce($account_id));
    }

    public function enableTotpAction()
    {
        $nonce = $this->payload->nonce ?? null;
        $signature = $this->payload->signature ?? null;

        if (empty($nonce)) {
            return $this->response->error(Response::ERR_EMPTY_PARAM, 'nonce');
        }

        if (empty($signature)) {
            return $this->response->error(Response::ERR_EMPTY_PARAM, 'signature');
        }

        $account_id = Auth::accountFromNonce($nonce);
        if (empty($account_id)) {
            return $this->response->error(Response::ERR_BAD_PARAM, 'nonce');
        }

        $public_key = Account::getPublicKeyFromAccountId($account_id);

        if (!ed25519_sign_open($nonce, $public_key, base64_decode($signature))) {
            return $this->response->error(Response::ERR_BAD_SIGN);
        }

        $wallet = Wallets::load($account_id);
        if (empty($wallet)) {
            return $this->response->error(Response::ERR_NOT_FOUND);
        }

        if (!empty($wallet->totp_secret)) {
            return $this->response->error(Response::ERR_ALREADY_EXISTS);
        }

        $wallet->generateTotpSecret();
        $wallet->save();

        return $this->response->json($wallet->pickProperties(['totp_secret']));
    }

    public function activateTotpAction()
    {
        $account_id = $this->payload->account_id ?? null;
        $totp_code = $this->payload->totp_code ?? null;

        if (!Account::isValidAccountId($account_id)) {
            return $this->response->error(Response::ERR_BAD_PARAM, 'account_id');
        }

        if (empty($totp_code)) {
            return $this->response->error(Response::ERR_EMPTY_PARAM, 'totp_code');
        }

        $wallet = Wallets::load($account_id);
        if (empty($wallet)) {
            return $this->response->error(Response::ERR_NOT_FOUND);
        }

        if (!$wallet->is_totp_enabled) {
            return $this->response->error(Response::ERR_TOTP_DISABLED);
        }

        $t = new TOTP(null, $wallet->totp_secret);
        if (!$t->verify((string)$totp_code)) {
            return $this->response->error(Response::ERR_TFA_TOTP);
        }

        $wallet->is_totp_enabled = true;
        $wallet->save();

        return $this->response->json('ok');
    }

    public function disableTotpAction()
    {
        $nonce = $this->payload->nonce ?? null;
        $signature = $this->payload->signature ?? null;

        if (empty($nonce)) {
            return $this->response->error(Response::ERR_EMPTY_PARAM, 'nonce');
        }

        if (empty($signature)) {
            return $this->response->error(Response::ERR_EMPTY_PARAM, 'signature');
        }

        $account_id = Auth::accountFromNonce($nonce);
        if (empty($account_id)) {
            return $this->response->error(Response::ERR_BAD_PARAM, 'nonce');
        }

        $public_key = Account::getPublicKeyFromAccountId($account_id);

        if (!ed25519_sign_open($nonce, $public_key, base64_decode($signature))) {
            return $this->response->error(Response::ERR_BAD_SIGN);
        }

        $wallet = Wallets::load($account_id);
        if (empty($wallet)) {
            return $this->response->error(Response::ERR_NOT_FOUND);
        }

        $wallet->is_totp_enabled = false;
        $wallet->totp_secret = null;
        $wallet->save();
    }

    public function sendSmsAction()
    {
        $account_id = $this->payload->account_id ?? null;
        $wallet_id = $this->payload->wallet_id ?? null;

        if (empty($account_id)) {
            return $this->response->error(Response::ERR_EMPTY_PARAM, 'account_id');
        }

        if (empty($wallet_id)) {
            return $this->response->error(Response::ERR_EMPTY_PARAM, 'wallet_id');
        }

        $wallet = Wallets::load($account_id);
        if (empty($wallet) || $wallet->wallet_id != $wallet_id) {
            return $this->response->error(Response::ERR_NOT_FOUND);
        }

        if (empty($wallet->phone)) {
            return $this->response->error(Response::ERR_NO_PHONE);
        }

        if (!Auth::isSmsAllowed($wallet->phone)) {
            return $this->response->error(Response::ERR_SMS_LIMIT);
        }

        $this->sms->Auth([
            'login'    => $this->config->sms->login,
            'password' => $this->config->sms->password,
        ]);

        $balance = $this->sms->GetCreditBalance();
        if (!is_numeric($balance->GetCreditBalanceResult)) {
            $this->logger->error('Cannot send SMS: ' . $balance->GetCreditBalanceResult);

            return $this->response->error(Response::ERR_SERVICE);
        }

        $balance = intval($balance->GetCreditBalanceResult);
        if ($balance <= 0) {
            $this->logger->error('Cannot send SMS. Empty balance!');

            return $this->response->error(Response::ERR_SERVICE);
        }

        $code = Auth::createOtp($wallet->account_id);
        $sms = [
            'sender'      => $this->config->sms->sender,
            'destination' => '+' . $wallet->phone,
            'text'        => 'Your auth code: ' . $code
        ];
        $response = $this->sms->SendSMS($sms);

        if (!is_array($response->SendSMSResult->ResultArray) || $response->SendSMSResult->ResultArray[0] != 'Сообщения успешно отправлены') {
            $this->logger->error('Cannot send SMS:', [$response->SendSMSResult->ResultArray, $sms]);

            return $this->response->error(Response::ERR_SERVICE);
        }

        Auth::incrSmsLimit($wallet->phone);

        return $this->response->json('ok');
    }
}