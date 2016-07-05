<?php
/**
 * Created by PhpStorm.
 * User: skorzun
 * Date: 17.06.16
 * Time: 12:31
 */

namespace SWP\Services;

use \Phalcon\Http\Response;

abstract class ResponseService {

	/**
	 * @param $content -- a json_encode() content to show
	 * @param int $statusCode -- HTTP status code
	 * @return Response
	 */
	public static function prepareResponse($content, $statusCode = 200) {
		$response = new Response();
		$response->setStatusCode($statusCode);
		$response->setHeader('Content-Type', 'application/json');
		$response->setHeader('Access-Control-Allow-Origin', '*');
		$response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
		$response->setContent($content);
		return $response;
	}
}