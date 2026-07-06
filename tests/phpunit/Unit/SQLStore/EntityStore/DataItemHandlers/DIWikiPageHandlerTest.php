<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore\DataItemHandlers;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIWikiPageHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\EntityStore\DataItemHandlers\DIWikiPageHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   2.5
 *
 * @author mwjames
 */
class DIWikiPageHandlerTest extends TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DIWikiPageHandler::class,
			new DIWikiPageHandler( $store )
		);
	}

	public function testImmutableMethodAccess() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DIWikiPageHandler( $store );

		$this->assertIsArray(

			$instance->getTableFields()
		);

		$this->assertIsArray(

			$instance->getFetchFields()
		);

		$this->assertIsArray(

			$instance->getTableIndexes()
		);

		$this->assertIsString(

			$instance->getIndexField()
		);

		$this->assertIsString(

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
			->willReturn( 42 );

		$idTable->expects( $this->any() )
			->method( 'makeSMWPageID' )
			->willReturn( 1001 );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new DIWikiPageHandler( $store );

		$this->assertIsArray(

			$instance->getWhereConds( WikiPage::newFromText( 'Foo' ) )
		);

		$this->assertIsArray(

			$instance->getInsertValues( WikiPage::newFromText( 'Foo' ) )
		);
	}

	/**
	 * @dataProvider dbKeysProvider
	 */
	public function testDataItemFromDBKeys( $dbKeys ) {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DIWikiPageHandler( $store );

		$this->assertInstanceOf(
			WikiPage::class,
			$instance->dataItemFromDBKeys( $dbKeys )
		);
	}

	public function testDataItemFromDBKeys_Sort() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DIWikiPageHandler( $store );

		$dbKeys = [ 'Foo', NS_MAIN, 'iw', 'sort', 'subobject' ];
		$dataItem = $instance->dataItemFromDBKeys( $dbKeys );

		$this->assertSame(
			'sort',
			$dataItem->getSortKey()
		);
	}

	/**
	 * @dataProvider dbKeysExceptionProvider
	 */
	public function testDataItemFromDBKeysThrowsException( $dbKeys ) {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DIWikiPageHandler( $store );

		$this->expectException( DataItemHandlerException::class );
		$instance->dataItemFromDBKeys( $dbKeys );
	}

	public function dbKeysProvider() {
		# 0 SMW_NS_PROPERTY, user defined property
		$provider[] = [
			[ 'Foo', SMW_NS_PROPERTY, 'bar', '', '' ]
		];

		# 1 SMW_NS_PROPERTY, pre-defined property
		$provider[] = [
			[ '_Foo', SMW_NS_PROPERTY, 'bar', '', '' ]
		];

		# 0 SMW_NS_PROPERTY, pre-defined property (see bug 48711)
		$provider[] = [
			[ '_Foo', SMW_NS_PROPERTY, '', '', '' ]
		];

		$provider[] = [
			[ 'Foo', NS_MAIN, 'iw', 'sort', 'subobject' ]
		];

		return $provider;
	}

	public function dbKeysExceptionProvider() {
		$provider[] = [
			[ 'Foo' ]
		];

		$provider[] = [
			[ 'Foo', '', '', '', '', '', '' ]
		];

		return $provider;
	}

}
