<?php

namespace SMW\Tests\Schema;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\Listener\ChangeListener\ChangeRecord;
use SMW\Property\SpecificationLookup;
use SMW\Schema\SchemaFinder;
use SMW\Schema\SchemaList;
use SMW\Store;
use SMWDIBlob as DIBlob;

/**
 * @covers \SMW\Schema\SchemaFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SchemaFinderTest extends TestCase {

	private $store;
	private $propertySpecificationLookup;
	private Cache $cache;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SchemaFinder::class,
			new SchemaFinder( $this->store, $this->propertySpecificationLookup, $this->cache )
		);
	}

	public function testGetSchemaListByType() {
		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Bar' => 42 ], 1001 ] ) );
		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Foobar' => 'test' ], [ 'Foo' => 'Bar' ] ] ) );

		$this->cache->expects( $this->any() )
			->method( 'fetch' )
			->willReturn( false );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ) ] );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getSpecification' )
			->with(
				$this->anyThing(),
				new DIProperty( '_SCHEMA_DEF' ) )
			->willReturnOnConsecutiveCalls( [ $data[0] ], [ $data[1] ] );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$this->assertInstanceOf(
			SchemaList::class,
			$instance->getSchemaListByType( 'Foo' )
		);
	}

	public function testGetConstraintSchema() {
		$subject = DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY );

		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Bar' => 42 ], 1001 ] ) );
		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Foobar' => 'test' ], [ 'Foo' => 'Bar' ] ] ) );

		$callCount = 0;
		$this->propertySpecificationLookup->expects( $this->exactly( 2 ) )
			->method( 'getSpecification' )
			->willReturnCallback( static function () use ( &$callCount, $subject, $data ) {
				return [ [ $subject ], [ $data[0] ] ][$callCount++];
			} );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$this->assertInstanceOf(
			SchemaList::class,
			$instance->getConstraintSchema( new DIProperty( 'Foo' ) )
		);
	}

	public function testNewSchemaList() {
		$subject = DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY );

		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Bar' => 42 ], 1001 ] ) );

		$callCount = 0;
		$this->propertySpecificationLookup->expects( $this->exactly( 2 ) )
			->method( 'getSpecification' )
			->willReturnCallback( static function () use ( &$callCount, $subject, $data ) {
				return [ [ $subject ], [ $data[0] ] ][$callCount++];
			} );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$this->assertInstanceOf(
			SchemaList::class,
			$instance->newSchemaList( new DIProperty( 'Foo' ), new DIProperty( 'BAR' ) )
		);
	}

	public function testNewSchemaList_NoMatch() {
		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getSpecification' )
			->willReturn( false );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$this->assertNull(
						$instance->newSchemaList( new DIProperty( 'Foo' ), new DIProperty( 'BAR' ) )
		);
	}

	public function testNewSchemaList_EmptyDefinition() {
		$subject = DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY );
		$data[] = new DIBlob( '' );

		$callCount = 0;
		$this->propertySpecificationLookup->expects( $this->exactly( 2 ) )
			->method( 'getSpecification' )
			->willReturnCallback( static function () use ( &$callCount, $subject, $data ) {
				return [ [ $subject ], [ $data[0] ] ][$callCount++];
			} );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$this->assertInstanceOf(
			SchemaList::class,
			$instance->newSchemaList( new DIProperty( 'Foo' ), new DIProperty( 'BAR' ) )
		);
	}

	public function testRegisterPropertyChangeListener() {
		$propertyChangeListener = $this->getMockBuilder( PropertyChangeListener::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyChangeListener->expects( $this->once() )
			->method( 'addListenerCallback' )
			->with(	'_SCHEMA_TYPE' );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$instance->registerPropertyChangeListener( $propertyChangeListener );
	}

	public function testInvalidateCacheFromChangeRecord() {
		$changeRecord = new ChangeRecord(
			[
				new ChangeRecord( [ 'row' => [ 'o_hash' => 'Foo' ] ] )
			]
		);

		$this->cache->expects( $this->once() )
			->method( 'delete' )
			->with( $this->stringContains( 'smw:schema:c3ddb092fa95e99be46cbbc922e04900' ) );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$instance->invalidateCache( new DIProperty( '_SCHEMA_TYPE' ), $changeRecord );
	}

	public function testInvalidateCacheFromChangeRecord_InvalidKey() {
		$changeRecord = new ChangeRecord(
			[
				new ChangeRecord( [ 'row' => [ 'o_hash' => 'Foo' ] ] )
			]
		);

		$this->cache->expects( $this->never() )
			->method( 'delete' );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$instance->invalidateCache( new DIProperty( 'Foo' ), $changeRecord );
	}

	public function testInvalidateCacheFromChangeRecord_NoHashField() {
		$changeRecord = new ChangeRecord(
			[
				new ChangeRecord( [ 'row' => [ 'o_id' => 42 ] ] )
			]
		);

		$this->cache->expects( $this->never() )
			->method( 'delete' );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$instance->invalidateCache( new DIProperty( '_SCHEMA_TYPE' ), $changeRecord );
	}

}
