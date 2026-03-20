<?php

namespace SMW\Tests;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\DataModel\SemanticData;
use SMW\HashBuilder;

/**
 * @covers \SMW\HashBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class HashBuilderTest extends TestCase {

	/**
	 * @dataProvider segmentProvider
	 */
	public function testTitleRoundTrip( $namespace, $title, $interwiki, $fragment ) {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->makeTitle( $namespace, $title, $fragment, $interwiki );

		$this->assertEquals(
			$title,
			HashBuilder::newTitleFromHash(
				HashBuilder::getHashIdForTitle( $title )
			)
		);
	}

	/**
	 * @dataProvider segmentProvider
	 */
	public function testDiWikiPageRoundTrip( $namespace, $title, $interwiki, $subobjectName ) {
		$dataItem = new WikiPage( $title, $namespace, $interwiki, $subobjectName );

		$this->assertEquals(
			$dataItem,
			HashBuilder::newDiWikiPageFromHash(
				HashBuilder::getHashIdForDiWikiPage( $dataItem )
			)
		);
	}

	public function testPredefinedProperty() {
		$instance = new HashBuilder();

		$property = new Property( '_MDAT' );
		$dataItem = $property->getDiWikiPage();

		$this->assertEquals(
			$dataItem,
			$instance->newDiWikiPageFromHash(
				$instance->getHashIdForDiWikiPage( $dataItem )
			)
		);

		$this->assertEquals(
			$dataItem,
			$instance->newDiWikiPageFromHash(
				$instance->createHashIdFromSegments( $property->getKey(), SMW_NS_PROPERTY )
			)
		);
	}

	public function testContentHashId() {
		$hash = HashBuilder::createFromContent( 'Foo' );

		$this->assertIsString(

			$hash
		);

		$this->assertSame(
			$hash,
			HashBuilder::createFromContent( [ 'Foo' ] )
		);

		$this->assertStringContainsString(
			'Bar',
			HashBuilder::createFromContent( [ 'Foo' ], 'Bar' )
		);
	}

	public function testCreateFromSemanticData() {
		$semanticData = new SemanticData(
			WikiPage::newFromText( __METHOD__ )
		);

		$this->assertIsString(

			HashBuilder::createFromSemanticData( $semanticData )
		);
	}

	public function testCreateFromSemanticDataWithSubSemanticDataAndPHPSerialization() {
		$semanticData = new SemanticData(
			WikiPage::newFromText( __METHOD__ )
		);

		$containerSemanticData = new ContainerSemanticData(
			new WikiPage( __METHOD__, NS_MAIN, '', 'Foo' )
		);

		$containerSemanticData->addSubSemanticData(
			new ContainerSemanticData( new WikiPage( __METHOD__, NS_MAIN, '', 'Foo2' ) )
		);

		$semanticData->addSubSemanticData(
			$containerSemanticData
		);

		$semanticData->addSubSemanticData(
			new ContainerSemanticData( new WikiPage( __METHOD__, NS_MAIN, '', 'Bar' ) )
		);

		$sem = serialize( $semanticData );

		$this->assertIsString(

			HashBuilder::createFromSemanticData( unserialize( $sem ) )
		);
	}

	public function segmentProvider() {
		$provider[] = [ NS_FILE, 'ichi', '', '' ];
		$provider[] = [ NS_HELP, 'ichi', 'ni', '' ];
		$provider[] = [ NS_MAIN, 'ichi maru', 'ni', 'san' ];

		return $provider;
	}

}
