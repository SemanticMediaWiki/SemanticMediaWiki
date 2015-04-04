<?php

namespace SMW\Tests;

use SMW\DIWikiPage;

use SMWDataItem as DataItem;
use SMWDINumber as DINumber;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDIConcept as DIConcept;

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
	public function testGetDataItemExpElement( DataItem $dataItem, $instance ) {

		if ( $instance === null ) {
			return $this->assertNull( Exporter::getInstance()->getDataItemExpElement( $dataItem ) );
		}

		$this->assertInstanceOf(
			$instance,
			Exporter::getInstance()->getDataItemExpElement( $dataItem )
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
		$provider[] = array( new DINumber( 9001 ),  'SMWExpElement' );

		$provider[] = array( new DIBlob( 'foo' ),   'SMWExpElement' );
		$provider[] = array( new DIBoolean( true ), 'SMWExpElement' );

		$provider[] = array( new DIConcept( 'Foo', '', '', '', '' ), null );

		return $provider;
	}

	public function uriDataItemProvider() {

		$provider[] = array( 'Foo',              new DIWikiPage( 'Foo', NS_MAIN, '', '' ) );
		$provider[] = array( 'Foo#Bar',          new DIWikiPage( 'Foo', NS_MAIN, '', 'Bar' ) );
		$provider[] = array( 'Foo#Bar#Oooo',     new DIWikiPage( 'Foo', NS_MAIN, '', 'Bar#Oooo' ) );
		$provider[] = array( 'Property:Foo',     new DIWikiPage( 'Foo', SMW_NS_PROPERTY, '', '' ) );
		$provider[] = array( 'Concept:Foo',      new DIWikiPage( 'Foo', SMW_NS_CONCEPT, '', '' ) );
		$provider[] = array( 'Unknown:Foo',      new DIWikiPage( 'Unknown:Foo', NS_MAIN, '', '' ) );
		$provider[] = array( 'Unknown:Foo#Bar',  new DIWikiPage( 'Unknown:Foo', NS_MAIN, '', 'Bar' ) );
		$provider[] = array( 'Property:Foo#Bar', new DIWikiPage( 'Foo', SMW_NS_PROPERTY, '', 'Bar' ) );

		return $provider;
	}

}
