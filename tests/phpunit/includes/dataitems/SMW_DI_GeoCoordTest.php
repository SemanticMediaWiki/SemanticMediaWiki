<?php

/**
 * Tests for the SMWDIGeoCoord class.
 *
 * @file
 * @since storerewrite
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWDataItems
 *
 * @author Nischay Nahata
 */
class SMWDIGeoCoordTest extends MediaWikiTestCase {

	public function serializationDataProvider() {
		return array(
			array( array( 'lat'=>83.34, 'lon'=>38.44, 'alt'=>54 ) ),
			array( array( 'lat'=>42.43, 'lon'=>33.32 ) ),
		);
	}

	/**
	* @dataProvider serializationDataProvider
	*/
	public function testSerialization( $GeoCoord ) {
		$diGeoCoord = new SMWDIGeoCoord( $GeoCoord );
		
		$this->assertEquals( $diGeoCoord,SMWDIGeoCoord::doUnserialize( $diGeoCoord->getSerialization() ) );
	}
}