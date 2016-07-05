<?php

/**
 * Created by PhpStorm.
 * User: skorzun
 * Date: 16.06.16
 * Time: 15:19
 */

use Phalcon\Mvc\Controller;
use Phalcon\Di;
use Phalcon\Validation;

use \SWP\Models\Wallet;
use \SWP\Services\ResponseService;
use \SWP\Validators\UserNameValidator;
use \SWP\Validators\CreateWalletValidator;
use \SWP\Validators\WalletIdValidator;

class WalletsController extends Controller
{
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

        if (!empty($params['phone'])) {
            $wallet->phone = preg_replace('/[^0-9]/', '', $params['phone']);
        }

        if (!empty($params['email'])) {
            $wallet->email = $params['email'];
        }

        $wallet->usernameProof = null;
        $wallet->createdAt = date('D M d Y H:i:s O');
        $wallet->updatedAt = $wallet->createdAt;

        try {
            $result = $wallet->createWallet();
        } catch (Exception $e) {
            $preparedData['status'] = "fail";
            $preparedData['code'] = $e->getMessage();
            return ResponseService::prepareResponse(json_encode($preparedData));
        }

        $result->kdfParams = json_decode($result->kdfParams);

        $preparedData['status'] = "success";
        $preparedData['newLockVersion'] = "0";
        return ResponseService::prepareResponse(json_encode($preparedData));
    }

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
            return ResponseService::prepareResponse(json_encode($validationResult));
        }

        $wallet = new Wallet($this->riakDB, $params['username']);
        try {
            $result = $wallet->getData();
        } catch (Exception $e) {
            $preparedData['status'] = "fail";
            $preparedData['code'] = $e->getMessage();
            return ResponseService::prepareResponse(json_encode($preparedData));
        }

        $preparedData['username'] = $result->username;
        $preparedData['salt'] = $result->salt;
        $preparedData['kdfParams'] = $result->kdfParams;
        $preparedData['totpRequired'] = $result->totpRequired;
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
            return ResponseService::prepareResponse(json_encode($validationResult));
        }
        $validation = new WalletIdValidator();
        $validationResult = $this->doValidation($validation, $params);
        if ($validationResult['status'] != "success") {
            return ResponseService::prepareResponse(json_encode($validationResult));
        }
        $wallet = new Wallet($this->riakDB, $params['username']);
        try {
            $result = $wallet->getData();
        } catch (Exception $e) {
            $preparedData['status'] = "fail";
            $preparedData['code'] = $e->getMessage();
            return ResponseService::prepareResponse(json_encode($preparedData));
        }

        $preparedData['lockVersion'] = $result->lockVersion;
        $preparedData['mainData'] = $result->mainData;
        $preparedData['keychainData'] = $result->keychainData;
        $preparedData['updatedAt'] = $result->updatedAt;

        return ResponseService::prepareResponse(json_encode($preparedData, JSON_UNESCAPED_SLASHES));
    }

    public function getWalletDataAction()
    {
        $params = json_decode(file_get_contents('php://input'), true);
        if (!$params) {
            $params = $this->request->getPost();
        }

        $wallet = new Wallet($this->riakDB, '');
        $wallet->accountId = @$params['accountId'];
        $wallet->email = @$params['email'];
        $wallet->phone = @$params['phone'];
        try {
            $result = $wallet->searchData();
            $wallet = new Wallet($this->riakDB, $result);
            $result = $wallet->getData();
        } catch (Exception $e) {
            $preparedData['status'] = "fail";
            $preparedData['code'] = $e->getMessage();
            return ResponseService::prepareResponse(json_encode($preparedData));
        }

        $preparedData['username'] = $result->username;
        $preparedData['accountId'] = $result->accountId;
        $preparedData['phone'] = $result->phone;
        $preparedData['email'] = $result->email;

        return ResponseService::prepareResponse(json_encode($preparedData));
    }

}