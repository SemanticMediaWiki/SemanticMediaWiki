<?php

namespace SMW\Test;

use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\Subobject;
use SMW\DataValueFactory;
use SMW\DIProperty;

use SMWDITime as DITime;

use Title;

/**
 * @covers \SMWExporter
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SMWExporterTest extends CompatibilityTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWExporter';
	}

	/**
	 * @dataProvider dataItemExpElementProvider
	 *
	 * @since 1.9
	 */
	public function testGetDataItemExpElement( \SMWDataItem $dataItem, $instance ) {

		if ( $instance !== null ) {
			$this->assertInstanceOf( $instance, \SMWExporter::getDataItemExpElement( $dataItem ) );
		}

		$this->assertTrue( true );
	}

	/**
	 * @since return
	 */
	public function dataItemExpElementProvider() {

		$provider = array();

		// #0 (bug 56643)
		$provider[] = array( new \SMWDINumber( 9001 ),  'SMWExpElement' );

		$provider[] = array( new \SMWDIBlob( 'foo' ),   'SMWExpElement' );
		$provider[] = array( new \SMWDIBoolean( true ), 'SMWExpElement' );

		$provider[] = array( new \SMWDIConcept( 'Foo', '', '', '', '' ), null );

		$provider[] = array( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ), 'SMWExpResource' );

		return $provider;
	}

	/**
	*
	*/
	public function testGetResourceElementForWikiPage() {

		$this->assertInstanceOf(
			'SMWExpResource',
			\SMWExporter::getResourceElementForWikiPage( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) )
		);
	}

	/**
	*
	*/
	public function testMakeExportData() {

		$title = Title::newFromText( __METHOD__ );

		$semData = new SemanticData( DIWikiPage::newFromTitle( $title ) );
                $semData->addPropertyObjectValue(
                        new DIProperty( '_MDAT' ),
                        DITime::newFromTimestamp( 1272508903 )
                );

		$subobject = new Subobject( $title );
		$subobject->setEmptySemanticDataForId( 'ID_1234' );
		$dataValue = DataValueFactory::getInstance()->newPropertyValue( 'myProperty', 'Baar' );
		$subobject->addDataValue( $dataValue );

		$semData->addSubSemanticData( $subobject->getSemanticData() );

		$exportData = \SMWExporter::makeExportData( $semData );

		$this->assertInternalType(
			'array',
			$exportData
		);

		$this->assertTrue( count($exportData) > 0 );

		$this->assertInstanceOf(
			'\SMWExpData',
			$exportData[0]
		);

	}

}