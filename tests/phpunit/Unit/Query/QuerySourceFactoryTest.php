<?php

namespace SMW\Tests\Query;

use SMW\Query\QuerySourceFactory;
use SMW\QueryEngine;
use SMW\Store;
use SMW\StoreAware;
use SMW\Tests\TestEnvironment;
use SMWQuery as Query;

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
			[
				'foo' => FakeQueryEngine::class
			]
		);

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->get( 'foo' )
		);
	}

	public function testGetStandardStore() {

		$instance = new QuerySourceFactory(
			$this->store,
			[]
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\SQLStore',
			$instance->get( 'sql_store' )
		);

		$this->assertEquals(
			'SMWSQLStore',
			$instance->toString( 'sql_store' )
		);
	}

	public function testGetAsString() {

		$store = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getInfo' )
			->will( $this->returnValue( [ 'SPARQLStore' ] ) );

		$instance = new QuerySourceFactory(
			$store,
			[]
		);

		$this->assertContains(
			'SPARQLStore',
			$instance->toString()
		);
	}

	public function testGetFromAnotherFakeSourceThatImplementsStoreAware() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->with( $this->stringContains( 'foo' ) );

		$instance = new QuerySourceFactory(
			$store,
			[
				'bar' => AnotherFakeQueryEngine::class
			]
		);

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->get( 'bar' )
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
