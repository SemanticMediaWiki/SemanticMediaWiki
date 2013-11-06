<?php

namespace SMW\Test;

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

		return $provider;
	}

}