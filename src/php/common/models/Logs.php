<?php

namespace App\Models;

use Exception;
use Phalcon\DI;

class Logs
{
    const RIAK_BUCKET = 'log';

    /** @var  Log record level */
    public $Level;

    /** @var  Service name */
    public $Service;

    /** @var  Const for riak */
    public $_type;

    /** @var  Log record message */
    public $Message;

    /** @var  Log record fields in json */
    public $Fields;

    /** @var  Log record timestamp */
    public $Timestamp;

    /** @var array Fields which will be saved to DB */
    static $fields_to_save = [
        'Level',
        'Service',
        'Fields',
        'Message',
        'Timestamp',
        '_type',
    ];

    public static function getLevels() {
        return [
            \Monolog\Logger::DEBUG     => 'debug',
            \Monolog\Logger::INFO      => 'info',
            \Monolog\Logger::NOTICE    => 'notice',
            \Monolog\Logger::WARNING   => 'warning',
            \Monolog\Logger::ERROR     => 'error',
            \Monolog\Logger::CRITICAL  => 'critical',
            \Monolog\Logger::ALERT     => 'alert',
            \Monolog\Logger::EMERGENCY => 'emergency',
        ];
    }

    public function __construct()
    {
        $this->id = self::generateId();
    }

    public function save()
    {
        $this->validate();
        $riak = DI::getDefault()->getRiak();

        return $riak->set(self::RIAK_BUCKET, $this->id, $this->pickProperties(self::$fields_to_save));
    }

    private function validate()
    {
        if (empty($this->id)) {
            throw new Exception('Bad param: id');
        }

        if (empty($this->Level)) {
            throw new Exception('Bad param: Level');
        }

        if (empty($this->_type)) {
            throw new Exception('Bad param: _type');
        }

        if (empty($this->Service)) {
            throw new Exception('Bad param: Service');
        }

        if (empty($this->Fields)) {
            throw new Exception('Bad param: Fields');
        }

        if (empty($this->Message)) {
            throw new Exception('Bad param: Message');
        }

        if (empty($this->Timestamp)) {
            throw new Exception('Bad param: Timestamp');
        }
    }

    public static function load($id)
    {
        if (empty($id)) {
            return false;
        }

        $riak = DI::getDefault()->getRiak();
        $props = $riak->get(self::RIAK_BUCKET, $id);
        if (empty($props)) {
            return false;
        }

        $record = new self($id);
        $record->fillProperties($props, self::$fields_to_save);

        return $record;
    }

    public function pickProperties($fields)
    {
        $save = [];

        if (empty($fields) || !is_array($fields)) {
            return $save;
        }

        foreach ($fields as $field) {
            if (property_exists($this, $field)) {
                $save[$field] = $this->$field;
            }
        }

        return $save;
    }

    public function fillProperties($data, array $allowed_only = [])
    {
        if (!empty($data) && is_array($data)) {
            foreach ($data as $field => $value) {
                if (property_exists($this, $field) && (empty($allowed_only) || in_array($field, $allowed_only))) {
                    $this->$field = $value;
                }
            }
        }
    }

    public static function generateId()
    {
        return time() . '-' . mt_rand();
    }
}