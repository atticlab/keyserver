<?php

namespace SWP\Services;

use \Phalcon\Http\Response;

abstract class ResponseService {

	/**
	 * @param $content -- a json_encode() content to show
	 * @param int $statusCode -- HTTP status code
	 * @return Response
	 */
	public static function prepareResponse($content, $statusCode = 200)
	{
		$response = new Response();

		# Set CORS headers
		$response->setHeader('Access-Control-Allow-Origin', '*');
    	$response->setHeader('Access-Control-Allow-Credentials', 'true');
    	$response->setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
    	$response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
		$response->setHeader('Content-Type', 'application/json');

		$response->setStatusCode($statusCode);
		$response->setContent($content);

		return $response;
	}
}