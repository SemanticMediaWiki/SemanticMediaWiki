<?php

namespace SMW\Tests\Integration;

use Iterator;
use MediaWiki\MediaWikiServices;
use SMW\Connection\ConnectionManager;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Lookup\ListLookup;
use SMW\MediaWiki\Connection\Database;
use SMW\RequestOptions;
use SMW\Store;
use SMW\StoreFactory;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * Tests for the Store class.
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
class StoreTest extends SMWIntegrationTestCase {

///// Reading methods /////

	public function getSemanticDataProvider() {
		return [
			[ MediaWikiServices::getInstance()->getTitleFactory()->newMainPage()->getFullText() ],
		];
	}

	/**
	 * @dataProvider getSemanticDataProvider
	 */
	public function testGetSemanticData( $titleText, $filter = false ) {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
		$title = $titleFactory->newFromText( $titleText );
		$subject = WikiPage::newFromTitle( $title );
		$store = StoreFactory::getStore();

		$this->assertInstanceOf(
			SemanticData::class,
			$store->getSemanticData( $subject, $filter ),
			"Result should be instance of SMWSemanticData."
		);
	}

	public function getPropertyValuesDataProvider() {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
		return [
			[ $titleFactory->newMainPage()->getFullText(), new Property( '_MDAT' ) ],
			[ $titleFactory->newMainPage()->getFullText(), Property::newFromUserLabel( 'Age' ) ],
		];
	}

	/**
	 * @dataProvider getPropertyValuesDataProvider
	 */
	public function testGetPropertyValues( $titleText, Property $property, $requestOptions = null ) {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $titleText );
		$subject = WikiPage::newFromTitle( $title );
		$store = StoreFactory::getStore();
		$result = $store->getPropertyValues( $subject, $property, $requestOptions );

		$this->assertIsArray( $result );
		$this->assertContainsOnlyInstancesOf( DataItem::class, $result );
	}

	public function getPropertySubjectsDataProvider() {
		return [
			[ new Property( '_MDAT' ), null ],
		];
	}

	/**
	 * @dataProvider getPropertySubjectsDataProvider
	 */
	public function testGetPropertySubjects( Property $property, $value, $requestOptions = null ) {
		$store = StoreFactory::getStore();
		$result = $store->getPropertySubjects( $property, $value, $requestOptions );

		$this->assertInstanceOf(
			Iterator::class,
			$result
		);

		foreach ( $result as $page ) {
			$this->assertInstanceOf(
				WikiPage::class,
				$page,
				"Result should be instance of WikiPage."
			);
		}
	}

	public function getPropertiesDataProvider() {
		return [
			[ MediaWikiServices::getInstance()->getTitleFactory()->newMainPage()->getFullText() ],
		];
	}

	/**
	 * @dataProvider getPropertiesDataProvider
	 */
	public function testGetProperties( $titleText, $requestOptions = null ) {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $titleText );
		$subject = WikiPage::newFromTitle( $title );
		$store = StoreFactory::getStore();
		$result = $store->getProperties( $subject, $requestOptions );

		$this->assertIsArray( $result );

		foreach ( $result as $property ) {
			$this->assertInstanceOf(
				DataItem::class,
				$property,
				"Result should be instance of Property."
			);
		}
	}

///// Special page functions /////

	public function testGetPropertiesSpecial() {
		// Really bailing out here and making the test database dependent!!

		// This test fails on mysql http://bugs.mysql.com/bug.php?id=10327
		if ( $GLOBALS['wgDBtype'] == 'mysql' ) {
			$this->assertTrue( true );
			return;
		}

		$store = StoreFactory::getStore();
		$result = $store->getPropertiesSpecial( new RequestOptions() );

		$this->assertInstanceOf( ListLookup::class, $result );
		foreach ( $result->fetchList() as $row ) {
			$this->assertCount( 2, $row );

			$this->assertInstanceOf(
				DataItem::class,
				$row[0],
				"Result should be DataItem instance."
			);
		}
	}

	public function testGetUnusedPropertiesSpecial() {
		$store = StoreFactory::getStore();
		$result = $store->getUnusedPropertiesSpecial( new RequestOptions() );

		$this->assertInstanceOf( ListLookup::class, $result );
		foreach ( $result->fetchList() as $row ) {
			$this->assertInstanceOf(
				DataItem::class,
				$row,
				"Result should be instance of Property."
			);
		}
	}

	public function testGetWantedPropertiesSpecial() {
		$store = StoreFactory::getStore();
		$result = $store->getWantedPropertiesSpecial( new RequestOptions() );

		$this->assertInstanceOf( ListLookup::class, $result );
		foreach ( $result->fetchList() as $row ) {
			$this->assertInstanceOf(
				Property::class,
				$row[0],
				"Result should be instance of Property."
			);
		}
	}

	public function testGetStatistics() {
		$store = StoreFactory::getStore();
		$result = $store->getStatistics();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'PROPUSES', $result );
		$this->assertArrayHasKey( 'USEDPROPS', $result );
		$this->assertArrayHasKey( 'DECLPROPS', $result );
	}

	public function testConnection() {
		$store = StoreFactory::getStore();
		$store->setConnectionManager( new ConnectionManager() );

		$this->assertInstanceOf(
			Database::class,
			$store->getConnection( 'mw.db' )
		);
	}

	public function testGetRedirectTarget() {
		$wikipage = new WikiPage( 'Foo', NS_MAIN );
		$expected = new WikiPage( 'Bar', NS_MAIN );

		$instance = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyValues' ] )
			->getMockForAbstractClass();

		$instance->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [ $expected ] );

		$this->assertEquals(
			$expected,
			$instance->getRedirectTarget( $wikipage )
		);
	}

}
