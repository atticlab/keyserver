<?php
/**
 * Created by PhpStorm.
 * User: skorzun
 * Date: 16.06.16
 * Time: 16:37
 */

namespace SWP\Models;

use \Basho\Riak;
use \Basho\Riak\Bucket;
use \Basho\Riak\Command;
use Exception;

class Wallet
{
    public $username;
    public $accountId;
    public $walletId;
    public $salt;
    public $kdfParams;
    public $publicKey;
    public $mainData;
    public $keychainData;
    public $usernameProof;
    public $createdAt;
    public $updatedAt;
    public $lockVersion;

    public $totpRequired = false; //old stellar-wallet appendicitis

    public $phone;
    public $email;

    /**
     * @var Riak $riak
     */
    private $riak = null;
    /**
     * @var Riak\DataType\Map $data
     */
    private $data = null;
    /**
     * @var Riak\Bucket $bucket
     */
    private $bucket;
    /**
     * @var Riak\Location $location
     */
    private $location;

    public function __construct(Riak $riak, $username)
    {
        $this->riak = $riak;
        $this->bucket = new Bucket('wallets');
        $this->username = $username;
        $this->location = new Riak\Location($username, $this->bucket);
    }

    public function __toString()
    {
        return json_encode([
            'username' => $this->username,
            'accountId' => $this->accountId,
            'walletId' => $this->walletId,
            'salt' => $this->salt,
            'publicKey' => $this->publicKey,
            'mainData' => $this->mainData,
            'keychainData' => $this->keychainData,
            'kdfParams' => $this->kdfParams,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'totpRequired' => $this->totpRequired,
            'usernameProof' => $this->usernameProof,
            'phone' => $this->phone,
            'email' => $this->email
        ]);
    }

    private function setFromJSON($data)
    {
        $data = json_decode($data);
        foreach ($data AS $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function getData()
    {
        $response = (new Command\Builder\FetchObject($this->riak))
            ->atLocation($this->location)
            ->build()
            ->execute();
        if ($response->isSuccess()) {
            if ($response->getObject()) {
                $this->data = $response->getObject()->getData();
            } else {
                throw new Exception('not_found');
            }
        } elseif ($response->isNotFound()) {
            throw new Exception('not_found');
        } else {
            throw new Exception('unknown_error: ' . $response->getStatusCode());
        }
        $this->setFromJSON($this->data);
        $this->lockVersion = $response->getObject()->getVclock();
        return $this;
    }

    public function searchData()
    {
        $index = null;
        if ($this->accountId) {
            $index['name'] = 'accountId_bin';
            $index['value'] = $this->accountId;
        } elseif ($this->email) {
            $index['name'] = 'email_bin';
            $index['value'] = $this->email;
        } elseif ($this->phone) {
            $index['name'] = 'phone_bin';
            $index['value'] = $this->phone;
        }
        if ($index) {
            $response = (new Command\Builder\QueryIndex($this->riak))
                ->buildBucket('wallets')
                ->withIndexName($index['name'])
                ->withScalarValue($index['value'])
                ->withMaxResults(1)
                ->build()
                ->execute()
                ->getResults();
        }

        if ($response) {
            return $response[0];
        } else {
            throw new Exception('not_found');
        }
    }


    public function createWallet()
    {
        try {
            $this->getData();
        } catch (Exception $e) {
            if ($e->getMessage() == 'not_found') {

                //store indexes
                $response = (new \Basho\Riak\Command\Builder\Search\StoreIndex($this->riak))
                    ->withName('accountId_bin')
                    ->build()
                    ->execute();

                $response = (new \Basho\Riak\Command\Builder\Search\StoreIndex($this->riak))
                    ->withName('phone_bin')
                    ->build()
                    ->execute();

                $response = (new \Basho\Riak\Command\Builder\Search\StoreIndex($this->riak))
                    ->withName('email_bin')
                    ->build()
                    ->execute();

                $command = (new Command\Builder\StoreObject($this->riak))
                    ->buildObject($this)
                    ->atLocation($this->location);

                if (isset($this->accountId)) {
                    $command->getObject()->addValueToIndex('accountId_bin', $this->accountId);
                }

                if (isset($this->phone)) {
                    $command->getObject()->addValueToIndex('phone_bin', $this->phone);
                }
                if (isset($this->email)) {
                    $command->getObject()->addValueToIndex('email_bin', $this->email);
                }
                $response = $command->build()->execute();

                if ($response->isSuccess()) {
                    $this->data = $response;
                }
                return $this;
            } else {
                throw new Exception('unknown_error: ' . $e->getMessage());
            }
        }
        if ($this->walletId) {
            throw new Exception('already_taken');
        }
    }

}