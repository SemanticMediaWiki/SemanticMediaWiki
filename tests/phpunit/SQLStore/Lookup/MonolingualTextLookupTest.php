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
class MonolingualTextLookupTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
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

	/**
	 * @dataProvider subjectProvider
	 */
	public function testFetchFromTable( $subject, $languageCode, $expected ) {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'tablename' )
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$query = new Query( $connection );

		$property = DIProperty::newFromUserLabel( 'Foo' );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturn( $query );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->willReturn( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'Foo' => $tableDefinition ] );

		$instance = new MonolingualTextLookup(
			$this->store
		);

		$instance->fetchFromTable( $subject, $property, $languageCode );

		$this->assertSame(
			$expected,
			$query->getSQL()
		);
	}

	public function subjectProvider() {
		yield 'Foo' => [
			new DIWikiPage( 'Foo', NS_MAIN, '', '' ),
			'fr',
			'SELECT t0.o_id AS id, o0.smw_title AS v0, o0.smw_namespace AS v1, o0.smw_iw AS v2, o0.smw_subobject AS v3,' .
			' t2.o_hash AS text_short, t2.o_blob AS text_long, t3.o_hash AS lcode FROM  AS t0' .
			' INNER JOIN smw_object_ids AS o0 ON t0.o_id=o0.smw_id' .
			' INNER JOIN smw_object_ids AS o1 ON t0.s_id=o1.smw_id' .
			' INNER JOIN smw_object_ids AS t1 ON t0.p_id=t1.smw_id' .
			' INNER JOIN  AS t2 ON t2.s_id=o0.smw_id' .
			' INNER JOIN  AS t3 ON t3.s_id=o0.smw_id' .
			' WHERE (o1.smw_hash=ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6) AND (o0.smw_iw!=:smw) AND (o0.smw_iw!=:smw-delete)' .
			' AND (t0.p_id=42) AND (t3.o_hash=fr)'
		];

		yield 'Foo#_ML123' => [
			new DIWikiPage( 'Foo', NS_MAIN, '', '_ML123' ),
			'en',
			'SELECT t0.o_id AS id, o0.smw_title AS v0, o0.smw_namespace AS v1, o0.smw_iw AS v2, o0.smw_subobject AS v3,' .
			' t2.o_hash AS text_short, t2.o_blob AS text_long, t3.o_hash AS lcode FROM  AS t0' .
			' INNER JOIN smw_object_ids AS o0 ON t0.o_id=o0.smw_id' .
			' INNER JOIN smw_object_ids AS t1 ON t0.p_id=t1.smw_id' .
			' INNER JOIN  AS t2 ON t2.s_id=o0.smw_id' .
			' INNER JOIN  AS t3 ON t3.s_id=o0.smw_id' .
			' WHERE (o0.smw_hash=22e50d45339970c49c3f3e35f73b38efee8fc60b) AND (o0.smw_iw!=:smw) AND (o0.smw_iw!=:smw-delete)' .
			' AND (t0.p_id=42) AND (t3.o_hash=en)'
		];
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
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'readQuery' )
			->willReturn( [ (object)$row ] );

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
			->willReturn( $query );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->willReturn( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'Foo' => $tableDefinition ] );

		$instance = new MonolingualTextLookup(
			$this->store
		);

		$this->assertInstanceof(
			'\SMWDIContainer',
			$instance->newDIContainer( $subject, $property )
		);
	}
}
