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


class JSONValidator extends Validator implements ValidatorInterface {
	/**
	 * validation JSON
	 *
	 * @param Phalcon\Validation $validator
	 * @param string $attribute
	 * @return boolean
	 */
	public function validate(Validation $validator, $attribute) {
		$value = stripcslashes($validator->getValue($attribute));
		$result = json_decode($value);
		if (json_last_error() != JSON_ERROR_NONE) {
			$message = $this->getOption('message');
			if (!$message) {
				$message = 'This is not a JSON!';
			}
			$validator->appendMessage(new Message($message, $attribute, 'JSON'));

			return false;
		}

		return true;
	}
}