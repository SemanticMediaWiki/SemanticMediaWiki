<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\MonolingualTextLookup;
use SMW\MediaWiki\Connection\Query;
use SMW\DIWikiPage;
use SMW\DIProperty;

/**
 * @covers \SMW\SQLStore\Lookup\MonolingualTextLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class MonolingualTextLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			MonolingualTextLookup::class,
			new MonolingualTextLookup( $this->store )
		);
	}

	public function testFetchFromTable() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$query = new Query( $connection );

		$subject = DIWikiPage::newFromText( __METHOD__ );
		$property = DIProperty::newFromUserLabel( 'Foo' );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ 'Foo' => $tableDefinition ] ) );

		$instance = new MonolingualTextLookup(
			$this->store
		);

		$instance->fetchFromTable( $subject, $property, 'fr' );

		$this->assertSame(
			'SELECT t0.o_id AS id, o0.smw_title AS v0, o0.smw_namespace AS v1, o0.smw_iw AS v2, o0.smw_subobject AS v3,'.
			' t2.o_hash AS text_short, t2.o_blob AS text_long, t3.o_hash AS lcode FROM  AS t0' .
			' INNER JOIN  AS t1 ON t0.p_id=t1.smw_id' .
			' INNER JOIN  AS o0 ON t0.o_id=o0.smw_id' .
			' INNER JOIN  AS t2 ON t2.s_id=o0.smw_id' .
			' INNER JOIN  AS t3 ON t3.s_id=o0.smw_id' .
			' WHERE (t0.s_id=) AND (t0.p_id=) AND (o0.smw_iw!=:smw) AND (o0.smw_iw!=:smw-delete) AND (t3.o_hash=fr)',
			$query->getSQL()
		);
	}

	public function testNewDIContainer() {

		$row = [
			'v0' => __METHOD__,
			'v1' => NS_MAIN,
			'v2' => '',
			'v3' => '_bar',
			'text_short' => 'Foobar',
			'text_long' => null,
			'lcode' => 'en'
		];

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$connection->expects( $this->any() )
			->method( 'query' )
			->will( $this->returnValue( [ (object)$row ] ) );

		$query = new Query( $connection );

		$subject = new DIWikiPage( __METHOD__, NS_MAIN, '', '_bar' );
		$property = DIProperty::newFromUserLabel( 'Foo' );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ 'Foo' => $tableDefinition ] ) );

		$instance = new MonolingualTextLookup(
			$this->store
		);

		$this->assertInstanceof(
			'\SMWDIContainer',
			$instance->newDIContainer( $subject, $property )
		);
	}
}
