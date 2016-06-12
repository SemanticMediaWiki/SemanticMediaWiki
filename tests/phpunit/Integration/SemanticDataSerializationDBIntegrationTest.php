<?php

namespace SMW\Tests\Integration;

use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SerializerFactory;
use SMW\Subobject;
use SMW\Tests\MwDBaseUnitTestCase;
use Title;

/**
 * @group semantic-mediawiki-integration
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
		$subobject->setEmptyContainerForId( 'SomeSubobjectToSerialize' );

		$subobject->getSemanticData()->addDataValue(
			DataValueFactory::getInstance()->newDataValueByText( 'Foo', 'Bar' )
		);

		$semanticDataBeforeUpdate->addSubobject( $subobject );

		$this->getStore()->updateData( $semanticDataBeforeUpdate );

		$semanticDataAfterUpdate = $this->getStore()->getSemanticData( $subject );

		$serializerFactory = new SerializerFactory();

		$serialization = $serializerFactory->getSerializerFor( $semanticDataAfterUpdate )->serialize( $semanticDataAfterUpdate );

		$this->assertEquals(
			$semanticDataAfterUpdate->getHash(),
			$serializerFactory->getDeserializerFor( $serialization )->deserialize( $serialization )->getHash()
		);
	}

}
