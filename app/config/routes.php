<?php

use Phalcon\Mvc\Micro\Collection as MicroCollection;
use \SWP\Services\ResponseService;

$app->notFound(function() {
	return ResponseService::prepareResponse("no_content", 204);
});

if (class_exists("IndexController")) {
    $indexController = new IndexController();
    $app->get('/', [$indexController, 'indexAction']);
}

if (class_exists("WalletsController")) {
	$v2 = new MicroCollection();
	$v2->setHandler(new WalletsController());
	$v2->setPrefix('/v2/wallets/');

	//create wallet
	$v2->post('create', "createAction");

	//show_login_params
	$v2->post('show_login_params', "getLoginParamsAction");
	$v2->post('show', "showAction");
	$v2->post('update', "updateAction");
	$v2->post('updatePassword', "updatePasswordAction");

	//delete accounts
	$v2->get('delete_accounts', "deleteAccountsAction");

	//getWalletData
	$v2->post('get_wallet_data', "getWalletDataAction");

    //check login exist
	$v2->post('is_login_exist', "isLoginExistAction");

	$app->mount($v2);
}

if (class_exists("KdfparamsController")) {
	$controller = new KdfparamsController();
	$app->get('/v2/kdf_params', [$controller, 'showAction']);
}