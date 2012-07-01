<?php

/**
 * Tests for the SMWDINumber class.
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
class SMWDINumberTest extends MediaWikiTestCase {

	public function serializationDataProvider() {
		return array(
			array( 0 ),
			array( 243,35353 ),
		);
	}

	/**
	* @dataProvider serializationDataProvider
	*/
	public function testSerialization( $number ) {
		$diNumber = new SMWDINumber( $number );
		
		$this->assertEquals( $diNumber,SMWDINumber::doUnserialize( $diNumber->getSerialization() ) );
	}
}