<?php

namespace SMW\Tests\SPARQLStore\Exception;

use SMW\SPARQLStore\Exception\BadHttpEndpointResponseException;

/**
 * @covers \SMW\SPARQLStore\Exception\BadHttpEndpointResponseException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class BadHttpEndpointResponseExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\Exception\BadHttpEndpointResponseException',
			new BadHttpEndpointResponseException( 'Foo', 'Bar', 'Que' )
		);
	}

	/**
	 * @dataProvider errorCodeProvider
	 */
	public function testErrorCodes( $errorCode ) {

		$instance = new BadHttpEndpointResponseException( $errorCode, '', '' );
		$this->assertEquals( $errorCode, $instance->getCode() );
	}

	public function errorCodeProvider() {

		$provider = array(
			array( BadHttpEndpointResponseException::ERROR_MALFORMED ),
			array( BadHttpEndpointResponseException::ERROR_REFUSED ),
			array( BadHttpEndpointResponseException::ERROR_GRAPH_NOEXISTS ),
			array( BadHttpEndpointResponseException::ERROR_GRAPH_EXISTS ),
			array( BadHttpEndpointResponseException::ERROR_OTHER ),
			array( BadHttpEndpointResponseException::ERROR_NOSERVICE )
		);

		return $provider;
	}

}
