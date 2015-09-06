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

		$provider = array(
			array( BadHttpDatabaseResponseException::ERROR_MALFORMED ),
			array( BadHttpDatabaseResponseException::ERROR_REFUSED ),
			array( BadHttpDatabaseResponseException::ERROR_GRAPH_NOEXISTS ),
			array( BadHttpDatabaseResponseException::ERROR_GRAPH_EXISTS ),
			array( BadHttpDatabaseResponseException::ERROR_OTHER ),
			array( BadHttpDatabaseResponseException::ERROR_NOSERVICE )
		);

		return $provider;
	}

}
