<?php

namespace SMW\Tests\Exporter\ResourceBuilders;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\ResourceBuilders\MonolingualTextPropertyValueResourceBuilder;
use SMW\Tests\TestEnvironment;
use SMWExpData as ExpData;

/**
 * @covers \SMW\Exporter\ResourceBuilders\MonolingualTextPropertyValueResourceBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class MonolingualTextPropertyValueResourceBuilderTest extends \PHPUnit_Framework_TestCase {

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
			MonolingualTextPropertyValueResourceBuilder::class,
			new MonolingualTextPropertyValueResourceBuilder()
		);
	}

	public function testIsNotResourceBuilderForNonExternalIdentifierTypedProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new MonolingualTextPropertyValueResourceBuilder();

		$this->assertFalse(
			$instance->isResourceBuilderFor( $property )
		);
	}

	public function testAddResourceValueForValidProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_mlt_rec' );

		$monolingualTextValue = $this->dataValueFactory->newDataValueByProperty(
			$property,
			'Bar@en'
		);

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'Mo', null )
		);

		$instance = new MonolingualTextPropertyValueResourceBuilder();

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
