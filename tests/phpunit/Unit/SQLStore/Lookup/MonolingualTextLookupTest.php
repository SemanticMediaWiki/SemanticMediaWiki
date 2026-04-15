<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Container;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Connection\Query;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\Lookup\MonolingualTextLookup;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\Lookup\MonolingualTextLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class MonolingualTextLookupTest extends TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
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
	public function testFetchFromTable( $subject, $languageCode, $expectedParts ) {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'tablename' )
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$query = new Query( $connection );

		$property = Property::newFromUserLabel( 'Foo' );

		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$connection = $this->getMockBuilder( Database::class )
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

		$sql = $query->getSQL();

		foreach ( $expectedParts as $part ) {
			$this->assertStringContainsString( $part, $sql );
		}

		$this->assertStringContainsString(
			'smw_hash=' . $subject->getSha1(),
			$sql,
			'SQL should contain the binary hash from getSha1()'
		);
	}

	public function subjectProvider() {
		yield 'Foo' => [
			new WikiPage( 'Foo', NS_MAIN, '', '' ),
			'fr',
			[
				'SELECT t0.o_id AS id',
				'INNER JOIN smw_object_ids AS o1 ON t0.s_id=o1.smw_id',
				'o1.smw_hash=',
				'(t0.p_id=42)',
				'(t3.o_hash=fr)',
			]
		];

		yield 'Foo#_ML123' => [
			new WikiPage( 'Foo', NS_MAIN, '', '_ML123' ),
			'en',
			[
				'SELECT t0.o_id AS id',
				'o0.smw_hash=',
				'(t0.p_id=42)',
				'(t3.o_hash=en)',
			]
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

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$connection->expects( $this->any() )
			->method( 'readQuery' )
			->willReturn( [ (object)$row ] );

		$query = new Query( $connection );

		$subject = new WikiPage( __METHOD__, NS_MAIN, '', '_bar' );
		$property = Property::newFromUserLabel( 'Foo' );

		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
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
			Container::class,
			$instance->newDIContainer( $subject, $property )
		);
	}
}
