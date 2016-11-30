<?php

namespace SWP\Validators;

use Phalcon\Validation;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength as StringLength;

class CreateWalletValidator extends Validation {
	public function initialize() {
		$this->add(
			'username',
			new PresenceOf(
				array(
					'message' => 'missing_field'
				)
			)
		);

		$this->add(
			'walletId',
			new PresenceOf(
				array(
					'message' => 'missing_field'
				)
			)
		);

		$this->add(
			'salt',
			new PresenceOf(
				array(
					'message' => 'missing_field'
				)
			)
		);

		$this->add(
			'kdfParams',
			new PresenceOf(
				array(
					'message' => 'missing_field'
				)
			)
		);

		$this->add(
			'publicKey',
			new PresenceOf(
				array(
					'message' => 'missing_field'
				)
			)
		);

		$this->add(
			'mainData',
			new PresenceOf(
				array(
					'message' => 'missing_field'
				)
			)
		);

		$this->add(
			'keychainData',
			new PresenceOf(
				array(
					'message' => 'missing_field'
				)
			)
		);

		/*$this->add(
			'username',
			new Email(
				array(
					'message' => 'missing_field'
				)
			)
		);*/

		$this->add(
			'username',
			new StringLength(array(
	             'max' => 255,
	             'min' => 3,
	             'messageMaximum' => 'The username is not 3-255 characters (too big)',
	             'messageMinimum' => 'The username is not 3-255 characters (too small)'
	         ))
		);

		$this->add(
			'salt',
			new ByteLengthValidator(array(
	            'length' => 16,
	            'message' => 'The salt must be an 16 bytes length'
	        ))
		);

		$this->add(
			'walletId',
			new ByteLengthValidator(array(
				                        'length' => 32,
				                        'message' => 'The walletId must be an 32 bytes length'
			                        ))
		);

		$this->add(
			'publicKey',
			new ByteLengthValidator(array(
				                        'length' => 32,
				                        'message' => 'The publicKey must be an 32 bytes length'
			                        ))
		);

		$this->add(
			'kdfParams',
			new JSONValidator(array(
				                  'message' => 'The kdfParams is not a JSON'
			                  ))
		);

		$this->add(
			'phone',
			new PhoneNumberValidator(
				array(
					'message' => 'phone_bad_format',
					"allowEmpty" => true
				)
			)
		);

		$this->add(
			'email',
			new Email(
				array(
					'message' => 'missing_field',
					"allowEmpty" => true
				)
			)
		);

		/*$this->add(
			'mainData',
			new HashCompareValidator(array(
				                         'message' => 'The mainData is corrupt',
				                         'with' => 'mainDataHash'
			                         ))
		);

		$this->add(
			'keychainData',
			new HashCompareValidator(array(
				                         'message' => 'The keychainData is corrupt',
				                         'with' => 'keychainDataHash'
			                         ))
		);*/
	}

}