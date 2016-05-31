<?php

namespace SMW\Tests\Query;

use SMW\Query\QuerySourceFactory;
use SMW\QueryEngine;
use SMW\StoreAware;
use SMW\Store;
use SMWQuery as Query;
use SMW\Tests\TestEnvironment;

/**
 * @covers SMW\Query\QuerySourceFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QuerySourceFactoryTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			QuerySourceFactory::class,
			new QuerySourceFactory( $this->store )
		);
	}

	public function testGetFromFakeSource() {

		$instance = new QuerySourceFactory(
			$this->store,
			array(
				'foo' => FakeQueryEngine::class
			)
		);

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->getWithLocalFallback( 'foo' )
		);
	}

	public function testGetFromAnotherFakeSourceThatImplementsStoreAware() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'getConnection' ) )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->with( $this->stringContains( 'foo' ) );

		$instance = new QuerySourceFactory(
			$store,
			array(
				'bar' => AnotherFakeQueryEngine::class
			)
		);

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->getWithLocalFallback( 'bar' )
		);
	}
}

class FakeQueryEngine implements QueryEngine {

	public function getQueryResult( Query $query ) {
		return '';
	}

}

class AnotherFakeQueryEngine extends FakeQueryEngine implements StoreAware {

	public function setStore( Store $store ) {
		return $store->getConnection( 'foo' );
	}

}
