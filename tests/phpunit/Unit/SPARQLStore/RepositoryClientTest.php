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
			RepositoryClient::class,
			new RepositoryClient( '', '', '', '' )
		);
	}

	public function testFeatureFlag() {

		$instance = new RepositoryClient( 'Foo', 'Bar', 'Nu', 'Vim' );
		$instance->setFeatureSet( 2 | 4 | 8 );

		$this->assertTrue(
			$instance->isFlagSet( 8 )
		);

		$this->assertFalse(
			$instance->isFlagSet( 16 )
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
