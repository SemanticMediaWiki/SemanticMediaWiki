<?php

namespace SMW\Tests\Utils;

use MediaWiki\Api\ApiErrorFormatter;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiResult;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Request\WebRequest;
use SMW\Tests\Utils\Mock\MockSuperUser;

/**
 * Class contains Api related request methods
 *
 * @license GPL-2.0-or-later
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
		$result = new ApiResult( 5 );

		$errorFormatter = new ApiErrorFormatter(
			$result,
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
			'none',
			false
		);

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

		return $api->getResult()->getResultData();
	}

	private function newRequestContext( $request = [] ) {
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
