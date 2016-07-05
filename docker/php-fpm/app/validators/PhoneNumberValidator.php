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
     * validation phone number (+XXXXXXXXXXXX)
     *
     * @param Phalcon\Validation $validator
     * @param string $attribute
     * @return boolean
     */
    public function validate(Validation $validator, $attribute)
    {
        if (($message = $this->getOption('allowEmpty')) &&
            (!$validator->getValue($attribute))
        ) {
            return true;
        }

        $value = preg_replace('/[^0-9\+]/', '', $validator->getValue($attribute));

        if (strlen($value) < 5 || strlen($value) > 20) {
            $message = $this->getOption('message');
            if (!$message) {
                $message = 'Very long phone number!';
            }
            $validator->appendMessage(new Message($message, $attribute, 'phone'));

            return false;
        }
        ///^(\+)(\d{5,20}+)$/
        if (!preg_match('/^(\d{5,20}+)$/', $value)) {
            $message = $this->getOption('message');
            if (!$message) {
                $message = 'phone_bad_format';
            }
            $validator->appendMessage(new Message($message, $attribute, 'phone'));

            return false;
        }
        return true;
    }
}