<?php

namespace SMW\Tests\Exporter\ResourceBuilders;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\ResourceBuilders\UniquenessConstraintPropertyValueResourceBuilder;
use SMW\Tests\TestEnvironment;
use SMWExpData as ExpData;

/**
 * @covers \SMW\Exporter\ResourceBuilders\UniquenessConstraintPropertyValueResourceBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class UniquenessConstraintPropertyValueResourceBuilderTest extends \PHPUnit_Framework_TestCase {

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
			UniquenessConstraintPropertyValueResourceBuilder::class,
			new UniquenessConstraintPropertyValueResourceBuilder()
		);
	}

	public function testIsNotResourceBuilderForNonUniquenessConstraintProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new UniquenessConstraintPropertyValueResourceBuilder();

		$this->assertFalse(
			$instance->isResourceBuilderFor( $property )
		);
	}

	public function testAddResourceValueForValidProperty() {

		$property = $this->dataItemFactory->newDIProperty( '_PVUC' );

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'Mo', null )
		);

		$instance = new UniquenessConstraintPropertyValueResourceBuilder();

		$instance->addResourceValue(
			$expData,
			$property,
			$this->dataItemFactory->newDIBoolean( true )
		);

		$this->assertTrue(
			$instance->isResourceBuilderFor( $property )
		);
	}

}
