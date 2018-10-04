<?php

namespace SMW\Tests\Exporter\ResourceBuilders;

use SMW\DataItemFactory;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\ResourceBuilders\ConceptPropertyValueResourceBuilder;
use SMW\Tests\TestEnvironment;
use SMWExpData as ExpData;

/**
 * @covers \SMW\Exporter\ResourceBuilders\ConceptPropertyValueResourceBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ConceptPropertyValueResourceBuilderTest extends \PHPUnit_Framework_TestCase {

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
			ConceptPropertyValueResourceBuilder::class,
			new ConceptPropertyValueResourceBuilder()
		);
	}

	public function testIsNotResourceBuilderForNonConcProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new ConceptPropertyValueResourceBuilder();

		$this->assertFalse(
			$instance->isResourceBuilderFor( $property )
		);
	}

	public function testAddResourceValueForConcProperty() {

		$property = $this->dataItemFactory->newDIProperty( '_CONC' );
		$dataItem = $this->dataItemFactory->newDIConcept( 'Foo' );

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'Mo', null )
		);

		$instance = new ConceptPropertyValueResourceBuilder();

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
