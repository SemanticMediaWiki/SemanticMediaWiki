<?php

namespace SMW\Tests\Schema;

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
class SchemaFinderTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $propertySpecificationLookup;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SchemaFinder::class,
			new SchemaFinder( $this->store, $this->propertySpecificationLookup )
		);
	}

	public function testGetSchemaListByType() {

		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Bar' => 42 ], 1001 ] ) );
		$data[] = new DIBlob( json_encode( [ 'Foo' => [ 'Foobar' => 'test' ], [ 'Foo' => 'Bar' ] ] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [
				DIWikiPage::newFromText( 'Foo' ),
				DIWikiPage::newFromText( 'Bar' ) ] ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getSpecification' )
			->with(
				$this->anyThing(),
				$this->equalTo( new DIProperty( '_SCHEMA_DEF' ) ) )
			->will( $this->onConsecutiveCalls( [ $data[0] ], [ $data[1] ] ) );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup
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
				$this->equalTo( new DIProperty( 'Foo' ) ),
				$this->equalTo( new DIProperty( '_CONSTRAINT_SCHEMA' ) ) )
			->will( $this->onConsecutiveCalls( [ $subject ] ) );

		$this->propertySpecificationLookup->expects( $this->at( 1 ) )
			->method( 'getSpecification' )
			->with(
				$this->equalTo( $subject ),
				$this->equalTo( new DIProperty( '_SCHEMA_DEF' ) ) )
			->will( $this->onConsecutiveCalls( [ $data[0] ], [ $data[1] ] ) );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup
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
				$this->equalTo( new DIProperty( 'Foo' ) ),
				$this->equalTo( new DIProperty( 'BAR' ) ) )
			->will( $this->onConsecutiveCalls( [ $subject ] ) );

		$this->propertySpecificationLookup->expects( $this->at( 1 ) )
			->method( 'getSpecification' )
			->with(
				$this->equalTo( $subject ),
				$this->equalTo( new DIProperty( '_SCHEMA_DEF' ) ) )
			->will( $this->onConsecutiveCalls( [ $data[0] ] ) );

		$instance = new SchemaFinder(
			$this->store,
			$this->propertySpecificationLookup
		);

		$this->assertInstanceOf(
			'\SMW\Schema\SchemaList',
			$instance->newSchemaList( new DIProperty( 'Foo' ), new DIProperty( 'BAR' ) )
		);
	}

}
