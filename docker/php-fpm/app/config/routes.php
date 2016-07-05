<?php
use Phalcon\Mvc\Micro\Collection as MicroCollection;
use \SWP\Services\ResponseService;

$app->get('/', function () {
	return ResponseService::prepareResponse("Welcome to SmartMoney Wallet!", 200);
});
$app->notFound(function () {
	return ResponseService::prepareResponse("no_content", 204);
});

if (class_exists("WalletsController")) {
	$v2 = new MicroCollection();
	$v2->setHandler(new WalletsController());
	$v2->setPrefix('/v2/wallets/');

	//create wallet
	$v2->post('create', "createAction");

	//show_login_params
	$v2->post('show_login_params', "getLoginParamsAction");

	//show
	$v2->post('show', "showAction");

	//getWalletData
	$v2->post('get_wallet_data', "getWalletDataAction");

	$app->mount($v2);
}