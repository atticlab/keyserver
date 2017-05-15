<?php
namespace App\Controllers;

use App\Lib\Auth;
use App\Lib\Helpers;
use App\Lib\Response;
use App\Models\Wallets;
use OTPHP\TOTP;
use Smartmoney\Stellar\Account;

class WalletsController extends ControllerBase
{
    public function createAction()
    {
        $account_id = $this->payload->account_id ?? null;
        if (!Account::isValidAccountId($account_id)) {
            return $this->response->error(Response::ERR_BAD_PARAM, 'account_id');
        }

        $wallet = Wallets::load($account_id);
        if (!empty($wallet)) {
            return $this->response->error(Response::ERR_ALREADY_EXISTS);
        }

        return $this->validateParams(function ($email, $phone, $face_uuid, $wallet) use ($account_id) {
            if (!empty($wallet)) {
                return $this->response->error(Response::ERR_ALREADY_EXISTS);
            }

            $wallet = new Wallets($account_id);
            $wallet->email = $email;
            $wallet->phone = $phone;
            $wallet->face_uuid = $face_uuid;
            $wallet->wallet_id = $this->payload->wallet_id ?? null;
            $wallet->keychain_data = $this->payload->keychain_data ?? null;
            $wallet->salt = $this->payload->salt ?? null;
            $wallet->kdf_params = $this->payload->kdf_params ?? null;
            $wallet->is_locked = false;
            $wallet->created_at = time();

            try {
                $wallet->save();
            } catch (Exception $e) {
                return $this->response->error(Response::ERR_BAD_PARAM, $e->getMessage());
            }

            $this->response->json('ok');
        });
    }

    public function getDataAction()
    {
        return $this->validateParams(function ($email, $phone, $face_uuid, $wallet) {
            if (empty($wallet)) {
                return $this->response->error(Response::ERR_NOT_FOUND);
            }

            return $this->response->json($wallet->pickProperties(['account_id', 'salt', 'kdf_params']));
        });
    }

    public function notExistAction()
    {
        return $this->validateParams(function ($email, $phone, $face_uuid, $wallet) {
            if (!empty($wallet)) {
                return $this->response->error(Response::ERR_ALREADY_EXISTS);
            }

            return $this->response->json('ok');
        });
    }

    public function getAction()
    {
        $account_id = $this->payload->account_id ?? null;
        $wallet_id = $this->payload->wallet_id ?? null;
        $totp_code = $this->payload->totp_code ?? null;
        $sms_code = $this->payload->sms_code ?? null;

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

        // Two-factor auth logics
        if ($wallet->is_totp_enabled) {
            // Google auth enabled
            if (empty($totp_code)) {
                return $this->response->error(Response::ERR_EMPTY_PARAM, 'totp_code');
            }

            $t = new TOTP(null, $wallet->totp_secret);
            if (!$t->verify((string)$totp_code)) {
                return $this->response->error(Response::ERR_TFA_TOTP);
            }
        } elseif (!empty($wallet->phone)) {
            // Phone authentication
            if (empty($sms_code)) {
                return $this->response->error(Response::ERR_EMPTY_PARAM, 'sms_code');
            }

            $account_id = Auth::accountFromOtp($sms_code);
            if (empty($account_id) || $account_id != $wallet->account_id) {
                return $this->response->error(Response::ERR_TFA_SMS);
            }

            // Clear sms limit
            Auth::clearSmsLimit($wallet->phone);
        }

        return $this->response->json($wallet->pickProperties([
            'keychain_data',
            'email',
            'phone',
            'face_id',
            'is_totp_enabled'
        ]));
    }

    private function validateParams($cb)
    {
        $email = $this->payload->email ?? null;
        $phone = $this->payload->phone ?? null;
        $face_uuid = $this->payload->face_uuid ?? null;

        if (empty($phone) && empty($email) && empty($face_uuid)) {
            return $this->response->error(Response::ERR_EMPTY_PARAM, 'email|phone|face_uuid');
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->error(Response::ERR_BAD_PARAM, 'email');
        }

        if (!empty($face_uuid) && !Helpers::isUUID($face_uuid)) {
            return $this->response->error(Response::ERR_BAD_PARAM, 'face_uuid');
        }

        $wallet = null;

        if (!empty($email)) {
            $wallet = Wallets::findFirstByField('email', $email);
        }

        if (empty($wallet) && !empty($phone)) {
            $wallet = Wallets::findFirstByField('phone', intval($phone));
        }

        if (empty($wallet) && !empty($face_uuid)) {
            $wallet = Wallets::findFirstByField('face_uuid', $face_uuid);
        }

        return $cb($email, $phone, $face_uuid, $wallet);
    }
}