<?php

namespace SMW\Tests;

use SMW\HashBuilder;
use SMW\DIWikiPage;
use SMW\DIProperty;

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

		$hash = HashBuilder::createHashIdForContent( 'Foo' );

		$this->assertInternalType(
			'string',
			$hash
		);

		$this->assertSame(
			$hash,
			HashBuilder::createHashIdForContent( array( 'Foo' ) )
		);

		$this->assertContains(
			'Bar',
			HashBuilder::createHashIdForContent( array( 'Foo' ), 'Bar' )
		);
	}

	public function segmentProvider() {

		$provider[] = array( NS_FILE, 'ichi', '', '' );
		$provider[] = array( NS_HELP, 'ichi', 'ni', '' );
		$provider[] = array( NS_MAIN, 'ichi maru', 'ni', 'san' );

		return $provider;
	}

}
