<?php

namespace SMW\Tests\Elastic;

use SMW\Elastic\ElasticFactory;

/**
 * @covers \SMW\Elastic\ElasticFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticFactoryTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ElasticFactory::class,
			new ElasticFactory()
		);
	}

	public function testNewIndexer() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Indexer',
			$instance->newIndexer( $this->store )
		);
	}

	public function testNewQueryEngine() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->newQueryEngine( $this->store )
		);
	}

	public function testNewRebuilder() {

		$instance = new ElasticFactory();

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Rebuilder',
			$instance->newRebuilder( $this->store )
		);
	}

}
