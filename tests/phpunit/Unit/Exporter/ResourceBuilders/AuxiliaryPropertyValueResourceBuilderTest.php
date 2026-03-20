<?php

namespace SMW\Tests\Unit\Exporter\ResourceBuilders;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\Export\ExpData;
use SMW\Export\Exporter;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\ResourceBuilders\AuxiliaryPropertyValueResourceBuilder;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Exporter\ResourceBuilders\AuxiliaryPropertyValueResourceBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class AuxiliaryPropertyValueResourceBuilderTest extends TestCase {

	private $dataItemFactory;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();
		$this->testEnvironment = new TestEnvironment();

		$this->testEnvironment->resetPoolCacheById( Exporter::POOLCACHE_ID );
	}

	protected function tearDown(): void {
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
