<?php
/**
 * Created by PhpStorm.
 * User: skorzun
 * Date: 17.06.16
 * Time: 13:54
 */

namespace SWP\Validators;

use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation as Validation;


class PhoneNumberValidator extends Validator implements ValidatorInterface
{
    /**
     * Used to validate mobile phone number length
     **/
    const PHONE_LENGTH = 10;

    /**
     * validation phone number (XXXXXXXXXX)
     *
     * @param Phalcon\Validation $validator
     * @param string $attribute
     * @return boolean
     */
    public function validate(Validation $validator, $attribute)
    {
        $value = $validator->getValue($attribute);
        if (!$value && $this->getOption('allowEmpty')) {
            return true;
        }

        if (!preg_match('/^(\d){'.self::PHONE_LENGTH.'}$/', $value)) {
            $message = $this->getOption('message') ?: 'phone_bad_format';
            $validator->appendMessage(new Message($message, $attribute, 'phone'));
            return false;
        }

        return true;
    }
}