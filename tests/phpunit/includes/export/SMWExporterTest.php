<?php

namespace SMW\Tests;

use SMW\DIWikiPage;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDIConcept as DIConcept;
use SMWDINumber as DINumber;
use SMWExporter as Exporter;
use SMWExpResource as ExpResource;

/**
 * @covers \SMWExporter
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SMWExporterTest extends \PHPUnit_Framework_TestCase {

	// @see #795
	public function testExportDataForPropertyPage() {

		$propertyPage = new DIWikiPage( 'Foo', SMW_NS_PROPERTY );

		$expData = Exporter::getInstance()->makeExportDataForSubject( $propertyPage );

		$this->assertInstanceOf(
			'\SMWExpData',
			$expData
		);

		$this->assertInstanceOf(
			'\SMWExpNsResource',
			$expData->getSubject()
		);
	}

	/**
	 * @dataProvider dataItemExpElementProvider
	 */
	public function testnewExpElement( DataItem $dataItem, $instance ) {

		if ( $instance === null ) {
			return $this->assertNull( Exporter::getInstance()->newExpElement( $dataItem ) );
		}

		$this->assertInstanceOf(
			$instance,
			Exporter::getInstance()->newExpElement( $dataItem )
		);
	}

	/**
	 * @dataProvider uriDataItemProvider
	 * #378
	 */
	public function testFindDataItemForExpElement( $uri, $expectedDataItem ) {

		$uri = Exporter::getInstance()->getNamespaceUri( 'wiki' ) . $uri;

		$this->assertEquals(
			$expectedDataItem,
			Exporter::getInstance()->findDataItemForExpElement( new ExpResource( $uri ) )
		);
	}

	public function dataItemExpElementProvider() {

		// #0 (bug 56643)
		$provider[] = [ new DINumber( 9001 ),  'SMWExpElement' ];

		$provider[] = [ new DIBlob( 'foo' ),   'SMWExpElement' ];
		$provider[] = [ new DIBoolean( true ), 'SMWExpElement' ];

		$provider[] = [ new DIConcept( 'Foo', '', '', '', '' ), 'SMWExpData' ];

		return $provider;
	}

	public function uriDataItemProvider() {

		$provider[] = [ 'Foo',              new DIWikiPage( 'Foo', NS_MAIN, '', '' ) ];
		$provider[] = [ 'Foo#Bar',          new DIWikiPage( 'Foo', NS_MAIN, '', 'Bar' ) ];
		$provider[] = [ 'Foo#Bar#Oooo',     new DIWikiPage( 'Foo', NS_MAIN, '', 'Bar#Oooo' ) ];
		$provider[] = [ 'Property:Foo',     new DIWikiPage( 'Foo', SMW_NS_PROPERTY, '', '' ) ];
		$provider[] = [ 'Concept:Foo',      new DIWikiPage( 'Foo', SMW_NS_CONCEPT, '', '' ) ];
		$provider[] = [ 'Unknown:Foo',      new DIWikiPage( 'Unknown:Foo', NS_MAIN, '', '' ) ];
		$provider[] = [ 'Unknown:Foo#Bar',  new DIWikiPage( 'Unknown:Foo', NS_MAIN, '', 'Bar' ) ];
		$provider[] = [ 'Property:Foo#Bar', new DIWikiPage( 'Foo', SMW_NS_PROPERTY, '', 'Bar' ) ];

		return $provider;
	}

}
