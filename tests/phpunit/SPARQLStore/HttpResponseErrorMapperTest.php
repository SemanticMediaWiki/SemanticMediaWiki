<?php

namespace SMW\Tests\SPARQLStore;

use PHPUnit\Framework\TestCase;
use SMW\SPARQLStore\Exception\BadHttpEndpointResponseException;
use SMW\SPARQLStore\HttpResponseErrorMapper;

/**
 * @covers \SMW\SPARQLStore\HttpResponseErrorMapper
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class HttpResponseErrorMapperTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			HttpResponseErrorMapper::class,
			new HttpResponseErrorMapper()
		);
	}

	/**
	 * @dataProvider gracefulHttpCodeProvider
	 */
	public function testGracefulHttpCodes( int $httpCode ) {
		$instance = new HttpResponseErrorMapper();
		$instance->mapErrorResponse( $httpCode, '', 'http://endpoint', 'SELECT ?s' );

		// No exception = pass
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @dataProvider throwingHttpCodeProvider
	 */
	public function testThrowingHttpCodes( int $httpCode ) {
		$instance = new HttpResponseErrorMapper();

		$this->expectException( BadHttpEndpointResponseException::class );
		$instance->mapErrorResponse( $httpCode, '', 'http://endpoint', 'SELECT ?s' );
	}

	public function testConnectionFailureIsGraceful() {
		$instance = new HttpResponseErrorMapper();
		$instance->mapErrorResponse( 0, 'Connection refused', 'http://endpoint', 'SELECT ?s' );

		$this->addToAssertionCount( 1 );
	}

	public static function gracefulHttpCodeProvider() {
		return [
			'not found' => [ 404 ],
		];
	}

	public static function throwingHttpCodeProvider() {
		return [
			'malformed' => [ 400 ],
			'refused' => [ 500 ],
			'other' => [ 503 ],
		];
	}
}
