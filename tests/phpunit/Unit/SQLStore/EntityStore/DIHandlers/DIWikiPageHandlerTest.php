<?php

namespace SMW\Tests\SQLStore\EntityStore\DIHandlers;

use SMW\SQLStore\EntityStore\DIHandlers\DIWikiPageHandler;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\EntityStore\DIHandlers\DIWikiPageHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class DIWikiPageHandlerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\DIHandlers\DIWikiPageHandler',
			new DIWikiPageHandler( $store )
		);
	}

	public function testImmutableMethodAccess() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DIWikiPageHandler( $store );

		$this->assertInternalType(
			'array',
			$instance->getTableFields()
		);

		$this->assertInternalType(
			'array',
			$instance->getFetchFields()
		);

		$this->assertInternalType(
			'array',
			$instance->getTableIndexes()
		);

		$this->assertInternalType(
			'string',
			$instance->getIndexField()
		);

		$this->assertInternalType(
			'string',
			$instance->getLabelField()
		);
	}

	public function testMutableMethodAccess() {

		// EntityIdTable
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID', 'makeSMWPageID' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 42 ) );

		$idTable->expects( $this->any() )
			->method( 'makeSMWPageID' )
			->will( $this->returnValue( 1001 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$instance = new DIWikiPageHandler( $store );

		$this->assertInternalType(
			'array',
			$instance->getWhereConds( DIWikiPage::newFromText( 'Foo' ) )
		);

		$this->assertInternalType(
			'array',
			$instance->getInsertValues( DIWikiPage::newFromText( 'Foo' ) )
		);
	}

	/**
	 * @dataProvider dbKeysProvider
	 */
	public function testDataItemFromDBKeys( $dbKeys ) {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DIWikiPageHandler( $store );

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->dataItemFromDBKeys( $dbKeys )
		);
	}

	/**
	 * @dataProvider dbKeysExceptionProvider
	 */
	public function testDataItemFromDBKeysThrowsException( $dbKeys ) {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DIWikiPageHandler( $store );

		$this->setExpectedException( '\SMWDataItemException' );
		$instance->dataItemFromDBKeys( $dbKeys );
	}

	public function dbKeysProvider() {

		#0 SMW_NS_PROPERTY, user defined property
		$provider[] = array(
			array( 'Foo', SMW_NS_PROPERTY, 'bar', '', '' )
		);

		#1 SMW_NS_PROPERTY, pre-defined property
		$provider[] = array(
			array( '_Foo', SMW_NS_PROPERTY, 'bar', '', '' )
		);

		return $provider;
	}

	public function dbKeysExceptionProvider() {

		#0 SMW_NS_PROPERTY, pre-defined property (see bug 48711)
		$provider[] = array(
			array( '_Foo', SMW_NS_PROPERTY, '', '', '' )
		);

		return $provider;
	}

}
