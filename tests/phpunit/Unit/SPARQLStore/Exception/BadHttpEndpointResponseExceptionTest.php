<?php

namespace SMW\Tests\Unit\SPARQLStore\Exception;

use PHPUnit\Framework\TestCase;
use SMW\SPARQLStore\Exception\BadHttpEndpointResponseException;

/**
 * @covers \SMW\SPARQLStore\Exception\BadHttpEndpointResponseException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class BadHttpEndpointResponseExceptionTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			BadHttpEndpointResponseException::class,
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
		$provider = [
			[ BadHttpEndpointResponseException::ERROR_MALFORMED ],
			[ BadHttpEndpointResponseException::ERROR_REFUSED ],
			[ BadHttpEndpointResponseException::ERROR_GRAPH_NOEXISTS ],
			[ BadHttpEndpointResponseException::ERROR_GRAPH_EXISTS ],
			[ BadHttpEndpointResponseException::ERROR_OTHER ],
			[ BadHttpEndpointResponseException::ERROR_NOSERVICE ]
		];

		return $provider;
	}

}
