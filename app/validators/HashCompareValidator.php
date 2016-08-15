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


class HashCompareValidator extends Validator implements ValidatorInterface {
	/**
	 * validation JSON
	 *
	 * @param Phalcon\Validation $validator
	 * @param string $attribute
	 * @return boolean
	 */
	public function validate(Validation  $validator, $attribute) {
		$value = $validator->getValue($attribute);
		$hash = $this->getOption('with');
		if ($hash != hash('sha1', $value)) {
			$message = $this->getOption('message');
			if (!$message) {
				$message = 'This is not valid hash!';
			}
			$validator->appendMessage(new Message($message, $attribute, 'HASH'));

			return false;
		}

		return true;
	}
}