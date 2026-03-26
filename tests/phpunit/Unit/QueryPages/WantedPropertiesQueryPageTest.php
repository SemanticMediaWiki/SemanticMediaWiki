<?php

namespace SMW\Tests\Unit\QueryPages;

use PHPUnit\Framework\TestCase;
use Skin;
use SMW\DataItemFactory;
use SMW\QueryPages\WantedPropertiesQueryPage;
use SMW\Settings;
use SMW\Store;

/**
 * @covers \SMW\QueryPages\WantedPropertiesQueryPage
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class WantedPropertiesQueryPageTest extends TestCase {

	private $store;
	private $skin;
	private $dataItemFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->skin = $this->getMockBuilder( Skin::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			WantedPropertiesQueryPage::class,
			new WantedPropertiesQueryPage( $this->store )
		);
	}

	public function testFormatResultDIError() {
		$error = $this->dataItemFactory->newDIError( 'Foo' );

		$instance = new WantedPropertiesQueryPage( $this->store );

		$result = $instance->formatResult(
			$this->skin,
			[ $error, null ]
		);

		$this->assertIsString(

			$result
		);

		$this->assertEmpty(
			$result
		);
	}

	public function testFormatPropertyItemOnUserDefinedProperty() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new WantedPropertiesQueryPage( $this->store );

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

		$instance = new WantedPropertiesQueryPage( $this->store );

		$result = $instance->formatResult(
			$this->skin,
			[ $property, 42 ]
		);

		$this->assertEmpty(
			$result
		);
	}

}
