<?php

/**
 * Tests for the SMWSQLStore2 class.
 *
 * @file
 * @since storerewrite
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWStore
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
	public function testGetSemanticData( $titleText ,$filter = false) {
		$title = Title::newFromText( $titleText );
		$subject = SMWDIWikiPage::newFromTitle( $title );
		$store = new SMWSQLStore2();

		$this->assertInstanceOf(
			'SMWSemanticData',
			$store->getSemanticData( $subject, $filter ),
			"Result should be instance of SMWSemanticData."
		);
	}

	public function getPropertyValuesDataProvider() {
		return array(
			array( Title::newMainPage()->getFullText(), new SMWDIProperty('_MDAT') ),
			array( Title::newMainPage()->getFullText(), SMWDIProperty::newFromUserLabel('Age') ),
			#add more pages and properties here, make sure they exist
			#array( Test, Property ),
		);
	}

	/**
	* @dataProvider getPropertyValuesDataProvider
	*/
	public function testGetPropertyValues( $titleText, SMWDIProperty $property, $requestOptions = null ) {
		$title = Title::newFromText( $titleText );
		$subject = SMWDIWikiPage::newFromTitle( $title );
		$store = new SMWSQLStore2();
		$result = $store->getPropertyValues( $subject, $property, $requestOptions );

		$this->assertTrue( is_array( $result ) );

		foreach( $result as $di ) {
			$this->assertInstanceOf(
				'SMWDataItem',
				$di,
				"Result should be instance of SMWDataItem."
			);
		}
	}

	public function getPropertySubjectsDataProvider() {
		return array(
			array( new SMWDIProperty('_MDAT'), null ),
			#add more properties and values (SMWDataItem) here, make sure they exist
			#array( Property, value ),
		);
	}

	/**
	* @dataProvider getPropertySubjectsDataProvider
	*/
	public function testGetPropertySubjects( SMWDIProperty $property, $value, $requestOptions = null ) {
		$store = new SMWSQLStore2();
		$result = $store->getPropertySubjects( $property, $value, $requestOptions );

		$this->assertTrue( is_array( $result ) );

		foreach( $result as $page ) {
			$this->assertInstanceOf(
				'SMWDIWikiPage',
				$page,
				"Result should be instance of SMWDIWikiPage."
			);
		}
	}

	public function getPropertiesDataProvider() {
		return array(
			array( Title::newMainPage()->getFullText() ),
			#add more pages here, make sure they exist
			#array( Test ),
		);
	}

	/**
	* @dataProvider getPropertiesDataProvider
	*/
	public function testGetProperties( $titleText, $requestOptions = null ) {
		$title = Title::newFromText( $titleText );
		$subject = SMWDIWikiPage::newFromTitle( $title );
		$store = new SMWSQLStore2();
		$result = $store->getProperties( $subject, $requestOptions );

		$this->assertTrue( is_array( $result ) );

		foreach( $result as $property ) {
			$this->assertInstanceOf(
				'SMWDIProperty',
				$property,
				"Result should be instance of SMWDIProperty."
			);
		}
	}
}
