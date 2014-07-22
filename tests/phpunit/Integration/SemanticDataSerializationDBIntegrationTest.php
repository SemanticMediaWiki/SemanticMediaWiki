<?php

namespace SMW\Tests\Integration;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\SemanticDataValidator;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;
use SMW\DataValueFactory;
use SMW\Subobject;
use SMW\SerializerFactory;

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
class SemanticDataSerializationDBIntegrationTest extends MwDBaseUnitTestCase {

	public function testRoundtripOfSerializedSemanticDataAfterStoreUpdate() {

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$semanticDataBeforeUpdate = new SemanticData( $subject );

		$subobject = new Subobject( $subject->getTitle() );
		$subobject->setEmptySemanticDataForId( 'SomeSubobjectToSerialize' );

		$subobject->getSemanticData()->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' )
		);

		$semanticDataBeforeUpdate->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$this->getStore()->updateData( $semanticDataBeforeUpdate );

		$semanticDataAfterUpdate = $this->getStore()->getSemanticData( $subject );

		$this->assertNotEquals(
			$semanticDataBeforeUpdate->getHash(),
			$semanticDataAfterUpdate->getHash(),
			'Assert that the hash is not comparable due to the added _SKEY property during the update'
		);

		$serializerFactory = new SerializerFactory();

		$this->assertEquals(
			$semanticDataAfterUpdate->getHash(),
			$serializerFactory->deserialize( $serializerFactory->serialize( $semanticDataAfterUpdate ) )->getHash()
		);
	}

}
