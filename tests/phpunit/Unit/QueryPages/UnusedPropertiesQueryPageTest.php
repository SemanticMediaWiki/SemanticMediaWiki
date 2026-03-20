<?php

namespace SMW\Tests\Unit\QueryPages;

use PHPUnit\Framework\TestCase;
use Skin;
use SMW\DataItemFactory;
use SMW\Exception\PropertyNotFoundException;
use SMW\QueryPages\UnusedPropertiesQueryPage;
use SMW\Settings;
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

}
