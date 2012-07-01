<?php

/**
 * Tests for the SMWDIBlob class.
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
class SMWDIBlobTest extends MediaWikiTestCase {

	public function serializationDataProvider() {
		return array(
			array( 'I love SemanticMediawiki' ),
			array( 'It is open source' ),
		);
	}

	/**
	* @dataProvider serializationDataProvider
	*/
	public function testSerialization( $blob ) {
		$diBlob = new SMWDIBlob( $blob );
		
		$this->assertEquals( $diBlob,SMWDIBlob::doUnserialize( $diBlob->getSerialization() ) );
	}
}