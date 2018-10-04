<?php

namespace SMW\Tests\DataModel;

use SMW\DataItemFactory;
use SMW\DataModel\ContainerSemanticData;
use SMW\DataModel\SubSemanticData;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\DataModel\SubSemanticData
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SubSemanticDataTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SubSemanticData::class,
			new SubSemanticData( $this->dataItemFactory->newDIWikiPage( __METHOD__, NS_MAIN ) )
		);
	}

	public function testAddSubSemanticData() {

		$instance = new SubSemanticData(
			$this->dataItemFactory->newDIWikiPage( __METHOD__, NS_MAIN )
		);

		$this->assertEmpty(
			$instance->getSubSemanticData()
		);

		$containerSemanticData = new ContainerSemanticData(
			$this->dataItemFactory->newDIWikiPage( __METHOD__, NS_MAIN, '', 'Foo' )
		);

		$instance->addSubSemanticData(
			$containerSemanticData
		);

		$this->assertNotEmpty(
			$instance->getSubSemanticData()
		);
	}

	public function testAddSubSemanticDataWithMismatchedSubjectThrowsException() {

		$instance = new SubSemanticData(
			$this->dataItemFactory->newDIWikiPage( __METHOD__, NS_MAIN )
		);

		$this->setExpectedException( '\SMW\Exception\SubSemanticDataException');

		$instance->addSubSemanticData(
			ContainerSemanticData::makeAnonymousContainer( true, true )
		);
	}

	public function testRemoveSubSemanticData() {

		$instance = new SubSemanticData(
			$this->dataItemFactory->newDIWikiPage( __METHOD__, NS_MAIN )
		);

		$containerSemanticData = new ContainerSemanticData(
			$this->dataItemFactory->newDIWikiPage( __METHOD__, NS_MAIN, '', 'Foo' )
		);

		$instance->addSubSemanticData(
			$containerSemanticData
		);

		$this->assertTrue(
			$instance->hasSubSemanticData( 'Foo' )
		);

		$instance->removeSubSemanticData(
			$containerSemanticData
		);

		$this->assertFalse(
			$instance->hasSubSemanticData( 'Foo' )
		);
	}

	public function testRemoveProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new SubSemanticData(
			$this->dataItemFactory->newDIWikiPage( __METHOD__, NS_MAIN )
		);

		$containerSemanticData = new ContainerSemanticData(
			$this->dataItemFactory->newDIWikiPage( __METHOD__, NS_MAIN, '', 'Foo' )
		);

		$containerSemanticData->addPropertyObjectValue(
			$property,
			$this->dataItemFactory->newDIBlob( 'Bar' )
		);

		$instance->addSubSemanticData(
			$containerSemanticData
		);

		$subSemanticData = $instance->findSubSemanticData( 'Foo' );

		$this->assertTrue(
			$subSemanticData->hasProperty( $property )
		);

		$instance->removeProperty(
			$property
		);

		$this->assertFalse(
			$subSemanticData->hasProperty( $property )
		);
	}

}
