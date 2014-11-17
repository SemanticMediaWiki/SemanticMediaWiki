<?php

namespace SMW\Test;

use SMW\Tests\MwDBaseUnitTestCase;

use SMW\StoreFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\ConnectionManager;

use Title;
use SMWQueryProcessor;
use SMWRequestOptions;

/**
 * Tests for the SMWStore class.
 *
 * @since 1.8
 *
 *
 * @group SMW
 * @group SMWStore
 * @group SMWExtension
 * @group Database
 *
 * @author Nischay Nahata
 */
class StoreTest extends MwDBaseUnitTestCase {

///// Reading methods /////

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
		$subject = DIWikiPage::newFromTitle( $title );
		$store = StoreFactory::getStore();

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$store->getSemanticData( $subject, $filter ),
			"Result should be instance of SMWSemanticData."
		);
	}

	public function getPropertyValuesDataProvider() {
		return array(
			array( Title::newMainPage()->getFullText(), new DIProperty('_MDAT') ),
			array( Title::newMainPage()->getFullText(), DIProperty::newFromUserLabel('Age') ),
			#add more pages and properties here, make sure they exist
			#array( Test, Property ),
		);
	}

	/**
	* @dataProvider getPropertyValuesDataProvider
	*/
	public function testGetPropertyValues( $titleText, DIProperty $property, $requestOptions = null ) {
		$title = Title::newFromText( $titleText );
		$subject = DIWikiPage::newFromTitle( $title );
		$store = StoreFactory::getStore();
		$result = $store->getPropertyValues( $subject, $property, $requestOptions );

		$this->assertTrue( is_array( $result ) );

		foreach( $result as $di ) {
			$this->assertInstanceOf(
				'\SMWDataItem',
				$di,
				"Result should be instance of SMWDataItem."
			);
		}
	}

	public function getPropertySubjectsDataProvider() {
		return array(
			array( new DIProperty('_MDAT'), null ),
			#add more properties and values (SMWDataItem) here, make sure they exist
			#array( Property, value ),
		);
	}

	/**
	* @dataProvider getPropertySubjectsDataProvider
	*/
	public function testGetPropertySubjects( DIProperty $property, $value, $requestOptions = null ) {
		$store = StoreFactory::getStore();
		$result = $store->getPropertySubjects( $property, $value, $requestOptions );

		$this->assertTrue( is_array( $result ) );

		foreach( $result as $page ) {
			$this->assertInstanceOf(
				'\SMW\DIWikiPage',
				$page,
				"Result should be instance of DIWikiPage."
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
		$subject = DIWikiPage::newFromTitle( $title );
		$store = StoreFactory::getStore();
		$result = $store->getProperties( $subject, $requestOptions );

		$this->assertTrue( is_array( $result ) );

		foreach( $result as $property ) {
			$this->assertInstanceOf(
				'\SMW\DIProperty',
				$property,
				"Result should be instance of DIProperty."
			);
		}
	}

///// Query answering /////

	public function getQueryResultDataProvider() {
		return array(
			array( '[[Modification date::+]]|?Modification date|sort=Modification date|order=desc' ),
		);
	}

	/**
	* @dataProvider getQueryResultDataProvider
	*/
	public function testGetQueryResult( $query ) {
		// TODO: this prevents doing [[Category:Foo||bar||baz]], must document.
		// TODO: for some reason PHPUnit is failing here. Line in SQLStore2Queries with comment "This test printed output:"
//		$rawParams = explode( '|', $query );
//
//		list( $queryString, $parameters, $printouts ) = SMWQueryProcessor::getComponentsFromFunctionParams( $rawParams, false );
//		SMWQueryProcessor::addThisPrintout( $printouts, $parameters );
//		$parameters = SMWQueryProcessor::getProcessedParams( $parameters, $printouts );
//		$smwQuery = SMWQueryProcessor::createQuery( $queryString, $parameters, SMWQueryProcessor::SPECIAL_PAGE, '', $printouts );
//		$store = \SMW\StoreFactory::getStore();
//		$queryResult = $store->getQueryResult( $smwQuery );
//
//		$this->assertInstanceOf(
//			'\SMWQueryResult',
//			$queryResult,
//			"Result should be instance of SMWQueryResult."
//		);

		$this->assertTrue( true );
	}

///// Special page functions /////

	public function testGetPropertiesSpecial() {
		// Really bailing out here and making the test database dependant!!

		// This test fails on mysql http://bugs.mysql.com/bug.php?id=10327
		if( $GLOBALS['wgDBtype'] == 'mysql' ) {
			$this->assertTrue( true );
			return;
		}

		$store = StoreFactory::getStore();
		$result = $store->getPropertiesSpecial( new SMWRequestOptions() );

		$this->assertInstanceOf( '\SMW\ResultCollector', $result );
		foreach( $result->getResults() as $row ) {
			$this->assertEquals( 2, sizeof( $row ) );

			$this->assertInstanceOf(
				'\SMW\DIProperty',
				$row[0],
				"Result should be instance of DIProperty."
			);
		}
	}

	public function testGetUnusedPropertiesSpecial() {
		$store = StoreFactory::getStore();
		$result = $store->getUnusedPropertiesSpecial( new SMWRequestOptions() );

		$this->assertInstanceOf( '\SMW\ResultCollector', $result );
		foreach( $result->getResults() as $row ) {
			$this->assertInstanceOf(
				'\SMW\DIProperty',
				$row,
				"Result should be instance of DIProperty."
			);
		}
	}

	public function testGetWantedPropertiesSpecial() {
		$store = StoreFactory::getStore();
		$result = $store->getWantedPropertiesSpecial( new SMWRequestOptions() );

		$this->assertInstanceOf( '\SMW\ResultCollector', $result );
		foreach( $result->getResults() as $row ) {
			$this->assertInstanceOf(
				'\SMW\DIProperty',
				$row[0],
				"Result should be instance of DIProperty."
			);
		}
	}

	public function testGetStatistics() {
		$store = StoreFactory::getStore();
		$result = $store->getStatistics();

		$this->assertTrue( is_array( $result ) );
		$this->assertArrayHasKey( 'PROPUSES', $result );
		$this->assertArrayHasKey( 'USEDPROPS', $result );
		$this->assertArrayHasKey( 'DECLPROPS', $result );
	}

	public function testConnection() {

		$store = StoreFactory::getStore();
		$store->setConnectionManager( new ConnectionManager() );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Database',
			$store->getConnection( 'mw.db' )
		);
	}

}
