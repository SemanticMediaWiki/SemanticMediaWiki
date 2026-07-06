<?php

namespace SMW\Tests\Unit\Elastic\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\HierarchyLookup;
use SMW\MediaWiki\Connection\Database;
use SMW\Services\ServicesContainer;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\Elastic\QueryEngine\ConditionBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ConditionBuilderTest extends TestCase {

	private $store;
	private $termsLookup;
	private $elasticClient;
	private $entityIdManager;
	private $hierarchyLookup;
	private $servicesContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticClient = $this->getMockBuilder( DummyClient::class )
			->disableOriginalConstructor()
			->getMock();

		$callback = function ( $type ) use( $database ) {
			if ( $type === 'mw.db' ) {
				return $connection;
			}

			return $this->elasticClient;
		};

		$this->store = $this->getMockBuilder( SQLStore::class )
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

		$this->hierarchyLookup = $this->getMockBuilder( HierarchyLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->servicesContainer = $this->getMockBuilder( ServicesContainer::class )
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
