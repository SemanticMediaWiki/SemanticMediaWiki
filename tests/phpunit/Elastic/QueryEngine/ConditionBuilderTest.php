<?php

namespace SMW\Tests\Elastic\QueryEngine;

use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Query\QueryResult;
use SMW\DIWikiPage;
use SMWQuery as Query;

/**
 * @covers \SMW\Elastic\QueryEngine\ConditionBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ConditionBuilderTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $termsLookup;
	private $elasticClient;
	private $entityIdManager;
	private $hierarchyLookup;
	private $servicesContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticClient = $this->getMockBuilder( '\SMW\Elastic\Connection\DummyClient' )
			->disableOriginalConstructor()
			->getMock();

		$callback = function ( $type ) use( $database ) {
			if ( $type === 'mw.db' ) {
				return $connection;
			};

			return $this->elasticClient;
		};

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturnCallback( $callback );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->entityIdManager );

		$this->termsLookup = $this->getMockBuilder( '\SMW\Elastic\QueryEngine\TermsLookup\termsLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->servicesContainer = $this->getMockBuilder( '\SMW\Services\ServicesContainer' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConditionBuilder::class,
			new ConditionBuilder( $this->store, $this->termsLookup, $this->hierarchyLookup, $this->servicesContainer )
		);
	}

	public function testPrepareCache() {
		$this->entityIdManager->expects( $this->once() )
			->method( 'warmUpCache' );

		$instance = new ConditionBuilder(
			$this->store,
			$this->termsLookup,
			$this->hierarchyLookup,
			$this->servicesContainer
		);

		$instance->prepareCache( [] );
	}

}
