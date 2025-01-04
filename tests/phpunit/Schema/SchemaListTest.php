<?php

namespace SMW\Tests\Schema;

use SMW\DIWikiPage;
use SMW\Schema\SchemaList;
use SMW\Schema\SchemaDefinition;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Schema\SchemaList
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SchemaListTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SchemaList::class,
			new SchemaList( [] )
		);
	}

	public function testGeList() {
		$instance = new SchemaList( [] );

		$this->assertEquals(
			[],
			$instance->getList()
		);
	}

	public function testJsonSerialize() {
		$instance = new SchemaList( [] );

		$this->assertIsString(

			$instance->jsonSerialize()
		);
	}

	public function testGetFingerprint() {
		$instance = new SchemaList( [] );

		$this->assertIsString(

			$instance->getFingerprint()
		);
	}

	public function testAdd() {
		$schemaDefinition = new SchemaDefinition(
			'Bar',
			[ 'Foo' => [ 'Foobar' => 'test' ], [ 'Foo' => 'Bar' ] ]
		);

		$schemaList = new SchemaList( [] );
		$schemaList->add( $schemaDefinition );

		$this->assertEquals(
			[
				$schemaDefinition
			],
			$schemaList->getList()
		);

		$instance = new SchemaList( [] );
		$instance->add(	$schemaList );

		$this->assertEquals(
			[
				$schemaDefinition
			],
			$instance->getList()
		);
	}

	public function testGetMergedList() {
		$data[] = new SchemaDefinition(
			'Foo',
			[ 'Foo' => [ 'Bar' => 42 ], 1001 ]
		);

		$data[] = new SchemaDefinition(
			'Bar',
			[ 'Foo' => [ 'Foobar' => 'test' ], [ 'Foo' => 'Bar' ] ]
		);

		$instance = new SchemaList( $data );

		$this->assertEquals(
			[
				'Foo' => [ 'Bar' => 42, 'Foobar' => 'test' ],
				1001,
				[ 'Foo' => 'Bar' ]
			],
			$instance->merge( $instance )
		);
	}

	public function testToArray() {
		$data[] = new SchemaDefinition(
			'Foo',
			[ 'Foo' => [ 'Bar' => 42 ], 1001 ]
		);

		$data[] = new SchemaDefinition(
			'Bar',
			[ 'Foo' => [ 'Foobar' => 'test' ], [ 'Foo' => 'Bar' ] ]
		);

		$instance = new SchemaList( $data );

		$this->assertEquals(
			[
				'Foo' => [ 'Bar' => 42, 'Foobar' => 'test' ],
				1001,
				[ 'Foo' => 'Bar' ]
			],
			$instance->toArray()
		);

		$this->assertEquals(
			[ 'Bar' => 42, 'Foobar' => 'test' ],
			$instance->get( 'Foo' )
		);
	}

	public function testGet_Empty() {
		$data[] = new SchemaDefinition(
			'Foo',
			[ 'Foo' => [ 'Bar' => 42 ], 1001 ]
		);

		$instance = new SchemaList( $data );

		$this->assertEquals(
			[],
			$instance->get( 'NotAvailableKey' )
		);

		$this->assertNull(
						$instance->get( 'NotAvailableKey', null )
		);
	}

	public function testNewCompartmentIteratorByKey() {
		$data[] = new SchemaDefinition(
			'Foo',
			[ 'Foo' => [ 'Bar' => 42 ], 1001 ]
		);

		$data[] = new SchemaDefinition(
			'Bar',
			[ 'Foo' => [ 'Foobar' => 'test' ], [ 'Foo' => 'Bar' ] ]
		);

		$instance = new SchemaList( $data );
		$compartmentIterator = $instance->newCompartmentIteratorByKey( 'Foo' );

		$this->assertInstanceOf(
			'\SMW\Schema\CompartmentIterator',
			$compartmentIterator
		);

		$this->assertCount(
			2,
			$compartmentIterator
		);
	}

	public function testNewCompartmentIteratorByKey_NoValidKey() {
		$data[] = new SchemaDefinition(
			'Foo',
			[ 'Foo' => [ 'Bar' => 42 ], 1001 ]
		);

		$instance = new SchemaList( $data );
		$compartmentIterator = $instance->newCompartmentIteratorByKey( 'Foobar' );

		$this->assertInstanceOf(
			'\SMW\Schema\CompartmentIterator',
			$compartmentIterator
		);

		$this->assertCount(
			0,
			$compartmentIterator
		);
	}

	public function testNewCompartmentIteratorByKey_Empty() {
		$instance = new SchemaList( [] );
		$compartmentIterator = $instance->newCompartmentIteratorByKey( 'Foo' );

		$this->assertInstanceOf(
			'\SMW\Schema\CompartmentIterator',
			$compartmentIterator
		);

		$this->assertCount(
			0,
			$compartmentIterator
		);
	}

}
