<?php

namespace SMW\Tests\SQLStore\ListLookup;

use SMW\SQLStore\ListLookup\UndeclaredPropertyListLookup;
use SMW\DIProperty;

/**
 * @covers \SMW\SQLStore\ListLookup\UndeclaredPropertyListLookup
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class UndeclaredPropertyListLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$defaultPropertyType = '_foo';

		$this->assertInstanceOf(
			'\SMW\SQLStore\ListLookup\UndeclaredPropertyListLookup',
			new UndeclaredPropertyListLookup( $store, $defaultPropertyType, null )
		);
	}

	public function testListLookupInterfaceMethodAccess() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$defaultPropertyType = '_foo';

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UndeclaredPropertyListLookup( $store, $defaultPropertyType, $requestOptions );

		$this->assertInternalType(
			'string',
			$instance->getTimestamp()
		);

		$this->assertFalse(
			$instance->isCached()
		);

		$this->assertContains(
			'UndeclaredPropertyListLookup',
			$instance->getLookupIdentifier()
		);
	}

	public function testNullRequestOptionsThrowsException() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$defaultPropertyType = '_foo';

		$instance = new UndeclaredPropertyListLookup( $store, $defaultPropertyType, null );

		$this->setExpectedException( 'RuntimeException' );
		$instance->fetchList();
	}

	public function testInvalidTableIdThrowsException() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$defaultPropertyType = '_foo';

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UndeclaredPropertyListLookup( $store, $defaultPropertyType, $requestOptions );

		$this->setExpectedException( 'RuntimeException' );
		$instance->fetchList();
	}

	public function testLookupIdentifierChangedByRequestOptions() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$defaultPropertyType = '_foo';

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UndeclaredPropertyListLookup( $store, $defaultPropertyType, $requestOptions );
		$lookupIdentifier = $instance->getLookupIdentifier();

		$this->assertContains(
			'UndeclaredPropertyListLookup',
			$lookupIdentifier
		);

		$requestOptions->limit = 100;
		$instance = new UndeclaredPropertyListLookup( $store, $defaultPropertyType, $requestOptions );

		$this->assertContains(
			'UndeclaredPropertyListLookup',
			$instance->getLookupIdentifier()
		);

		$this->assertNotSame(
			$lookupIdentifier,
			$instance->getLookupIdentifier()
		);
	}

	public function testfetchListForValidProperty() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'getIdTable' ) )
			->getMock();

		$row = new \stdClass;
		$row->smw_title = 'Foo';
		$row->count = 42;

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->once() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->once() )
			->method( 'findTypeTableId' )
			->with( $this->equalTo( '_foo' ) )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $tableDefinition ) ) );

		$defaultPropertyType = '_foo';

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UndeclaredPropertyListLookup( $store, $defaultPropertyType, $requestOptions );
		$result = $instance->fetchList();

		$this->assertInternalType(
			'array',
			$result
		);

		$expected = array(
			new DIProperty( 'Foo' ),
			42
		);

		$this->assertEquals(
			array( $expected ),
			$result
		);
	}

	public function testfetchListForInvalidProperty() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'getIdTable' ) )
			->getMock();

		$row = new \stdClass;
		$row->smw_title = '-Foo';
		$row->count = 42;

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->once() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->once() )
			->method( 'findTypeTableId' )
			->with( $this->equalTo( '_foo' ) )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $tableDefinition ) ) );

		$defaultPropertyType = '_foo';

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UndeclaredPropertyListLookup( $store, $defaultPropertyType, $requestOptions );
		$result = $instance->fetchList();

		$this->assertInternalType(
			'array',
			$result
		);

		$this->assertInstanceOf(
			'\SMWDIError',
			$result[0][0]
		);
	}

	public function testfetchListForFixedPropertyTable() {

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( true ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->never() )
			->method( 'select' );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'findTypeTableId' )
			->with( $this->equalTo( '_foo' ) )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $tableDefinition ) ) );

		$defaultPropertyType = '_foo';

		$requestOptions = $this->getMockBuilder( '\SMWRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UndeclaredPropertyListLookup( $store, $defaultPropertyType, $requestOptions );
		$result = $instance->fetchList();

		$this->assertInternalType(
			'array',
			$result
		);

		$this->assertEmpty(
			$result
		);
	}

}
