<?php

namespace SMW\Tests\SPARQLStore\Exception;

use SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException;

/**
 * @covers \SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class BadHttpDatabaseResponseExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException',
			new BadHttpDatabaseResponseException( 'Foo', 'Bar', 'Que' )
		);
	}

	/**
	 * @dataProvider errorCodeProvider
	 */
	public function testErrorCodes( $errorCode ) {

		$instance = new BadHttpDatabaseResponseException( $errorCode, '', '' );
		$this->assertEquals( $errorCode, $instance->getCode() );
	}

	public function errorCodeProvider() {

		$provider = [
			[ BadHttpDatabaseResponseException::ERROR_MALFORMED ],
			[ BadHttpDatabaseResponseException::ERROR_REFUSED ],
			[ BadHttpDatabaseResponseException::ERROR_GRAPH_NOEXISTS ],
			[ BadHttpDatabaseResponseException::ERROR_GRAPH_EXISTS ],
			[ BadHttpDatabaseResponseException::ERROR_OTHER ],
			[ BadHttpDatabaseResponseException::ERROR_NOSERVICE ]
		];

		return $provider;
	}

}
