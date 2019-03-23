<?php

namespace SMW\Tests\SQLStore\EntityStore\DataItemHandlers;

use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIWikiPageHandler;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\DataItemHandlers\DIWikiPageHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class DIWikiPageHandlerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DIWikiPageHandler::class,
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
			->setMethods( [ 'getSMWPageID', 'makeSMWPageID' ] )
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

		$this->setExpectedException( '\SMW\SQLStore\EntityStore\Exception\DataItemHandlerException' );
		$instance->dataItemFromDBKeys( $dbKeys );
	}

	public function dbKeysProvider() {

		#0 SMW_NS_PROPERTY, user defined property
		$provider[] = [
			[ 'Foo', SMW_NS_PROPERTY, 'bar', '', '' ]
		];

		#1 SMW_NS_PROPERTY, pre-defined property
		$provider[] = [
			[ '_Foo', SMW_NS_PROPERTY, 'bar', '', '' ]
		];

		#0 SMW_NS_PROPERTY, pre-defined property (see bug 48711)
		$provider[] = [
			[ '_Foo', SMW_NS_PROPERTY, '', '', '' ]
		];

		return $provider;
	}

	public function dbKeysExceptionProvider() {

		$provider[] = [
			[ 'Foo' ]
		];

		return $provider;
	}

}
