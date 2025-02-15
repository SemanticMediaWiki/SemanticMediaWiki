<?php

namespace SMW\Tests\Integration;

use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SerializerFactory;
use SMW\Subobject;
use SMW\Tests\SMWIntegrationTestCase;
use Title;

/**
 * @group semantic-mediawiki-integration
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class SemanticDataSerializationDBIntegrationTest extends SMWIntegrationTestCase {

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
