<?php

namespace SMW\Tests;

use SMW\DIConcept;
use SMW\DIWikiPage;
use SMW\Exporter\Element\ExpResource;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDINumber as DINumber;
use SMWExporter as Exporter;

/**
 * @covers \SMWExporter
 *
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SMWExporterTest extends \PHPUnit\Framework\TestCase {

	// @see #795
	public function testExportDataForPropertyPage() {
		$propertyPage = new DIWikiPage( 'Foo', SMW_NS_PROPERTY );

		$expData = Exporter::getInstance()->makeExportDataForSubject( $propertyPage );

		$this->assertInstanceOf(
			'\SMWExpData',
			$expData
		);

		$this->assertInstanceOf(
			'\SMW\Exporter\Element\ExpNsResource',
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
		$provider[] = [ new DINumber( 9001 ), '\SMW\Exporter\Element\ExpElement' ];

		$provider[] = [ new DIBlob( 'foo' ), '\SMW\Exporter\Element\ExpElement' ];
		$provider[] = [ new DIBoolean( true ), '\SMW\Exporter\Element\ExpElement' ];

		$provider[] = [ new DIConcept( 'Foo', '', '', '', '' ), 'SMWExpData' ];

		return $provider;
	}

	public function uriDataItemProvider() {
		$provider[] = [ 'Foo', new DIWikiPage( 'Foo', NS_MAIN, '', '' ) ];
		$provider[] = [ 'Foo#Bar', new DIWikiPage( 'Foo', NS_MAIN, '', 'Bar' ) ];
		$provider[] = [ 'Foo#Bar#Oooo', new DIWikiPage( 'Foo', NS_MAIN, '', 'Bar#Oooo' ) ];
		$provider[] = [ 'Property:Foo', new DIWikiPage( 'Foo', SMW_NS_PROPERTY, '', '' ) ];
		$provider[] = [ 'Concept:Foo', new DIWikiPage( 'Foo', SMW_NS_CONCEPT, '', '' ) ];
		$provider[] = [ 'Unknown:Foo', new DIWikiPage( 'Unknown:Foo', NS_MAIN, '', '' ) ];
		$provider[] = [ 'Unknown:Foo#Bar', new DIWikiPage( 'Unknown:Foo', NS_MAIN, '', 'Bar' ) ];
		$provider[] = [ 'Property:Foo#Bar', new DIWikiPage( 'Foo', SMW_NS_PROPERTY, '', 'Bar' ) ];

		return $provider;
	}

}
