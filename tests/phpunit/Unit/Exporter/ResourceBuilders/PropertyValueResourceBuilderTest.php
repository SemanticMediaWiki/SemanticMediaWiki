<?php

namespace SMW\Tests\Exporter\ResourceBuilders;

use SMW\DataItemFactory;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\ResourceBuilders\PropertyValueResourceBuilder;
use SMW\Tests\TestEnvironment;
use SMWExpData as ExpData;

/**
 * @covers \SMW\Exporter\ResourceBuilders\PropertyValueResourceBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyValueResourceBuilderTest extends \PHPUnit_Framework_TestCase {

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
			PropertyValueResourceBuilder::class,
			new PropertyValueResourceBuilder()
		);
	}

	public function testAddResourceValue() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar', NS_MAIN );

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'Mo', null )
		);

		$instance = new PropertyValueResourceBuilder();

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
