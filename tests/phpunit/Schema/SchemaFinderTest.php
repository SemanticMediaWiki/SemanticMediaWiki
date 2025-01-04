<?php

namespace SMW\Tests\Schema;

use Onoi\Cache\Cache;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDIBlob as DIBlob;
use SMW\Schema\SchemaFinder;

/**
 * @covers \SMW\Schema\SchemaFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SchemaFinderTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $propertySpecificationLookup;
	private Cache $cache;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
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
			'\SMW\Schema\SchemaList',
			$instance->getSchemaListByType( 'Foo' )
		);
	}

	public function testGetConstraintSchema() {
		$subject = DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY );

		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Bar' => 42 ], 1001 ] ) );
		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Foobar' => 'test' ], [ 'Foo' => 'Bar' ] ] ) );

		$this->propertySpecificationLookup->expects( $this->at( 0 ) )
			->method( 'getSpecification' )
			->with(
				new DIProperty( 'Foo' ),
				new DIProperty( '_CONSTRAINT_SCHEMA' ) )
			->willReturnOnConsecutiveCalls( [ $subject ] );

		$this->propertySpecificationLookup->expects( $this->at( 1 ) )
			->method( 'getSpecification' )
			->with(
				$subject,
				new DIProperty( '_SCHEMA_DEF' ) )
			->willReturnOnConsecutiveCalls( [ $data[0] ], [ $data[1] ] );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$this->assertInstanceOf(
			'\SMW\Schema\SchemaList',
			$instance->getConstraintSchema( new DIProperty( 'Foo' ) )
		);
	}

	public function testNewSchemaList() {
		$subject = DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY );

		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Bar' => 42 ], 1001 ] ) );

		$this->propertySpecificationLookup->expects( $this->at( 0 ) )
			->method( 'getSpecification' )
			->with(
				new DIProperty( 'Foo' ),
				new DIProperty( 'BAR' ) )
			->willReturnOnConsecutiveCalls( [ $subject ] );

		$this->propertySpecificationLookup->expects( $this->at( 1 ) )
			->method( 'getSpecification' )
			->with(
				$subject,
				new DIProperty( '_SCHEMA_DEF' ) )
			->willReturnOnConsecutiveCalls( [ $data[0] ] );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$this->assertInstanceOf(
			'\SMW\Schema\SchemaList',
			$instance->newSchemaList( new DIProperty( 'Foo' ), new DIProperty( 'BAR' ) )
		);
	}

	public function testNewSchemaList_NoMatch() {
		$this->propertySpecificationLookup->expects( $this->at( 0 ) )
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

		$this->propertySpecificationLookup->expects( $this->at( 0 ) )
			->method( 'getSpecification' )
			->with(
				new DIProperty( 'Foo' ),
				new DIProperty( 'BAR' ) )
			->willReturnOnConsecutiveCalls( [ $subject ] );

		$this->propertySpecificationLookup->expects( $this->at( 1 ) )
			->method( 'getSpecification' )
			->with(
				$subject,
				new DIProperty( '_SCHEMA_DEF' ) )
			->willReturnOnConsecutiveCalls( [ $data[0] ] );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup,
			$this->cache
		);

		$this->assertInstanceOf(
			'\SMW\Schema\SchemaList',
			$instance->newSchemaList( new DIProperty( 'Foo' ), new DIProperty( 'BAR' ) )
		);
	}

	public function testRegisterPropertyChangeListener() {
		$propertyChangeListener = $this->getMockBuilder( '\SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener' )
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
		$changeRecord = new \SMW\Listener\ChangeListener\ChangeRecord(
			[
				new \SMW\Listener\ChangeListener\ChangeRecord( [ 'row' => [ 'o_hash' => 'Foo' ] ] )
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
		$changeRecord = new \SMW\Listener\ChangeListener\ChangeRecord(
			[
				new \SMW\Listener\ChangeListener\ChangeRecord( [ 'row' => [ 'o_hash' => 'Foo' ] ] )
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
		$changeRecord = new \SMW\Listener\ChangeListener\ChangeRecord(
			[
				new \SMW\Listener\ChangeListener\ChangeRecord( [ 'row' => [ 'o_id' => 42 ] ] )
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
