<?php

namespace SMW\Tests\Integration;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataModel\Subobject;
use SMW\DataValueFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * Round-trips SemanticData through MediaWiki's JsonCodec, the same path
 * ParserCache uses to persist objects. Exercises the JsonDeserializable
 * implementation in SemanticData and SubSemanticData and ensures the
 * deserialization step does not throw when nested values have already been
 * recursively deserialized by the codec.
 *
 * @covers \SMW\DataModel\SemanticData
 * @covers \SMW\DataModel\SubSemanticData
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class SemanticDataJsonCodecRoundtripTest extends TestCase {

	/**
	 * @dataProvider semanticDataProvider
	 */
	public function testJsonCodecRoundtrip( SemanticData $data ): void {
		$codec = MediaWikiServices::getInstance()->getJsonCodec();

		$json = $codec->serialize( $data );
		$restored = $codec->deserialize( $json );

		$this->assertInstanceOf( SemanticData::class, $restored );
		$this->assertSame( $data->getHash(), $restored->getHash() );
	}

	public function semanticDataProvider(): array {
		ApplicationFactory::clear();

		$provider = [];
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		// Empty container.
		$empty = new SemanticData( WikiPage::newFromTitle( $title ) );
		$provider['empty'] = [ $empty ];

		// Single property value.
		$single = new SemanticData( WikiPage::newFromTitle( $title ) );
		$single->addDataValue(
			DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Bar' )
		);
		$provider['single property'] = [ $single ];

		// With a populated SubSemanticData (subobject) — the path that
		// triggered the regression in #6538.
		$withSub = new SemanticData( WikiPage::newFromTitle( $title ) );
		$withSub->addDataValue(
			DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Bar' )
		);

		$subobject = new Subobject( $title );
		$subobject->setEmptyContainerForId( 'Foo' );
		$subobject->addDataValue(
			DataValueFactory::getInstance()->newDataValueByText( 'Has subobjects', 'Bam' )
		);

		$withSub->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider['with subobject'] = [ $withSub ];

		return $provider;
	}

}
