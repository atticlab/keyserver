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

class WalletIdValidator extends Validation {
	public function initialize() {
		$this->add(
			'walletId',
			new PresenceOf(
				array(
					'message' => 'The walletId is required'
				)
			)
		);

		$this->add(
			'walletId',
			new ByteLengthValidator(array(
                'length' => 32,
                'message' => 'The walletId must be an 32 bytes length'
            ))
		);

	}

}