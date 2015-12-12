<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\SearchFieldContentRebuilder;
use FakeResultWrapper;

/**
 * @covers \SMW\Maintenance\SearchFieldContentRebuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SearchFieldContentRebuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Maintenance\SearchFieldContentRebuilder',
			new SearchFieldContentRebuilder( $store )
		);
	}

	public function testRebuild() {

		$arbitraryPropertyTableName = 'allornothing';

		$selectResult = array(
			'smw_title'   => 'Foo',
			'smw_sortkey' => 'Foo',
			'smw_id'      => 9999
		);

		$selectResultWrapper = new FakeResultWrapper( array( (object)$selectResult ) );

		$diHandlerBlob = $this->getMockBuilder( '\SMWDIHandlerBlob' )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( $selectResultWrapper ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $diHandlerBlob ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array(
				$this->getNonFixedPropertyTableDefinition( $arbitraryPropertyTableName ) )
			) );

		$instance = new SearchFieldContentRebuilder(
			$store
		);

		$instance->rebuild();
	}

	protected function getNonFixedPropertyTableDefinition( $propertyTableName ) {

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$propertyTable->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( $propertyTableName ) );

		$propertyTable->expects( $this->any() )
			->method( 'getDiType' )
			->will( $this->returnValue( 2 ) );

		return $propertyTable;
	}

}
