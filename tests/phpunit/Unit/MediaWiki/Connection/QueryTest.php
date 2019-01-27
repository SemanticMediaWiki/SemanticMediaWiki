<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\MediaWiki\Connection\Query;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Connection\Query
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class QueryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $connection;

	protected function setUp() {
		parent::setUp();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'tableName' )
			->will( $this->returnArgument(0) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Query::class,
			new Query( $this->connection )
		);
	}

	public function testNoType_ThrowsException() {

		$instance = new Query( $this->connection );

		$this->setExpectedException( 'RuntimeException' );
		$instance->build();
	}

	public function testNoFields_ThrowsException() {

		$instance = new Query( $this->connection );
		$instance->type( 'select' );

		$instance->table( 'foo' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->build();
	}

	public function testNoJoinType_ThrowsException() {

		$instance = new Query( $this->connection );

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->join( 'foo' );
	}

	public function testTable_Field() {

		$instance = new Query( $this->connection );
		$instance->type( 'select' );

		$instance->table( 'foo' );
		$instance->field( 'bar' );

		$this->assertSame(
			'SELECT bar FROM foo',
			$instance->build()
		);
	}

	public function testTable_AS() {

		$instance = new Query( $this->connection );
		$instance->type( 'select' );

		$instance->table( 'foo', 't1' );
		$instance->field( 'bar' );

		$this->assertSame(
			'SELECT bar FROM foo AS t1',
			$instance->build()
		);
	}

	public function testTable_Field_Condition() {

		$instance = new Query( $this->connection );
		$instance->type( 'select' );

		$instance->table( 'foo' );
		$instance->field( 'bar', 'b_ar' );
		$instance->condition( 'foobar' );

		$this->assertSame(
			'{"tables":"foo","fields":[["bar","b_ar"]],"conditions":[["foobar"]],"joins":[],"options":[],"alias":"","index":0,"autocommit":false}',
			(string)$instance
		);

		$this->assertSame(
			'SELECT bar AS b_ar FROM foo WHERE (foobar)',
			$instance->build()
		);
	}

	public function testField_HasField() {

		$instance = new Query( $this->connection );
		$instance->field( 'bar', 'b_ar' );

		$this->assertTrue(
			$instance->hasField()
		);

		$this->assertTrue(
			$instance->hasField( 'bar' )
		);

		$this->assertFalse(
			$instance->hasField( 'foo' )
		);
	}

	public function testTable_Field_Conditions() {

		$instance = new Query( $this->connection );
		$instance->type( 'select' );

		$instance->table( 'foo' );

		$instance->field( 'bar', 'b_ar' );
		$instance->field( 'f', 'a' );

		$instance->condition( 'foobar' );
		$instance->condition( $instance->asAnd( 'foo_bar' ) );
		$instance->condition( $instance->asOr( '_bar' ) );

		$this->assertSame(
			'SELECT bar AS b_ar, f AS a FROM foo WHERE ((foobar) AND (foo_bar) OR (_bar))',
			$instance->build()
		);
	}

	public function testTable_Join_Field_Conditions() {

		$instance = new Query( $this->connection );
		$instance->type( 'select' );

		$instance->table( 'foo' );
		$instance->join( 'INNER JOIN', 'abc as v1' );
		$instance->join( 'LEFT JOIN', [ 'def' => 'v2' ] );

		$instance->field( 'bar', 'b_ar' );
		$instance->field( 'f', 'a' );

		$instance->condition( 'foobar' );
		$instance->condition( $instance->asAnd( 'foo_bar' ) );
		$instance->condition( $instance->asOr( '_bar' ) );
		$instance->condition( $instance->asOr( '_foo' ) );

		$this->assertSame(
			'SELECT bar AS b_ar, f AS a FROM foo INNER JOIN abc as v1 LEFT JOIN def AS v2 WHERE (((foobar) AND (foo_bar) OR (_bar)) OR (_foo))',
			$instance->build()
		);
	}

	public function testTable_Join_ON_Field_Conditions() {

		$instance = new Query( $this->connection );
		$instance->type( 'select' );

		$instance->table( 'foo' );
		$instance->field( 'f', 'a' );
		$instance->join( 'LEFT JOIN', [ 'abc' => 'ON p=d' ] );

		$instance->condition( $instance->asAnd( 'foo_bar' ) );

		$this->assertSame(
			'SELECT f AS a FROM foo LEFT JOIN abc ON p=d WHERE (foo_bar)',
			$instance->build()
		);
	}

	public function testTable_Field_Condition_Options_Distinct_Order() {

		$instance = new Query( $this->connection );
		$instance->type( 'select' );

		$instance->table( 'foo' );
		$instance->field( 'f', 'a' );

		$instance->condition( $instance->asOr( 'foo_bar' ) );

		$instance->options(
			[
				'DISTINCT' => true,
				'ORDER BY' => '_foo',
				'LIMIT' => 42
			]
		);

		$this->assertSame(
			'SELECT DISTINCT f AS a FROM foo WHERE (foo_bar) ORDER BY _foo LIMIT 42',
			$instance->build()
		);
	}

	public function testTable_Field_Condition_Options_Group_Having() {

		$instance = new Query( $this->connection );
		$instance->type( 'select' );

		$instance->table( 'foo' );
		$instance->field( 'f', 'a' );

		$instance->condition( $instance->asOr( 'foo_bar' ) );

		$instance->options(
			[
				'HAVING' => 'COUNT(Foo) > 5',
				'GROUP BY' => '_foo',
				'LIMIT' => 42
			]
		);

		$this->assertSame(
			'SELECT f AS a FROM foo WHERE (foo_bar) GROUP BY _foo HAVING COUNT(Foo) > 5 LIMIT 42',
			$instance->build()
		);

	}

	public function testTable() {

		$this->connection->expects( $this->once() )
			->method( 'tableName' )
			->with( $this->equalTo( 'Bar' ) );

		$instance = new Query(
			$this->connection
		);

		$instance->table( 'Bar' );
	}

	public function testJoin() {

		$this->connection->expects( $this->once() )
			->method( 'tableName' )
			->with( $this->equalTo( 'Foo' ) );

		$instance = new Query(
			$this->connection
		);

		$instance->join( 'INNER JOIN', [ 'Foo' => 'bar ...' ] );
	}

	public function testEq() {

		$this->connection->expects( $this->once() )
			->method( 'addQuotes' )
			->with( $this->equalTo( 'Bar' ) )
			->will( $this->returnValue( '`Bar`' ) );

		$instance = new Query(
			$this->connection
		);

		$this->assertSame(
			'Foo=`Bar`',
			$instance->eq( 'Foo', 'Bar' )
		);
	}

	public function testIn() {

		$this->connection->expects( $this->once() )
			->method( 'makeList' )
			->with( $this->equalTo( [ 'a', 'b' ] ) )
			->will( $this->returnValue( 'a, b' ) );

		$instance = new Query(
			$this->connection
		);

		$this->assertSame(
			'Foo IN (a, b)',
			$instance->in( 'Foo', [ 'a', 'b' ] )
		);
	}

	public function testNeq() {

		$this->connection->expects( $this->once() )
			->method( 'addQuotes' )
			->with( $this->equalTo( 'Bar' ) )
			->will( $this->returnValue( '`Bar`' ) );

		$instance = new Query(
			$this->connection
		);

		$this->assertSame(
			'Foo!=`Bar`',
			$instance->neq( 'Foo', 'Bar' )
		);
	}

	public function testExecute() {

		$instance = new Query(
			$this->connection
		);

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with(
				$this->equalTo( $instance ),
				$this->equalTo( 'Foo' ) );


		$instance->execute( 'Foo' );
	}

}
