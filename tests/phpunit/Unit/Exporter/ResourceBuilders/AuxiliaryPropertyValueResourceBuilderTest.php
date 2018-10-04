<?php

namespace SMW\Tests\Exporter\ResourceBuilders;

use SMW\DataItemFactory;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\ResourceBuilders\AuxiliaryPropertyValueResourceBuilder;
use SMW\Tests\TestEnvironment;
use SMWExpData as ExpData;

/**
 * @covers \SMW\Exporter\ResourceBuilders\AuxiliaryPropertyValueResourceBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class AuxiliaryPropertyValueResourceBuilderTest extends \PHPUnit_Framework_TestCase {

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
			AuxiliaryPropertyValueResourceBuilder::class,
			new AuxiliaryPropertyValueResourceBuilder()
		);
	}

	public function testIsNotResourceBuilderForNonImpoProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new AuxiliaryPropertyValueResourceBuilder();

		$this->assertFalse(
			$instance->isResourceBuilderFor( $property )
		);
	}

	public function testAddResourceValueForSelectedAuxiliaryProperty() {

		$property = $this->dataItemFactory->newDIProperty( '_dat' );
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN );

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'Mo', null )
		);

		$instance = new AuxiliaryPropertyValueResourceBuilder();

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
