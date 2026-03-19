<?php

namespace SMW\Tests\Query;

use PHPUnit\Framework\TestCase;
use SMW\Query\Query;
use SMW\Query\QuerySourceFactory;
use SMW\QueryEngine;
use SMW\SPARQLStore\SPARQLStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\StoreAware;
use SMW\Tests\TestEnvironment;

/**
 * @covers SMW\Query\QuerySourceFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class QuerySourceFactoryTest extends TestCase {

	private $testEnvironment;
	private Store $store;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
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
			QueryEngine::class,
			$instance->get( 'foo' )
		);
	}

	public function testGetStandardStore() {
		$instance = new QuerySourceFactory(
			$this->store,
			[]
		);

		$this->assertInstanceOf(
			SQLStore::class,
			$instance->get( 'sql_store' )
		);

		$this->assertEquals(
			'SMWSQLStore',
			$instance->toString( 'sql_store' )
		);
	}

	public function testGetAsString() {
		$store = $this->getMockBuilder( SPARQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getInfo' )
			->willReturn( [ 'SPARQLStore' ] );

		$instance = new QuerySourceFactory(
			$store,
			[]
		);

		$this->assertStringContainsString(
			'SPARQLStore',
			$instance->toString()
		);
	}

	public function testGetFromAnotherFakeSourceThatImplementsStoreAware() {
		$store = $this->getMockBuilder( Store::class )
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
			QueryEngine::class,
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
