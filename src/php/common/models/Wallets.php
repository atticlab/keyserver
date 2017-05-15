<?php

namespace App\Models;

use App\Lib\Helpers;
use Exception;
use Phalcon\Di;
use Smartmoney\Stellar\Strkey\Base32;

class Wallets extends ModelBase
{
    const RIAK_BUCKET = 'wallets';
    const TOTP_SECRET_BYTELENGTH = 10;

    public $account_id;

    public $email;
    public $phone;
    public $face_uuid;
    public $wallet_id;
    public $keychain_data;
    public $salt;
    public $kdf_params;
    public $is_locked = false;
    public $created_at;
    public $is_totp_enabled = false;
    public $totp_secret;

    /** @var array Fields which will be stored to DB */
    static $stored_props = [
        'email',
        'phone',
        'face_uuid',
        'wallet_id',
        'keychain_data',
        'salt',
        'kdf_params',
        'is_locked',
        'created_at',
        'is_totp_enabled',
        'totp_secret'
    ];

    public function __construct($account_id)
    {
        $this->account_id = $account_id;
    }

    public static function load($account_id)
    {
        if (empty($account_id)) {
            return false;
        }

        $riak = Di::getDefault()->getRiak();
        $props = $riak->get(self::RIAK_BUCKET, $account_id);
        if (empty($props)) {
            return false;
        }

        $tx = new self($account_id);
        $tx->fillProperties($props, self::$stored_props);

        return $tx;
    }

    public function save()
    {
        $this->validate();
        $riak = DI::getDefault()->getRiak();

        return $riak->set(self::RIAK_BUCKET, $this->account_id, $this->pickProperties(self::$stored_props));
    }

    public static function findFirstByField($field, $value)
    {
        if (empty($field) || empty($value)) {
            return false;
        }

        $riak = DI::getDefault()->getRiak();

        $q = new \App\Lib\Riak\Query(self::RIAK_BUCKET);
        $q->where($field, $value)
            ->limit(1);

        $data = $riak->search($q);
        if (!empty($data['docs'])) {
            $props = reset($data['docs']);

            return self::load($props['_yz_rk']);
        }

        return false;
    }

    public function validate()
    {
        if (empty($this->account_id)) {
            throw new Exception('account_id');
        }

        if (empty($this->wallet_id)) {
            throw new Exception('wallet_id');
        }

        if (empty($this->keychain_data)) {
            throw new Exception('keychain_data');
        }

        if (empty($this->salt)) {
            throw new Exception('salt');
        }

        if (empty($this->kdf_params)) {
            throw new Exception('kdf_params');
        }

        if (is_null($this->is_locked)) {
            throw new Exception('is_locked');
        }

        if (empty($this->created_at)) {
            throw new Exception('created_at');
        }

        if (empty($this->phone) && empty($this->email) && empty($this->face_uuid)) {
            throw new Exception('phone|email|face_uuid');
        }

        if (!empty($this->phone) && !preg_match('/^(\+)?(38)(\d){10}$/', $this->phone)) {
            throw new Exception('Both email and phone empty');
        }

        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('email');
        }

        if (!empty($this->face_uuid) && !Helpers::isUUID($this->face_uuid)) {
            throw new Exception('face_uuid');
        }

        if ($this->is_totp_enabled && empty($this->totp_secret)) {
            throw new Exception('totp is enabled, but secret is empty');
        }

        $this->email = strtolower($this->email);
        $this->phone = intval($this->phone);

        return $this;
    }

    public function generateTotpSecret()
    {
        $this->totp_secret = Base32::encode(random_bytes(self::TOTP_SECRET_BYTELENGTH));
    }
}