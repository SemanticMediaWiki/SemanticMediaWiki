<?php

namespace SMW\Tests\Unit\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\Lookup\CachedListLookup;
use SMW\MediaWiki\Api\BrowseByProperty;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\MediaWiki\Api\BrowseByProperty
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class BrowseByPropertyTest extends TestCase {

	private $store;
	private $apiFactory;
	private $applicationFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
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
			BrowseByProperty::class,
			$instance
		);
	}

	public function testExecute() {
		$list[] = [
			new Property( 'Foo' ),
			42
		];

		$list[] = [
			new Property( 'Foaf:Foo' ),
			1001
		];

		$list[] = [
			new Property( 'Unknown:Foo' ),
			1001
		];

		$cachedListLookup = $this->getMockBuilder( CachedListLookup::class )
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
