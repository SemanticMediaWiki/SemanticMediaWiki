<?php

namespace SMW\Tests;

use SMW\DataModel\ContainerSemanticData;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\HashBuilder;
use SMW\SemanticData;
use Title;

/**
 * @covers \SMW\HashBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class HashBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider segmentProvider
	 */
	public function testTitleRoundTrip( $namespace, $title, $interwiki , $fragment ) {

		$title = Title::makeTitle( $namespace, $title, $fragment, $interwiki );

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

		$dataItem = new DIWikiPage( $title, $namespace, $interwiki, $subobjectName );

		$this->assertEquals(
			$dataItem,
			HashBuilder::newDiWikiPageFromHash(
				HashBuilder::getHashIdForDiWikiPage( $dataItem )
			)
		);
	}

	public function testPredefinedProperty() {

		$instance = new HashBuilder();

		$property = new DIProperty( '_MDAT' );
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

		$this->assertInternalType(
			'string',
			$hash
		);

		$this->assertSame(
			$hash,
			HashBuilder::createFromContent( [ 'Foo' ] )
		);

		$this->assertContains(
			'Bar',
			HashBuilder::createFromContent( [ 'Foo' ], 'Bar' )
		);
	}

	public function testCreateFromSemanticData() {

		$semanticData = new SemanticData(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$this->assertInternalType(
			'string',
			HashBuilder::createFromSemanticData( $semanticData )
		);
	}

	public function testCreateFromSemanticDataWithSubSemanticDataAndPHPSerialization() {

		$semanticData = new SemanticData(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$containerSemanticData = new ContainerSemanticData(
			new DIWikiPage( __METHOD__, NS_MAIN, '', 'Foo' )
		);

		$containerSemanticData->addSubSemanticData(
			new ContainerSemanticData( new DIWikiPage( __METHOD__, NS_MAIN, '', 'Foo2' ) )
		);

		$semanticData->addSubSemanticData(
			$containerSemanticData
		);

		$semanticData->addSubSemanticData(
			new ContainerSemanticData( new DIWikiPage( __METHOD__, NS_MAIN, '', 'Bar' ) )
		);

		$sem = serialize( $semanticData );

		$this->assertInternalType(
			'string',
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
