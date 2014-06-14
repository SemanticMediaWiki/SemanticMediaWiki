<?php

namespace SMW\Tests\Integration;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;
use SMW\DataValueFactory;
use SMW\StoreFactory;
use SMW\Subobject;

use SMWDIBlob as DIBlob;

use Title;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class SemanticDataStorageDBIntegrationTest extends MwDBaseUnitTestCase {

	/** Store */
	protected $store = null;

	protected function setUp() {
		parent::setUp();

		$this->store = StoreFactory::getStore();
	}

	public function testAddUserDefinedPagePropertyAsObjectToSemanticDataForStorage() {

		$property = new DIProperty( 'SomePageProperty' );

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'SomePropertyPageValue', NS_MAIN, '' )
		);

		$this->store->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->store->getSemanticData( $subject )->getProperties()
		);
	}

	public function testAddUserDefinedBlobPropertyAsObjectToSemanticDataForStorage() {

		$property = new DIProperty( 'SomeBlobProperty' );
		$property->setPropertyTypeId( '_txt' );

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIBlob( 'SomePropertyBlobValue' )
		);

		$this->store->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->store->getSemanticData( $subject )->getProperties()
		);
	}

	public function testAddUserDefinedPropertyAsDataValueToSemanticDataForStorage() {

		$propertyAsString = 'SomePropertyAsString';

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$dataValue = DataValueFactory::getInstance()->newPropertyValue(
			$propertyAsString,
			'Foo',
			false,
			$subject
		);

		$semanticData->addDataValue( $dataValue );

		$this->store->updateData( $semanticData );

		$this->assertArrayHasKey(
			$propertyAsString,
			$this->store->getSemanticData( $subject )->getProperties()
		);
	}

	public function testAddSubobjectToSemanticDataForStorage() {

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$subobject = new Subobject( $subject->getTitle() );
		$subobject->setEmptySemanticDataForId( 'SomeSubobject' );

		$subobject->getSemanticData()->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' )
		);

		$semanticData->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$this->store->updateData( $semanticData );

		$this->assertArrayHasKey(
			$subobject->getProperty()->getKey(),
			$this->store->getSemanticData( $subject )->getProperties()
		);

		foreach ( $this->store->getSemanticData( $subject )->getPropertyValues( $subobject->getProperty() ) as $subobject ) {

			$this->assertEquals(
				'SomeSubobject',
				$subobject->getSubobjectName()
			);

			$subobjectSemanticData = $this->store->getSemanticData( $subobject );

			$this->assertArrayHasKey(
				'Foo',
				$subobjectSemanticData->getProperties()
			);
		}
	}

}
