<?php

namespace SMW\Test;

use SMW\ArrayAccessor;
use SMW\DataItemFactory;
use SMW\PropertiesQueryPage;
use SMW\Settings;

/**
 * @covers \SMW\PropertiesQueryPage
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PropertiesQueryPageTest extends \PHPUnit_Framework_TestCase {

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

		$this->settings = Settings::newFromArray( array(
			'smwgPDefaultType'              => '_wpg',
			'smwgPropertyLowUsageThreshold' => 5,
			'smwgPropertyZeroCountDisplay'  => true
		) );

		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\PropertiesQueryPage',
			new PropertiesQueryPage( $this->store, $this->settings )
		);
	}

	public function testFormatResultDIError() {

		$error = $this->dataItemFactory->newDIError( 'Foo');

		$instance = new PropertiesQueryPage(
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

		$this->assertContains(
			'Foo',
			$result
		);
	}

	public function testInvalidResultException() {

		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$this->setExpectedException( '\SMW\InvalidResultException' );
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
			array( $property, 42 )
		);

		$this->assertContains(
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
			array( $property, 42 )
		);

		$this->assertContains(
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
			array( $property, 0 )
		);

		$this->assertNotEmpty(
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
			array( $property, $count )
		);

		$this->assertContains(
			'42',
			$result
		);
	}

}
