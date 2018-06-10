<?php

namespace SMW\Tests\Exporter\ResourceBuilders;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\ResourceBuilders\PropertyDescriptionValueResourceBuilder;
use SMW\Tests\TestEnvironment;
use SMWExpData as ExpData;

/**
 * @covers \SMW\Exporter\ResourceBuilders\PropertyDescriptionValueResourceBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyDescriptionValueResourceBuilderTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $dataValueFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();

		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->resetPoolCacheById( \SMWExporter::POOLCACHE_ID );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceof(
			PropertyDescriptionValueResourceBuilder::class,
			new PropertyDescriptionValueResourceBuilder()
		);
	}

	public function testIsNotResourceBuilderForPropertyDescription() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new PropertyDescriptionValueResourceBuilder();

		$this->assertFalse(
			$instance->isResourceBuilderFor( $property )
		);
	}

	public function testAddResourceValueForPropertyDescription() {

		$property = $this->dataItemFactory->newDIProperty( '_PDESC' );

		$monolingualTextValue = $this->dataValueFactory->newDataValueByProperty(
			$property,
			'Some description@en'
		);

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'Mo', null )
		);

		$instance = new PropertyDescriptionValueResourceBuilder();

		$instance->addResourceValue(
			$expData,
			$property,
			$monolingualTextValue->getDataItem()
		);

		$this->assertTrue(
			$instance->isResourceBuilderFor( $property )
		);
	}

}
