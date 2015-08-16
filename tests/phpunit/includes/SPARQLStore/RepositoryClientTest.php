<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\RepositoryClient;

/**
 * @covers \SMW\SPARQLStore\RepositoryClient
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class RepositoryClientTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RepositoryClient',
			new RepositoryClient( '', '', '', '' )
		);
	}

	public function testPublicAccess() {

		$instance = new RepositoryClient( 'Foo', 'Bar', 'Nu', 'Vim' );

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
