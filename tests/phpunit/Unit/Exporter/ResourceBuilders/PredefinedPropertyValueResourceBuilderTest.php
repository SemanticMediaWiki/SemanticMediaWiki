<?php

namespace SMW\Tests\Exporter\ResourceBuilders;

use SMW\Exporter\ResourceBuilders\PredefinedPropertyValueResourceBuilder;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;
use SMWExpData as ExpData;
use SMW\Exporter\Element\ExpNsResource;

/**
 * @covers \SMW\Exporter\ResourceBuilders\PredefinedPropertyValueResourceBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PredefinedPropertyValueResourceBuilderTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();
		$this->testEnvironment = new TestEnvironment();

		$this->testEnvironment->resetPoolCacheById( \SMWExporter::POOLCACHE_ID );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceof(
			PredefinedPropertyValueResourceBuilder::class,
			new PredefinedPropertyValueResourceBuilder()
		);
	}

	public function testIsNotResourceBuilderForUserDefinedProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new PredefinedPropertyValueResourceBuilder();

		$this->assertFalse(
			$instance->isResourceBuilderFor( $property )
		);
	}

	public function testAddResourceValueForPredefinedProperty() {

		$property = $this->dataItemFactory->newDIProperty( '_boo' );
		$dataItem = $this->dataItemFactory->newDIBoolean( true );

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'Mo', null )
		);

		$instance = new PredefinedPropertyValueResourceBuilder();

		$instance->addResourceValue(
			$expData,
			$property,
			$dataItem
		);

		$this->assertTrue(
			$instance->isResourceBuilderFor( $property )
		);
	}

}
