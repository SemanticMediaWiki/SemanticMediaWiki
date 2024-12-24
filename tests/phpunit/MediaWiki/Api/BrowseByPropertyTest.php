<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DIProperty;
use SMW\MediaWiki\Api\BrowseByProperty;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\MediaWiki\Api\BrowseByProperty
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class BrowseByPropertyTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $apiFactory;
	private $applicationFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->registerObject( 'Store', $this->store );

		$this->apiFactory = UtilityFactory::getInstance()->newMwApiFactory();
	}

	protected function tearDown(): void {
		$this->applicationFactory->clear();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$instance = new BrowseByProperty(
			$this->apiFactory->newApiMain( [] ),
			'browsebyproperty'
		);

		$this->assertInstanceOf(
			'SMW\MediaWiki\Api\BrowseByProperty',
			$instance
		);
	}

	public function testExecute() {
		$list[] = [
			new DIProperty( 'Foo' ),
			42
		];

		$list[] = [
			new DIProperty( 'Foaf:Foo' ),
			1001
		];

		$list[] = [
			new DIProperty( 'Unknown:Foo' ),
			1001
		];

		$cachedListLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\CachedListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$cachedListLookup->expects( $this->once() )
			->method( 'fetchList' )
			->willReturn( $list );

		$this->store->expects( $this->once() )
			->method( 'getPropertiesSpecial' )
			->willReturn( $cachedListLookup );

		$this->applicationFactory->registerObject( 'Store', $this->store );

		$result = $this->apiFactory->doApiRequest( [
			'action'  => 'browsebyproperty',
			'property' => 'Foo'
		] );

		$this->assertArrayHasKey(
			'query',
			$result
		);

		$this->assertArrayHasKey(
			'version',
			$result
		);

		$this->assertArrayHasKey(
			'query-continue-offset',
			$result
		);

		$this->assertArrayHasKey(
			'meta',
			$result
		);
	}

}
