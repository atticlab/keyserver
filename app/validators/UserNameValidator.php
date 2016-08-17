<?php
/**
 * Created by PhpStorm.
 * User: skorzun
 * Date: 17.06.16
 * Time: 13:34
 */

namespace SWP\Validators;

use Phalcon\Validation;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength as StringLength;

class UserNameValidator extends Validation {
	public function initialize() {
		$this->add(
			'username',
			new PresenceOf(
				array(
					'message' => 'The username is required'
				)
			)
		);

		$this->add(
			'username',
			new StringLength(array(
                 'max' => 255,
                 'min' => 3,
                 'messageMaximum' => 'invalid_username',
                 'messageMinimum' => 'invalid_username)'
             ))
		);

	}

}