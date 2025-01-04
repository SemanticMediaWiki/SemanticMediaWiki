<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\Options;
use SMW\SQLStore\EntityStore\PropertiesLookup;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\SQLStore\EntityStore\PropertiesLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertiesLookupTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			PropertiesLookup::class,
			new PropertiesLookup( $store )
		);
	}

	public function testLookupForNonFixedPropertyTable() {
		$dataItem = DIWikiPage::newFromText( __METHOD__ );
		$dataItem->setId( 42 );

		$resultWrapper = $this->getMockBuilder( '\Wikimedia\Rdbms\FakeResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$query = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Query' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'execute' )
			->willReturn( $resultWrapper );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->willReturn( $query );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection', 'getSQLOptions', 'getSQLConditions' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLOptions' )
			->willReturn( [] );

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLConditions' )
			->willReturn( '' );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertiesLookup(
			$store
		);

		$instance->fetchFromTable( $dataItem, $propertyTableDef );
	}

	public function testLookupForFixedPropertyTable() {
		$dataItem = DIWikiPage::newFromText( __METHOD__ );
		$dataItem->setId( 1001 );

		$resultWrapper = new FakeResultWrapper( [] );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$query = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Query' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'execute' )
			->willReturn( $resultWrapper );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->willReturn( $query );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertiesLookup(
			$store
		);

		$instance->fetchFromTable( $dataItem, $propertyTableDef );
	}

}
