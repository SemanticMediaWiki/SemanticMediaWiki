<?php

/**
 * Tests for the SMWDIBoolean class.
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
class SMWDIBooleanTest extends MediaWikiTestCase {

	public function serializationDataProvider() {
		return array(
			array( false ),
			array( true ),
		);
	}

	/**
	* @dataProvider serializationDataProvider
	*/
	public function testSerialization( $bool ) {
		$diBoolean = new SMWDIBoolean( $bool );
		
		$this->assertEquals( $diBoolean,SMWDIBoolean::doUnserialize( $diBoolean->getSerialization() ) );
	}
}