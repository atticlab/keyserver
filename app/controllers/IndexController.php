<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use SWP\Services\ResponseService;

class IndexController extends Controller
{
    public function indexAction()
    {
        $apiExlorerData = [
            $this->request->getHttpHost().'/v2/wallets/create' => [
                'method' => 'post',
                'params' => [
                    'username' => [
                        'description' => 'Username. Length - 3-255 symbols',
                        'type' => 'string',
                        'required' => true
                    ],
                    'walletId' => [
                        'description' => 'Wallet ID. Length - 32 symbols',
                        'type' => 'string',
                        'required' => true
                    ],
                    'accountId' => [
                        'description' => 'Account ID. Length - 56 symbols',
                        'type' => 'string',
                        'required' => true
                    ],
                    'salt' => [
                        'description' => 'Salt. Length - 16 symbols',
                        'type' => 'string',
                        'required' => true
                    ],
                    'kdfParams' => [
                        'description' => 'KdfParams. Must be a valid JSON',
                        'type' => 'string',
                        'required' => true
                    ],
                    'publicKey' => [
                        'description' => 'Public Key. Length - 32 symbols',
                        'type' => 'string',
                        'required' => true
                    ],
                    'mainData' => [
                        'description' => 'Main data',
                        'type' => 'string',
                        'required' => true
                    ],
                    'keychainData' => [
                        'description' => 'Keychain Data',
                        'type' => 'string',
                        'required' => true
                    ]
                ],
                'description' => 'Create wallet'
            ],
            $this->request->getHttpHost().'/v2/wallets/show_login_params' => [
                'method' => 'post',
                'params' => [
                    'username' => [
                        'description' => 'Username. Length - 3-255 symbols',
                        'type' => 'string',
                        'required' => true
                    ]
                ],
                'description' => 'Show login params by username'
            ],
            $this->request->getHttpHost().'/v2/wallets/show' => [
                'method' => 'post',
                'params' => [
                    'username' => [
                        'description' => 'Username. Length - 3-255 symbols',
                        'type' => 'string',
                        'required' => true
                    ],
                    'walletId' => [
                        'description' => 'Wallet ID. Length - 32 symbols',
                        'type' => 'string',
                        'required' => true
                    ],
                ],
                'description' => 'Show wallet data by username and wallet ID'
            ],
            $this->request->getHttpHost().'/v2/wallets/update' => [
                'method' => 'post',
                'params' => [
                    'phone' => [
                        'description' => 'Phone. Must be a valid mobile phone number. Length - 10 symbols',
                        'type' => 'string',
                        'required' => false
                    ],
                    'email' => [
                        'description' => 'User email. Must be a valid email address',
                        'type' => 'string',
                        'required' => false
                    ],
                    'HDW' => [
                        'description' => 'HDW',
                        'type' => 'string',
                        'required' => false
                    ],
                ],
                'description' => 'Update wallet by phone, email or HDW'
            ],
            $this->request->getHttpHost().'/v2/wallets/updatePassword' => [
                'method' => 'post',
                'params' => [
                    'walletId' => [
                        'description' => 'Wallet ID. Length - 32 symbols',
                        'type' => 'string',
                        'required' => true
                    ],
                    'salt' => [
                        'description' => 'Salt. Length - 16 symbols',
                        'type' => 'string',
                        'required' => true
                    ],
                    'kdfParams' => [
                        'description' => 'KdfParams. Must be a valid JSON',
                        'type' => 'string',
                        'required' => true
                    ],
                    'mainData' => [
                        'description' => 'Main data',
                        'type' => 'string',
                        'required' => true
                    ],
                    'keychainData' => [
                        'description' => 'Keychain Data',
                        'type' => 'string',
                        'required' => true
                    ],
                    'lockVersion' => [
                        'description' => 'Lock version',
                        'type' => 'int',
                        'required' => true
                    ],
                ],
                'description' => 'Update wallet password'
            ],
//            $this->request->getHttpHost().'/v2/wallets/delete_accounts' => [
//                'method' => 'get',
//                'params' => [
//
//                ],
//                'description' => 'Deletes account '
//            ],
            $this->request->getHttpHost().'/v2/wallets/get_wallet_data' => [
                'method' => 'post',
                'params' => [
                    'accountId' => [
                        'description' => 'Account ID. Length - 56 symbols',
                        'type' => 'string',
                        'required' => false
                    ],
                    'email' => [
                        'description' => 'Email. Must be a valid email address',
                        'type' => 'string',
                        'required' => false
                    ],
                    'phone' => [
                        'description' => 'Phone. Must be a valid mobile phone number. Length - 10 symbols',
                        'type' => 'string',
                        'required' => false
                    ],
                    'uniqueId' => [
                        'description' => 'Unique ID',
                        'type' => 'string',
                        'required' => false
                    ],
                    'login' => [
                        'description' => 'Login',
                        'type' => 'string',
                        'required' => false
                    ]
                ],
                'description' => 'Get wallet data by account ID, email, phone, unique ID or login'
            ]
        ];

        return ResponseService::prepareResponse(json_encode($apiExlorerData));
    }


}