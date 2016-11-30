<?php

use Phalcon\Mvc\Controller;
use Phalcon\Di;
use Phalcon\Validation;

use \SWP\Models\Wallet;
use \SWP\Services\ResponseService;
use \SWP\Services\SignedJsonService;
use \SWP\Validators\UserNameValidator;
use \SWP\Validators\UpdatePasswordValidator;
use \SWP\Validators\CreateWalletValidator;
use \SWP\Validators\WalletIdValidator;
use \SWP\Validators\PhoneNumberValidator;

class WalletsController extends Controller
{
    /**
     * Used to validate the http auth header
     **/
    const AUTH_HEADER_REGEX = 'STELLAR-WALLET-V2';

    /**
     * This is getLoginParams action,
     * that takes an username in post
     * @return \Phalcon\Http\Response
     */
    public function getLoginParamsAction()
    {
        $params = json_decode(file_get_contents('php://input'), true);
        $validation = new UserNameValidator();
        $validationResult = $this->doValidation($validation, $params);
        if ($validationResult['status'] != "success") {
            $this->logger->info('Login params not received', ['Path' => '/v2/wallets/show_login_params', 'Validation error:' => $validationResult]);
            return ResponseService::prepareResponse(json_encode($validationResult));
        }

        $wallet = new Wallet($this->riakDB, $params['username']);
        try {
            $result = $wallet->loadData();
        } catch (Exception $e) {
            $preparedData['status'] = "fail";
            $preparedData['code'] = $e->getMessage();
            $this->logger->error('Data not loaded from DB', ['Path' => '/v2/wallets/show_login_params', 'Error:' => $preparedData]);
            return ResponseService::prepareResponse(json_encode($preparedData));
        }

        $preparedData['username'] = $result->username;
        $preparedData['salt'] = $result->salt;
        $preparedData['kdfParams'] = $result->kdfParams;
        $preparedData['totpRequired'] = $result->totpRequired;
        $this->logger->debug('Login params received', ['Path' => '/v2/wallets/show_login_params', 'Data:' => $preparedData]);
        return ResponseService::prepareResponse(json_encode($preparedData));
    }

    /**
     * This is Create Wallet action
     * @return \Phalcon\Http\Response
     */
    public function createAction()
    {
        $params = json_decode(file_get_contents('php://input'), true);
        if (!$params) {
            $params = $this->request->getPost();
        }

        $validation = new CreateWalletValidator();
        $validationResult = $this->doValidation($validation, $params);
        if ($validationResult['status'] != "success") {
            $this->logger->info('Wallet not created', ['Path' => '/v2/wallets/create', 'Validation error:' => $validationResult]);
            return ResponseService::prepareResponse(json_encode($validationResult));
        }

        $wallet = new Wallet($this->riakDB, $params['username']);
        $wallet->walletId = $params['walletId'];
        $wallet->accountId = @$params['accountId'];
        $wallet->salt = $params['salt'];
        $wallet->kdfParams = stripcslashes($params['kdfParams']);
        $wallet->publicKey = $params['publicKey'];
        $wallet->mainData = $params['mainData'];
        $wallet->keychainData = $params['keychainData'];
        $wallet->usernameProof = null;
        $wallet->createdAt = date('D M d Y H:i:s O');
        $wallet->updatedAt = $wallet->createdAt;

        try {
            $wallet->createWallet();
        } catch (Exception $e) {
            $preparedData['status'] = "fail";
            $preparedData['code'] = $e->getMessage();
            $this->logger->error('Wallet not created', ['Path' => '/v2/wallets/create', 'Error:' => $preparedData]);
            return ResponseService::prepareResponse(json_encode($preparedData));
        }

        $preparedData['status'] = "success";

        $this->logger->debug('Wallet created successfully', ['Path' => '/v2/wallets/create', 'Data:' => $preparedData]);
        return ResponseService::prepareResponse(json_encode($preparedData));
    }

