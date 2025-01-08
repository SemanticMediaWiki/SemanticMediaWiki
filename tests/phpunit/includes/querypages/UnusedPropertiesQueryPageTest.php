<?php

namespace SMW\Tests;

use SMW\DataItemFactory;
use SMW\Settings;
use SMW\UnusedPropertiesQueryPage;

/**
 * @covers \SMW\UnusedPropertiesQueryPage
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class UnusedPropertiesQueryPageTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $skin;
	private $settings;
	private $dataItemFactory;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->skin = $this->getMockBuilder( '\Skin' )
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
			'\SMW\UnusedPropertiesQueryPage',
			new UnusedPropertiesQueryPage( $this->store, $this->settings )
		);
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

		$this->assertContains(
			'Foo',
			$result
		);
	}

	public function testInvalidResultThrowsException() {
		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$this->expectException( '\SMW\Exception\PropertyNotFoundException' );
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

		$this->assertContains(
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

		$this->assertContains(
			'Help:Special_properties',
			$result
		);
	}

}
