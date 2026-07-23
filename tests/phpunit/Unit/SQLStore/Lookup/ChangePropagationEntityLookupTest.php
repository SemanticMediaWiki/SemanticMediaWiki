<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Enum;
use SMW\IteratorFactory;
use SMW\Iterators\AppendIterator;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\ChangePropagationEntityLookup;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @covers \SMW\SQLStore\Lookup\ChangePropagationEntityLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationEntityLookupTest extends TestCase {

	private $store;
	private $iteratorFactory;

	private AppendIterator $appendIterator;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->iteratorFactory = $this->getMockBuilder( IteratorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->appendIterator = $this->getMockBuilder( AppendIterator::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ChangePropagationEntityLookup::class,
			new ChangePropagationEntityLookup( $this->store, $this->iteratorFactory )
		);
	}

	public function testFindByProperty() {
		$property = new Property( 'Foo' );

		$this->iteratorFactory->expects( $this->any() )
			->method( 'newAppendIterator' )
			->willReturn( $this->appendIterator );

		$instance = new ChangePropagationEntityLookup(
			$this->store,
			$this->iteratorFactory
		);

		$this->assertInstanceOf(
			'Iterator',
			$instance->findByProperty( $property )
		);
	}

	public function testFindByPropertySuspendsCacheWarmup(): void {
		$property = new Property( 'Foo' );

		$this->iteratorFactory->expects( $this->any() )
			->method( 'newAppendIterator' )
			->willReturn( $this->appendIterator );

		// The dispatch discovery must keep the subject iterator lazy (suspend
		// warmUpCache), otherwise a property attached to a very large number of
		// subjects materialises the whole set at once (#4344).
		$captured = null;
		$this->store->expects( $this->once() )
			->method( 'getAllPropertySubjects' )
			->willReturnCallback( static function ( $p, $opts = null ) use ( &$captured ) {
				$captured = $opts;
				return [];
			} );

		// The error-reference lookup on the same discovery must be suspended too.
		$capturedForError = null;
		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturnCallback( static function ( $p, $s = null, $opts = null ) use ( &$capturedForError ) {
				$capturedForError = $opts;
				return [];
			} );

		$instance = new ChangePropagationEntityLookup(
			$this->store,
			$this->iteratorFactory
		);

		$instance->findByProperty( $property );

		foreach ( [ $captured, $capturedForError ] as $opts ) {
			$this->assertInstanceOf( RequestOptions::class, $opts );
			$this->assertTrue( $opts->getOption( Enum::SUSPEND_CACHE_WARMUP ) );
		}
	}

	public function testFindByCategorySuspendsCacheWarmup(): void {
		$category = new WikiPage( 'Foo', NS_CATEGORY );

		$this->iteratorFactory->expects( $this->any() )
			->method( 'newAppendIterator' )
			->willReturn( $this->appendIterator );

		$captured = [];
		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturnCallback( static function ( $p, $s = null, $opts = null ) use ( &$captured ) {
				$captured[] = $opts;
				return [];
			} );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ WikiPage::newFromText( 'Bar' ) ] );

		$instance = new ChangePropagationEntityLookup(
			$this->store,
			$this->iteratorFactory
		);

		$instance->findByCategory( $category );

		$this->assertNotEmpty( $captured );
		foreach ( $captured as $opts ) {
			$this->assertInstanceOf( RequestOptions::class, $opts );
			$this->assertTrue( $opts->getOption( Enum::SUSPEND_CACHE_WARMUP ) );
		}
	}

	public function testFindByProperty_TypePropagation() {
		$property = new Property( 'Foo' );

		$this->iteratorFactory->expects( $this->any() )
			->method( 'newAppendIterator' )
			->willReturn( $this->appendIterator );

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher->expects( $this->any() )
			->method( 'getDefaultDataItemTables' )
			->willReturn( [] );

		$entityIdManager = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID', 'getDataItemsFromList' ] )
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$entityIdManager->expects( $this->any() )
			->method( 'getDataItemsFromList' )
			->willReturn( [] );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$instance = new ChangePropagationEntityLookup(
			$store,
			$this->iteratorFactory
		);

		$instance->isTypePropagation( true );

		$res = $instance->findByProperty( $property );

		$this->assertInstanceOf(
			'Iterator',
			$res
		);

		$this->assertEquals(
			$res,
			$instance->findAll( $property )
		);
	}

	public function testFindByCategory() {
		$category = new WikiPage( 'Foo', NS_CATEGORY );

		$this->iteratorFactory->expects( $this->any() )
			->method( 'newAppendIterator' )
			->willReturn( $this->appendIterator );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->with(
				new Property( '_INST' ),
				$this->anything() )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with(
				$this->anything(),
				new Property( '_SUBC' ) )
			->willReturn( [ WikiPage::newFromText( 'Bar' ) ] );

		$instance = new ChangePropagationEntityLookup(
			$store,
			$this->iteratorFactory
		);

		$res = $instance->findByCategory( $category );

		$this->assertInstanceOf(
			'Iterator',
			$res
		);

		$this->assertEquals(
			$res,
			$instance->findAll( $category )
		);
	}

	public function testFindAllOnUnknownTypeThrowsException() {
		$instance = new ChangePropagationEntityLookup(
			$this->store,
			$this->iteratorFactory
		);

		$this->expectException( 'RuntimeException' );
		$instance->findAll( 'Foo' );
	}

}
