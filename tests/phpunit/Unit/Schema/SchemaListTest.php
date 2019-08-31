<?php

namespace SMW\Tests\Schema;

use SMW\DIWikiPage;
use SMW\Schema\SchemaList;
use SMW\Schema\SchemaDefinition;

/**
 * @covers \SMW\Schema\SchemaList
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SchemaListTest extends \PHPUnit_Framework_TestCase {

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

		$this->assertInstanceOf(
			'\SMW\Schema\CompartmentIterator',
			$instance->newCompartmentIteratorByKey( 'Foo' )
		);
	}

}
