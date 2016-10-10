<?php

namespace SWP\Validators;

use Phalcon\Validation;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;

class UpdatePasswordValidator extends Validation
{
    public function initialize()
    {
        $this->add('walletId', new ByteLengthValidator([
            'length' => 32,
            'message' => 'The walletId must be an 32 bytes length'
        ]));

        $this->add('salt', new ByteLengthValidator([
            'length' => 16,
            'message' => 'The salt must be an 16 bytes length'
        ]));

        $this->add('kdfParams', new JSONValidator([
            'message' => 'The kdfParams is not a JSON'
        ]));


        $this->add('mainData', new PresenceOf(['message' => 'missing_field']));
        $this->add('keychainData', new PresenceOf(['message' => 'missing_field']));
        $this->add('lockVersion', new PresenceOf(['message' => 'missing_field']));
    }
}