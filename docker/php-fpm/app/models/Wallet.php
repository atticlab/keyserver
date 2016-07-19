<?php

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
    private $riak;
    /**
     * @var Basho\Riak\Object
     */
    private $object;
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


    /**
     * Loads data from RIAK and populates object with values
     *
     * @return $this
     **/
    public function loadData()
    {
        $response = (new Command\Builder\FetchObject($this->riak))
            ->atLocation($this->location)
            ->build()
            ->execute();

        if ($response->isSuccess()) {
            $this->object = $response->getObject();
        } elseif ($response->isNotFound()) {
            throw new Exception('not_found');
        } else {
            throw new Exception('unknown_error: ' . $response->getStatusCode());
        }

        if (empty($this->object)) {
            throw new Exception('not_found');
        }

        $this->setFromJSON($this->object->getData());
        $this->lockVersion = $this->object->getVclock();

        return $this;
    }

    public static function find($riak, $params)
    {
        $index = [];

        if (!empty($params['accountId'])) {
            $index['name'] = 'accountId_bin';
            $index['value'] = $params['accountId'];
        } elseif (!empty($params['email'])) {
            $index['name'] = 'email_bin';
            $index['value'] = $params['email'];
        } elseif (!empty($params['phone'])) {
            $index['name'] = 'phone_bin';
            $index['value'] = $params['phone'];
        }

        if (!empty($index)) {
            $response = (new Command\Builder\QueryIndex($riak))
                ->buildBucket('wallets')
                ->withIndexName($index['name'])
                ->withScalarValue($index['value'])
                ->withMaxResults(1)
                ->build()
                ->execute()
                ->getResults();
        }

        if (empty($response)) {
            throw new Exception('not_found');
        }

        return $response[0];
    }


    public function createWallet()
    {
        try {
            $this->loadData();
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

                if (!$response->isSuccess()) {
                    throw new Exception('unknown_error');
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

    public function update()
    {
        if (empty($this->object)) {
            throw new Exception('object_not_loaded');
        }

        $this->updatedAt = date('D M d Y H:i:s O');

        $save = $this->object->setData(json_encode($this));
        $updateCommand = (new Command\Builder\StoreObject($this->riak))
            ->withObject($save)
            ->atLocation($this->location)
            ->build();

        $result = $updateCommand->execute();
        if (!$result->isSuccess()) {
            throw new Exception("cannot_update");
        }

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

        if (!$response->isSuccess()) {
            throw new Exception('unknown_error');
        }

        return $result;
    }

    private function setFromJSON($data)
    {
        $data = json_decode($data);
        foreach ($data AS $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}