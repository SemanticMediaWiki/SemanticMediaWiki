<?php

namespace SMW\Tests\Unit\QueryPages;

use PHPUnit\Framework\TestCase;
use Skin;
use SMW\DataItemFactory;
use SMW\Exception\PropertyNotFoundException;
use SMW\QueryPages\PropertiesQueryPage;
use SMW\Settings;
use SMW\Store;

/**
 * @covers \SMW\QueryPages\PropertiesQueryPage
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class PropertiesQueryPageTest extends TestCase {

	private $store;
	private $skin;
	private $settings;
	private $dataItemFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->skin = $this->getMockBuilder( Skin::class )
			->disableOriginalConstructor()
			->getMock();

		$this->settings = Settings::newFromArray( [
			'smwgPDefaultType'              => '_wpg',
			'smwgPropertyLowUsageThreshold' => 5,
			'smwgPropertyZeroCountDisplay'  => true
		] );

		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertiesQueryPage::class,
			new PropertiesQueryPage( $this->store, $this->settings )
		);
	}

	public function testFormatResultDIError() {
		$error = $this->dataItemFactory->newDIError( 'Foo' );

		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			[ $error, null ]
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
		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$this->expectException( PropertyNotFoundException::class );
		$instance->formatResult( $this->skin, null );
	}

	public function testFormatPropertyItemOnUserDefinedProperty() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			[ $property, 42 ]
		);

		$this->assertStringContainsString(
			'Foo',
			$result
		);
	}

	public function testFormatPropertyItemOnPredefinedProperty() {
		$property = $this->dataItemFactory->newDIProperty( '_MDAT' );

		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			[ $property, 42 ]
		);

		$this->assertStringContainsString(
			'42',
			$result
		);
	}

	public function testFormatPropertyItemZeroDisplay() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->settings->set(
			'smwgPropertyZeroCountDisplay',
			false
		);

		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			[ $property, 0 ]
		);

		$this->assertEmpty(
			$result
		);
	}

	public function testFormatPropertyItemLowUsageThreshold() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$count  = 42;

		$this->settings->set(
			'smwgPropertyLowUsageThreshold',
			$count + 1
		);

		$this->settings->set(
			'smwgPDefaultType',
			'_wpg'
		);

		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			[ $property, $count ]
		);

		$this->assertStringContainsString(
			'42',
			$result
		);
	}

}
