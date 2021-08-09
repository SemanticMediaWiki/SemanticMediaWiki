<?php

namespace SMW\Tests;

use SMW\Connection\ConnectionManager;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\StoreFactory;
use SMWTestsDatabaseTestCase;
use SMWRequestOptions;
use Title;
use SMW\Tests\PHPUnitCompat;

/**
 * Tests for the SMWStore class.
 *
 * @since 1.8
 *
 * @group SMW
 * @group SMWStore
 * @group SMWExtension
 * @group Database
 *
 * @author Nischay Nahata
 */
class StoreTest extends DatabaseTestCase {

	use PHPUnitCompat;

///// Reading methods /////

	public function getSemanticDataProvider() {
		return [
			[ Title::newMainPage()->getFullText() ],
		];
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
		return [
			[ Title::newMainPage()->getFullText(), new DIProperty('_MDAT') ],
			[ Title::newMainPage()->getFullText(), DIProperty::newFromUserLabel('Age') ],
		];
	}

	/**
	* @dataProvider getPropertyValuesDataProvider
	*/
	public function testGetPropertyValues( $titleText, DIProperty $property, $requestOptions = null ) {
		$title = Title::newFromText( $titleText );
		$subject = DIWikiPage::newFromTitle( $title );
		$store = StoreFactory::getStore();
		$result = $store->getPropertyValues( $subject, $property, $requestOptions );

		$this->assertInternalType( 'array', $result );
		$this->assertContainsOnlyInstancesOf( '\SMWDataItem', $result );
	}

	public function getPropertySubjectsDataProvider() {
		return [
			[ new DIProperty('_MDAT'), null ],
		];
	}

	/**
	* @dataProvider getPropertySubjectsDataProvider
	*/
	public function testGetPropertySubjects( DIProperty $property, $value, $requestOptions = null ) {
		$store = StoreFactory::getStore();
		$result = $store->getPropertySubjects( $property, $value, $requestOptions );

		$this->assertInstanceOf(
			'\Iterator',
			$result
		);

		foreach( $result as $page ) {
			$this->assertInstanceOf(
				'\SMW\DIWikiPage',
				$page,
				"Result should be instance of DIWikiPage."
			);
		}
	}

	public function getPropertiesDataProvider() {
		return [
			[ Title::newMainPage()->getFullText() ],
		];
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
				'\SMWDataItem',
				$property,
				"Result should be instance of DIProperty."
			);
		}
	}

///// Special page functions /////

	public function testGetPropertiesSpecial() {
		// Really bailing out here and making the test database dependent!!

		// This test fails on mysql http://bugs.mysql.com/bug.php?id=10327
		if( $GLOBALS['wgDBtype'] == 'mysql' ) {
			$this->assertTrue( true );
			return;
		}

		$store = StoreFactory::getStore();
		$result = $store->getPropertiesSpecial( new SMWRequestOptions() );

		$this->assertInstanceOf( '\SMW\SQLStore\Lookup\ListLookup', $result );
		foreach( $result->fetchList() as $row ) {
			$this->assertCount( 2, $row );

			$this->assertInstanceOf(
				'\SMWDataItem',
				$row[0],
				"Result should be DataItem instance."
			);
		}
	}

	public function testGetUnusedPropertiesSpecial() {
		$store = StoreFactory::getStore();
		$result = $store->getUnusedPropertiesSpecial( new SMWRequestOptions() );

		$this->assertInstanceOf( '\SMW\SQLStore\Lookup\ListLookup', $result );
		foreach( $result->fetchList() as $row ) {
			$this->assertInstanceOf(
				'\SMWDataItem',
				$row,
				"Result should be instance of DIProperty."
			);
		}
	}

	public function testGetWantedPropertiesSpecial() {
		$store = StoreFactory::getStore();
		$result = $store->getWantedPropertiesSpecial( new SMWRequestOptions() );

		$this->assertInstanceOf( '\SMW\SQLStore\Lookup\ListLookup', $result );
		foreach( $result->fetchList() as $row ) {
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

	public function testGetRedirectTarget() {

		$wikipage = new DIWikiPage( 'Foo', NS_MAIN );
		$expected = new DIWikiPage( 'Bar', NS_MAIN );

		$instance = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyValues' ] )
			->getMockForAbstractClass();

		$instance->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $expected ] ) );

		$this->assertEquals(
			$expected,
			$instance->getRedirectTarget( $wikipage )
		);
	}

}