    /**
     * This is showAction that shows Wallet data by
     * username and walletId
     * @return \Phalcon\Http\Response
     */
    public function showAction()
    {
        $params = json_decode(file_get_contents('php://input'), true);
        $validation = new UserNameValidator();
        $validationResult = $this->doValidation($validation, $params);
        if ($validationResult['status'] != "success") {
            $this->logger->info('Wallet not showed', ['Path' => '/v2/wallets/show', 'Validation error:' => $validationResult]);
            return ResponseService::prepareResponse(json_encode($validationResult));
        }

        $validation = new WalletIdValidator();
        $validationResult = $this->doValidation($validation, $params);
        if ($validationResult['status'] != "success") {
            $this->logger->info('Wallet not showed', ['Path' => '/v2/wallets/show', 'Validation error:' => $validationResult]);
            return ResponseService::prepareResponse(json_encode($validationResult));
        }

        $wallet = new Wallet($this->riakDB, $params['username']);
        $preparedData = [];

        try {
            $result = $wallet->loadData();
        } catch (Exception $e) {
            $preparedData['status'] = "fail";
            $preparedData['code'] = $e->getMessage();
            $this->logger->error('Data not loaded from DB', ['Path' => '/v2/wallets/show', 'Error:' => $preparedData]);
            return ResponseService::prepareResponse(json_encode($preparedData));
        }

        //walletID check
        if ($params['walletId'] != $result->walletId) {
            $preparedData['status'] = "fail";
            $preparedData['code'] = "wallet_not_found";
            $this->logger->error('Wallet not found', ['Path' => '/v2/wallets/show', 'Error:' => $preparedData]);
            return ResponseService::prepareResponse(json_encode($preparedData));
        }

        $preparedData['lockVersion'] = $result->lockVersion;
        $preparedData['mainData'] = $result->mainData;
        $preparedData['keychainData'] = $result->keychainData;
        $preparedData['updatedAt'] = $result->updatedAt;
        $preparedData['email'] = $result->email;
        $preparedData['phone'] = $result->phone;
        $preparedData['uniqueId'] = $result->uniqueId;
        $preparedData['HDW'] = $result->HDW;

        $this->logger->debug('Wallet received successfully', ['Path' => '/v2/wallets/show', 'Data:' => $preparedData]);
        return ResponseService::prepareResponse(json_encode($preparedData, JSON_UNESCAPED_SLASHES));
    }

    public function updateAction()
    {
        $update = false;

        try {
            $wallet = $this->getWalletFromAuth();
        } catch (Exception $e) {
            $preparedData = [
                'status' => 'fail',
                'code' => $e->getMessage()
            ];

            $this->logger->info('Wallet not received. 401 Unauthorized', ['Path' => '/v2/wallets/update', 'Info:' => $preparedData]);
            return ResponseService::prepareResponse(json_encode($preparedData), 401);
        }

        $params = json_decode(file_get_contents('php://input'), true);

        if (!empty($params['phone']) && $params['phone'] != $wallet->phone) {
            $validation = new Validation();
            $validation->add('phone', new PhoneNumberValidator);
            $messages = $validation->validate($params);

            if (count($messages)) {
                $preparedData = [
                    'status' => 'fail',
                    'code' => 'bad_param_phone'
                ];

                $this->logger->info('Wallet not updated. 401 Unauthorized', ['Path' => '/v2/wallets/update', 'Validation error:' => $preparedData]);
                return ResponseService::prepareResponse(json_encode($preparedData), 401);
            }

            // Check if this phone exists
            try {
                Wallet::find($this->riakDB, ['phone' => $params['phone']]);
                $preparedData = [
                    'status' => 'fail',
                    'code' => 'phone_exists'
                ];

                $this->logger->info('Phone exists. 401 Unauthorized', ['Path' => '/v2/wallets/update', 'Validation error:' => $preparedData]);
                return ResponseService::prepareResponse(json_encode($preparedData), 401);
            } catch (Exception $e) {
                if ($e->getMessage() == 'not_found') {
                    $update = true;
                    $wallet->phone = $params['phone'];
                }
            }
        }

        if (!empty($params['email']) && $params['email'] != $wallet->email) {
            if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
                $preparedData = [
                    'status' => 'fail',
                    'code' => 'bad_param_email'
                ];

                $this->logger->info('Bad email. 401 Unauthorized', ['Path' => '/v2/wallets/update', 'Validation error:' => $preparedData]);
                return ResponseService::prepareResponse(json_encode($preparedData), 401);
            }

            // Check if this email exists
            try {
                Wallet::find($this->riakDB, ['email' => $params['email']]);
                $preparedData = [
                    'status' => 'fail',
                    'code' => 'email_exists'
                ];

                $this->logger->info('Phone exists. 401 Unauthorized', ['Path' => '/v2/wallets/update', 'Validation error:' => $preparedData]);
                return ResponseService::prepareResponse(json_encode($preparedData), 401);
            } catch (Exception $e) {
                if ($e->getMessage() == 'not_found') {
                    $update = true;
                    $wallet->email = $params['email'];
                }
            }
        }

