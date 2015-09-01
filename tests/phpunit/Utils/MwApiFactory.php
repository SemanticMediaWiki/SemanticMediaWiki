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

		if ( version_compare( $GLOBALS['wgVersion'], '1.25', '<' ) ) {
			return new ApiResult( $this->newApiMain( $params ) );
		}

		$result = new ApiResult( 5 );

		$errorFormatter = new \ApiErrorFormatter_BackCompat( $result );
		$result->setErrorFormatter( $errorFormatter );

		return $result;
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

		if ( method_exists( $api->getResult(), 'getResultData' ) ) {
			return $api->getResult()->getResultData();
		}

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
