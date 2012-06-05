<?php

/**
 * Tests for the SMWSQLStore2 class.
 *
 * @file
 * @since storerewrite
 *
 * @ingroup SMW
 * @ingroup Test
 * @group Store
 * @group Database
 *
 * @author Nischay Nahata
 */

class SMWSQLStore2Test extends MediaWikiTestCase {

	public function getSemanticDataProvider() {
		return array(
			array( Title::newMainPage()->getFullText() ),
			#add more pages here, make sure they exist
			#array( Test ),
		);
	}

	/**
	* @dataProvider getSemanticDataProvider
	*/
	public function testGetSemanticData( $titletext ,$filter = false) {
		$title = Title::newFromText( $titletext );
		$subject = SMWDIWikiPage::newFromTitle( $title );
		$store = new SMWSQLStore2();
		$this->assertInstanceOf( SMWSqlStubSemanticData, $store->getSemanticData( $subject, $filter ),
			"Result should be instance of SMWSqlStubSemanticData." );
	}
}
