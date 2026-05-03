<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Container;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\Lookup\MonolingualTextLookup;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;

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

	use MockSelectQueryBuilderTrait;

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
	public function testFetchFromTable( $subject, $languageCode ) {
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
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( [] ) );

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

		$this->assertIsIterable(
			$instance->fetchFromTable( $subject, $property, $languageCode )
		);
	}

	public function subjectProvider() {
		yield 'Foo' => [
			new WikiPage( 'Foo', NS_MAIN, '', '' ),
			'fr',
		];

		yield 'Foo#_ML123' => [
			new WikiPage( 'Foo', NS_MAIN, '', '_ML123' ),
			'en',
		];
	}

	public function testNewDIContainer() {
		$row = (object)[
			'v0' => __METHOD__,
			'v1' => NS_MAIN,
			'v2' => '',
			'v3' => '_bar',
			'text_short' => 'Foobar',
			'text_long' => null,
			'lcode' => 'en'
		];

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
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( [ $row ] ) );

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
