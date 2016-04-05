<?php

namespace SMW\Test;

use SMW\DataItemFactory;
use SMW\Settings;
use SMW\UnusedPropertiesQueryPage;

/**
 * @covers \SMW\UnusedPropertiesQueryPage
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class UnusedPropertiesQueryPageTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\UnusedPropertiesQueryPage',
			new UnusedPropertiesQueryPage( $this->store, $this->settings )
		);
	}

	public function testFormatResultDIError() {

		$error = $this->dataItemFactory->newDIError( 'Foo');

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			$error
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

		$instance = new UnusedPropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$this->setExpectedException( '\SMW\InvalidResultException' );
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
