<?php

/**
 * Tests for the SMWDIString class.
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
class SMWDIStringTest extends MediaWikiTestCase {

	public function serializationDataProvider() {
		return array(
			array( 'I love SemanticMediawiki' ),
			array( 'It is open source' ),
		);
	}

	/**
	* @dataProvider serializationDataProvider
	*/
	public function testSerialization( $String ) {
		$diString = new SMWDIString( $String );
		
		$this->assertEquals( $diString,SMWDIString::doUnserialize( $diString->getSerialization() ) );
	}
}