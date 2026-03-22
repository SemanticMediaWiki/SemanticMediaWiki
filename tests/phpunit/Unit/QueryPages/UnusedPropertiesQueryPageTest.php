<?php

namespace SMW\Tests\Unit\QueryPages;

use PHPUnit\Framework\TestCase;
use Skin;
use SMW\DataItemFactory;
use SMW\Exception\PropertyNotFoundException;
use SMW\QueryPages\UnusedPropertiesQueryPage;
use SMW\RequestOptions;
use SMW\Settings;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\QueryPages\UnusedPropertiesQueryPage
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class UnusedPropertiesQueryPageTest extends TestCase {

	private $store;
	private $skin;
	private $settings;
	private $dataItemFactory;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->skin = $this->getMockBuilder( Skin::class )
			->disableOriginalConstructor()
			->getMock();

		$this->settings = Settings::newFromArray( [] );

		$this->dataItemFactory = new DataItemFactory();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			UnusedPropertiesQueryPage::class,
			new UnusedPropertiesQueryPage( $this->store, $this->settings )
		);
	}

	public function testGetName() {
		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$this->assertSame( 'UnusedProperties', $instance->getName() );
	}

	public function testIsExpensive() {
		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$this->assertFalse( $instance->isExpensive() );
	}

	public function testIsSyndicated() {
		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$this->assertFalse( $instance->isSyndicated() );
	}

	public function testFormatResultDIError() {
		$error = $this->dataItemFactory->newDIError( 'Foo' );

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			$error
		);

		$this->assertIsString(
			$result
		);

		$this->assertStringContainsString(
			'Foo',
			$result
		);
	}

	public function testInvalidResultThrowsException() {
		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$this->expectException( PropertyNotFoundException::class );
		$instance->formatResult( $this->skin, null );
	}

	public function testFormatPropertyItemOnUserDefinedProperty() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			$property
		);

		$this->assertStringContainsString(
			'Foo',
			$result
		);
	}

	public function testFormatPropertyItemOnPredefinedProperty() {
		$property = $this->dataItemFactory->newDIProperty( '_MDAT' );

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			$property
		);

		$this->assertStringContainsString(
			'Help:Special_properties',
			$result
		);
	}

	public function testGetResults() {
		$property = $this->dataItemFactory->newDIProperty( 'TestProperty' );

		$listLookup = $this->getMockBuilder( ListLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->once() )
			->method( 'fetchList' )
			->willReturn( [ $property ] );

		$this->store->expects( $this->once() )
			->method( 'getUnusedPropertiesSpecial' )
			->willReturn( $listLookup );

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$requestOptions = new RequestOptions();
		$results = $instance->getResults( $requestOptions );

		$this->assertIsArray( $results );
		$this->assertCount( 1, $results );
		$this->assertSame( $property, $results[0] );
	}

	public function testGetResultsReturnsEmpty() {
		$listLookup = $this->getMockBuilder( ListLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->once() )
			->method( 'fetchList' )
			->willReturn( [] );

		$this->store->expects( $this->once() )
			->method( 'getUnusedPropertiesSpecial' )
			->willReturn( $listLookup );

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$requestOptions = new RequestOptions();
		$results = $instance->getResults( $requestOptions );

		$this->assertIsArray( $results );
		$this->assertEmpty( $results );
	}

	public function testGetCacheInfoWhenNotFromCache() {
		$listLookup = $this->getMockBuilder( ListLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->once() )
			->method( 'isFromCache' )
			->willReturn( false );

		$this->store->expects( $this->once() )
			->method( 'getUnusedPropertiesSpecial' )
			->willReturn( $listLookup );

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$requestOptions = new RequestOptions();
		$instance->getResults( $requestOptions );

		$cacheInfo = $instance->getCacheInfo();

		$this->assertIsString( $cacheInfo );
		$this->assertEmpty( $cacheInfo );
	}

	public function testGetCacheInfoWhenFromCache() {
		$timestamp = time();

		$listLookup = $this->getMockBuilder( ListLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->once() )
			->method( 'isFromCache' )
			->willReturn( true );

		$listLookup->expects( $this->once() )
			->method( 'getTimestamp' )
			->willReturn( $timestamp );

		$this->store->expects( $this->once() )
			->method( 'getUnusedPropertiesSpecial' )
			->willReturn( $listLookup );

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$requestOptions = new RequestOptions();
		$instance->getResults( $requestOptions );

		$cacheInfo = $instance->getCacheInfo();

		$this->assertIsString( $cacheInfo );
		$this->assertNotEmpty( $cacheInfo );
		$this->assertStringContainsString( 'smw-sp-properties-cache-info', $cacheInfo );
	}

	public function testGetPageHeader() {
		$listLookup = $this->getMockBuilder( ListLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->atLeastOnce() )
			->method( 'isFromCache' )
			->willReturn( false );

		$listLookup->expects( $this->atLeastOnce() )
			->method( 'fetchList' )
			->willReturn( [] );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getUnusedPropertiesSpecial' )
			->willReturn( $listLookup );

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		// Call doQuery to properly initialize selectOptions
		$instance->doQuery( 0, 50 );

		$pageHeader = $instance->getPageHeader();

		$this->assertIsString( $pageHeader );
		$this->assertNotEmpty( $pageHeader );
		$this->assertStringContainsString( 'smw-unusedproperties-docu', $pageHeader );
		$this->assertStringContainsString( 'smw-sp-properties-header-label', $pageHeader );
	}

}
