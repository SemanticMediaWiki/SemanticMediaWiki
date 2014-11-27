<?php

namespace SMW\Tests\Utils;

use SMW\Tests\Utils\Mock\MockSuperUser;

use ApiResult;
use ApiMain;
use RequestContext;
use WebRequest;
use FauxRequest;

/**
 * Class contains Api related request methods
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class MwApiFactory {

	/**
	 * @param array $params
	 *
	 * @return ApiMain
	 */
	public function newApiMain( array $params ) {
		return new ApiMain( $this->newRequestContext( $params ), true );
	}

	/**
	 * @param array $params
	 *
	 * @return ApiResult
	 */
	public function newApiResult( array $params ) {
		return new ApiResult( $this->newApiMain( $params ) );
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
	public function doApiRequest( array $params ) {

		$api = $this->newApiMain( $params );
		$api->execute();

		return $api->getResultData();
	}

	private function newRequestContext( $request = array() ) {

		$context = new RequestContext();

		if ( $request instanceof WebRequest ) {
			$context->setRequest( $request );
		} else {
			$context->setRequest( new FauxRequest( $request, true ) );
		}

		$context->setUser( new MockSuperUser() );

		return $context;
	}

}
