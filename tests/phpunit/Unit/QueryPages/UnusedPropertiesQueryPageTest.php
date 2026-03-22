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

	public function testFormatResultWithInvalidTypeThrowsException() {
		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$this->expectException( PropertyNotFoundException::class );
		$this->expectExceptionMessage(
			'UnusedPropertiesQueryPage expects results that are properties or errors.'
		);

		$instance->formatResult( $this->skin, [] );
	}

}
