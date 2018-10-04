<?php

namespace SMW\Tests\Exporter\ResourceBuilders;

use SMW\DataItemFactory;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\ResourceBuilders\SortPropertyValueResourceBuilder;
use SMW\Serializers\ExpDataSerializer;
use SMW\Tests\TestEnvironment;
use SMWExpData as ExpData;

/**
 * @covers \SMW\Exporter\ResourceBuilders\SortPropertyValueResourceBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SortPropertyValueResourceBuilderTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $testEnvironment;
	private $expDataSerializer;

	protected function setUp() {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();
		$this->testEnvironment = new TestEnvironment();
		$this->expDataSerializer = new ExpDataSerializer();

		$this->testEnvironment->resetPoolCacheById( \SMWExporter::POOLCACHE_ID );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceof(
			SortPropertyValueResourceBuilder::class,
			new SortPropertyValueResourceBuilder()
		);
	}

	public function testIsNotResourceBuilderForNon_SKEYProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new SortPropertyValueResourceBuilder();

		$this->assertFalse(
			$instance->isResourceBuilderFor( $property )
		);
	}

	public function testIsResourceBuilderFor_SKEYProperty() {

		$property = $this->dataItemFactory->newDIProperty( '_SKEY' );

		$instance = new SortPropertyValueResourceBuilder();

		$this->assertTrue(
			$instance->isResourceBuilderFor( $property )
		);
	}

	public function testAddResourceValueFor_SKEYProperty() {

		$property = $this->dataItemFactory->newDIProperty( '_SKEY' );
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN );

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'Mo', null )
		);

		$instance = new SortPropertyValueResourceBuilder();

		$instance->addResourceValue(
			$expData,
			$property,
			$dataItem
		);

		$res = json_encode( $this->expDataSerializer->serialize( $expData ) );

		$this->assertNotContains(
			'sort|http:\/\/semantic-mediawiki.org\/swivt\/1.0#|swivt',
			$res
		);
	}

	public function testAddResourceValueFor_SKEYPropertyWithEnabledCollationField() {

		$property = $this->dataItemFactory->newDIProperty( '_SKEY' );
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN );

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'Mo', null )
		);

		$instance = new SortPropertyValueResourceBuilder();
		$instance->enabledCollationField( true );

		$instance->addResourceValue(
			$expData,
			$property,
			$dataItem
		);

		$res = json_encode( $this->expDataSerializer->serialize( $expData ) );

		$this->assertContains(
			'sort|http:\/\/semantic-mediawiki.org\/swivt\/1.0#|swivt',
			$res
		);

		$this->assertContains(
			'http:\/\/www.w3.org\/2001\/XMLSchema#string',
			$res
		);
	}

	public function testAddResourceValueFor_SKEYPropertyWithEnabledCollationFieldOnBlobItem() {

		$property = $this->dataItemFactory->newDIProperty( '_SKEY' );
		$dataItem = $this->dataItemFactory->newDIBlob( 'Bar' );

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', '', '', null )
		);

		$instance = new SortPropertyValueResourceBuilder();
		$instance->enabledCollationField( true );

		$instance->addResourceValue(
			$expData,
			$property,
			$dataItem
		);

		$res = json_encode( $this->expDataSerializer->serialize( $expData ) );

		$this->assertContains(
			'sort|http:\/\/semantic-mediawiki.org\/swivt\/1.0#|swivt',
			$res
		);

		$this->assertContains(
			'http:\/\/www.w3.org\/2001\/XMLSchema#string',
			$res
		);
	}

}