        if (!empty($params['HDW']) && $params['HDW'] != $wallet->HDW) {
            $update = true;
            $wallet->HDW = $params['HDW'];
        }

        $preparedData = [
            'status' => 'fail',
            'code' => 'nothing_to_update'
        ];

        // TODO update lock version
        if ($update) {
            try {
                $result = $wallet->update();
                $preparedData = [
                    'status' => 'success',
                    'email' => $wallet->email,
                    'phone' => $wallet->phone,
                    'HDW' => $wallet->HDW,
                    'newLockVersion' => '0'
                ];
                $this->logger->debug('Wallet updated', ['Path' => '/v2/wallets/update', 'Data:' => $preparedData]);
            } catch (Exception $e) {
                $preparedData = [
                    'status' => 'fail',
                    'code' => $e->getMessage()
                ];
                $this->logger->error('Wallet not updated', ['Path' => '/v2/wallets/update', 'Error:' => $preparedData]);
            }
        } else {
            $this->logger->error('Wallet not updated', ['Path' => '/v2/wallets/update', 'Error:' => $preparedData]);
        }

        return ResponseService::prepareResponse(json_encode($preparedData));
    }

    public function updatePasswordAction()
    {
        try {
            $wallet = $this->getWalletFromAuth();
        } catch (Exception $e) {
            $preparedData = [
                'status' => 'fail',
                'code' => $e->getMessage()
            ];

            $this->logger->info('Wallet not received. 401 Unauthorized', ['Path' => '/v2/wallets/updatePassword', 'Info:' => $preparedData]);
            return ResponseService::prepareResponse(json_encode($preparedData), 401);
        }

        $params = json_decode(file_get_contents('php://input'), true);
        $validation = new UpdatePasswordValidator();
        $validationResult = $this->doValidation($validation, $params);
        if ($validationResult['status'] != "success") {
            $this->logger->info('Wallet password not updated', ['Path' => '/v2/wallets/updatePassword', 'Validation error:' => $validationResult]);
            return ResponseService::prepareResponse(json_encode($validationResult), 401);
        }

        $wallet->walletId = $params['walletId'];
        $wallet->salt = $params['salt'];
        $wallet->kdfParams = $params['kdfParams'];
        $wallet->mainData = $params['mainData'];
        $wallet->keychainData = $params['keychainData'];

        // TODO update lock version
        try {
            $result = $wallet->update();
            $preparedData = [
                'status' => 'success',
                'newLockVersion' => '0'
            ];
            $this->logger->debug('Wallet password updated', ['Path' => '/v2/wallets/updatePassword', 'Data:' => $preparedData]);
        } catch (Exception $e) {
            $preparedData = [
                'status' => 'fail',
                'code' => $e->getMessage()
            ];
            $this->logger->error('Wallet password not updated', ['Path' => '/v2/wallets/updatePassword', 'Data:' => $preparedData]);
        }

        return ResponseService::prepareResponse(json_encode($preparedData));
    }

    public function getWalletDataAction()
    {
        $params = json_decode(file_get_contents('php://input'), true);
        if (!$params) {
            $params = $this->request->getPost();
        }

        try {
            $result = Wallet::find($this->riakDB, $params);
            $wallet = new Wallet($this->riakDB, $result);
            $result = $wallet->loadData();
            $this->logger->debug('Wallet data loaded from DB', ['Path' => '/v2/wallets/get_wallet_data', 'Data:' => $result]);
        } catch (Exception $e) {
            $preparedData['status'] = "fail";
            $preparedData['code'] = $e->getMessage();
            $this->logger->error('Wallet data not loaded from DB', ['Path' => '/v2/wallets/get_wallet_data', 'Data:' => $preparedData]);
            return ResponseService::prepareResponse(json_encode($preparedData));
        }

        $preparedData['username'] = $result->username;
        $preparedData['accountId'] = $result->accountId;
        $preparedData['phone'] = $result->phone;
        $preparedData['email'] = $result->email;
        $preparedData['uniqueId'] = $result->uniqueId;

        $this->logger->debug('Wallet data received', ['Path' => '/v2/wallets/get-wallet_data', 'Data:' => $preparedData]);
        return ResponseService::prepareResponse(json_encode($preparedData));
    }

    /**
     * Check auth headers and return wallet object
     *
     * @return SWP\Models\Wallet
     **/
    private function getWalletFromAuth()
    {
        $auth_header = $this->request->getHeader('Authorization');
        if (strpos($auth_header, self::AUTH_HEADER_REGEX) === false) {
            throw new Exception("invalid_signature");
        }

        $auth_header = ltrim($auth_header, self::AUTH_HEADER_REGEX);
        $auth = [];

        foreach (explode(',', $auth_header) as $var) {
            $var = explode('=', $var, 2);
            if (empty($var[0]) || empty($var[1])) {
                throw new Exception("invalid_signature");
            }

            $auth[trim($var[0])] = trim($var[1], '"');
        }

        $validation = new UserNameValidator();
        $messages = $validation->validate($auth);
        if (count($messages)) {
            throw new Exception("invalid_username");
        }

        $validation = new WalletIdValidator();
        $messages = $validation->validate($auth);
        if (count($messages)) {
            throw new Exception("invalid_signature");
        }

        $wallet = new Wallet($this->riakDB, $auth['username']);
        $wallet->loadData();

        if ($auth['walletId'] != $wallet->walletId) {
            throw new Exception("invalid_signature");
        }

        $request_body = file_get_contents('php://input');

        // Check signature
        $is_signed = ed25519_sign_open($request_body, base64_decode($wallet->publicKey), base64_decode($auth['signature']));
        if (!$is_signed) {
            throw new Exception("invalid_signature");
        }

        return $wallet;
    }

    /**
     * This is a service function for simplifying validation
     * @param $validation -- a Validation object
     * @param $params -- an assoc array of params
     * @return mixed -- an assoc array of return params
     */
    private function doValidation($validation, $params)
    {
        $messages = $validation->validate($params);
        if (count($messages)) {
            foreach ($validation->getMessages() as $message) {
                $preparedData['status'] = "fail";
                $preparedData['code'] = $message->getMessage();
                $preparedData['field'] = $message->getField();
                return $preparedData;
            }
        }
        $preparedData['status'] = "success";
        return $preparedData;
    }

    public function deleteAccountsAction()
    {
        $users = [];
        foreach ($this->config->accountsToDelete as $username) {
            try {
                $wallet = new Wallet($this->riakDB, $username);
                $result = $wallet->delete();
                $users['deleted'][] = $username;
            } catch (Exception $e) {
                $preparedData['status'] = "fail";
                $preparedData['code'] = $e->getMessage();
                return ResponseService::prepareResponse(json_encode($preparedData));
                $users['not_found'][] = $username;
            }
        }
        return ResponseService::prepareResponse(json_encode($users));
    }
}