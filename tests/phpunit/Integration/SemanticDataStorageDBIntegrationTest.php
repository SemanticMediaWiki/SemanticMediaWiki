<?php

namespace SMW\Tests\Integration;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\SemanticDataValidator;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;
use SMW\DataValueFactory;
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
 * @since 2.0
 *
 * @author mwjames
 */
class SemanticDataStorageDBIntegrationTest extends MwDBaseUnitTestCase {

	public function testAddUserDefinedPagePropertyAsObjectToSemanticDataForStorage() {

		$property = new DIProperty( 'SomePageProperty' );

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'SomePropertyPageValue', NS_MAIN, '' )
		);

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $subject )->getProperties()
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

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $subject )->getProperties()
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

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$propertyAsString,
			$this->getStore()->getSemanticData( $subject )->getProperties()
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

		$this->getStore()->updateData( $semanticData );

		$expected = array(
			'propertyCount'  => 2,
			'properties' => array(
				new DIProperty( 'Foo' ),
				new DIProperty( '_SKEY' )
			),
			'propertyValues' => array( 'Bar', __METHOD__ )
		);

		$semanticDataValidator = new SemanticDataValidator();

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )->findSubSemanticData( 'SomeSubobject' )
		);
	}

}
