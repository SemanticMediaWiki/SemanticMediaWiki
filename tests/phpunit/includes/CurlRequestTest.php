<?php

namespace SMW\Tests;

use SMW\CurlRequest;

/**
 * @covers \SMW\CurlRequest
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class CurlRequestTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new CurlRequest( curl_init() );

		$this->assertInstanceOf(
			'\SMW\CurlRequest',
			$instance
		);

		$this->assertInstanceOf(
			'\SMW\HttpRequest',
			$instance
		);
	}

	public function testGetLastError() {

		$instance = new CurlRequest( curl_init() );
		$this->assertInternalType( 'string', $instance->getLastError() );
	}

	public function testGetLastErrorCode() {

		$instance = new CurlRequest( curl_init() );
		$this->assertInternalType( 'integer', $instance->getLastErrorCode() );
	}

	public function testExecuteForNullUrl() {

		$instance = new CurlRequest( curl_init( null ) );
		$instance->setOption( CURLOPT_RETURNTRANSFER, true );

		$this->assertFalse( $instance->execute() );
		$this->assertEmpty( $instance->getInfo( CURLINFO_HTTP_CODE ) );
	}

}
