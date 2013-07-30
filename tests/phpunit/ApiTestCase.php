<?php

namespace SMW\Test;

use ApiResult;
use ApiMain;

/**
 * Class contains Api related request methods
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Class contains Api related request methods
 *
 * @ingroup Api
 */
abstract class ApiTestCase extends SemanticMediaWikiTestCase {

	/**
	 * Returns ApiMain
	 *
	 * @param array $params
	 *
	 * @return ApiMain
	 */
	protected function getApiMain( array $params ) {
		return new ApiMain( $this->newContext( $params ), true );
	}

	/**
	 * Returns ApiResult
	 *
	 * @param array $params
	 *
	 * @return ApiResult
	 */
	protected function getApiResult( array $params ) {
		return new ApiResult( $this->getApiMain( $params ) );
	}

	/**
	 * Returns Api results
	 *
	 * The returned value is an array containing
	 * - the result data (array)
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	protected function doApiRequest( array $params ) {
		$api = $this->getApiMain( $params );
		$api->execute();
		return $api->getResultData();
	}

}
