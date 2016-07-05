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


class ByteLengthValidator extends Validator implements ValidatorInterface {
	/**
	 * validation JSON
	 *
	 * @param Phalcon\Validation $validator
	 * @param string $attribute
	 * @return boolean
	 */
	public function validate(Validation  $validator, $attribute) {
		$value = $validator->getValue($attribute);
		$length = $this->getOption('length');
		$decryptedValue = base64_decode($value);
		if (strlen($decryptedValue) != $length) {
			$message = $this->getOption('message');
			if (!$message) {
				$message = 'This is not valid length!';
			}
			$validator->appendMessage(new Message($message, $attribute, 'length'));

			return false;
		}

		return true;
	}
}