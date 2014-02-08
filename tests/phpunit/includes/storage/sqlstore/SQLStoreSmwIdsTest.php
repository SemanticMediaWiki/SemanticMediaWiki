<?php

namespace SMW\Tests\SQLStore;

use SMW\Tests\MockDBConnectionProvider;
use SMW\MediaWiki\Database;

use SMW\DIProperty;
use SMWSql3SmwIds;

/**
 * @covers \SMWSql3SmwIds
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group SQLStore
 *
 * @license GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class SQLStoreSmwIdsTest extends \PHPUnit_Framework_TestCase {

	public function getClass() {
		return '\SMWSql3SmwIds';
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf( $this->getClass(), new SMWSql3SmwIds( $store ) );
	}

	public function testGetPropertyIdWhereDatabaseIdAndSortThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		$writeConnection = new MockDBConnectionProvider();
		$mockDatabase = $writeConnection->getMockDatabase();

		$mockDatabase->expects( $this->once() )
			->method( 'tableExists' )
			->will( $this->returnValue( false ) );

		$database = new Database( new MockDBConnectionProvider, $writeConnection );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $database ) );

		$instance = new SMWSql3SmwIds( $store );

		$instance->getSMWPropertyID( new DIProperty( 'Foo' ) );

	}

}
