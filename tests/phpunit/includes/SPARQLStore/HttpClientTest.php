<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\HttpClient;

/**
 * @covers \SMW\SPARQLStore\HttpClient
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class HttpClientTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\HttpClient',
			new HttpClient( '', '', '', '' )
		);
	}

	public function testPublicAccess() {

		$instance = new HttpClient( 'Foo', 'Bar', 'Nu', 'Vim' );

		$this->assertSame(
			'Foo',
			$instance->getDefaultGraph()
		);

		$this->assertSame(
			'Bar',
			$instance->getQueryEndpoint()
		);

		$this->assertSame(
			'Nu',
			$instance->getUpdateEndpoint()
		);

		$this->assertSame(
			'Vim',
			$instance->getDataEndpoint()
		);
	}

}
