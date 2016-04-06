<?php

namespace SMW\Test;

use SMW\DataItemFactory;
use SMW\Settings;
use SMW\WantedPropertiesQueryPage;

/**
 * @covers \SMW\WantedPropertiesQueryPage
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class WantedPropertiesQueryPageTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $skin;
	private $settings;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$this->settings = Settings::newFromArray( array() );

		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\WantedPropertiesQueryPage',
			new WantedPropertiesQueryPage( $this->store, $this->settings )
		);
	}

	public function testFormatResultDIError() {

		$error = $this->dataItemFactory->newDIError( 'Foo');

		$instance = new WantedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			array( $error, null )
		);

		$this->assertInternalType(
			'string',
			$result
		);

		$this->assertEmpty(
			$result
		);
	}

	public function testFormatPropertyItemOnUserDefinedProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new WantedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			array( $property, 42 )
		);

		$this->assertContains(
			'Foo',
			$result
		);
	}

	public function testFormatPropertyItemOnPredefinedProperty() {

		$property = $this->dataItemFactory->newDIProperty( '_MDAT' );

		$instance = new WantedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			array( $property, 42 )
		);

		$this->assertEmpty(
			$result
		);
	}

}
