<?php

namespace SMW\Tests\Query;

use SMW\Query\DebugFormatter;

/**
 * @covers \SMW\Query\DebugFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class DebugFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testFormatDebugOutputWithoutQuery() {

		$instance = new DebugFormatter();

		$this->assertInternalType(
			'string',
			$instance->getStringFrom( 'foo', array(), null )
		);
	}

	public function testExplainFormat() {

		DebugFormatter::setExplainFormat( DebugFormatter::JSON_FORMAT );

		$this->assertEquals(
			'FORMAT=json',
			DebugFormatter::getFormat( 'mysql' )
		);
	}

	public function testFormatDebugOutputWithQuery() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$query->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$instance = new DebugFormatter();

		$this->assertInternalType(
			'string',
			$instance->getStringFrom( 'foo', array(), $query )
		);
	}

	/**
	 * @dataProvider sqlExplainFormatProvider
	 */
	public function testFormatSQLExplainOutput( $type, $res ) {

		$instance = new DebugFormatter();

		$this->assertInternalType(
			'string',
			$instance->prettifyExplain( $type, $res )
		);
	}

	public function testFormatSPARQLStatement() {

		$instance = new DebugFormatter();

		$sparql = '';

		$this->assertInternalType(
			'string',
			$instance->prettifySparql( $sparql )
		);
	}

	public function testFormatSQLStatement() {

		$instance = new DebugFormatter();

		$sql = '';
		$alias = '';

		$this->assertInternalType(
			'string',
			$instance->prettifySql( $sql, $alias )
		);
	}

	public function sqlExplainFormatProvider() {

		$row = array(
			'id' => '',
			'select_type' => '',
			'table' => '',
			'type' => '',
			'possible_keys' => '',
			'key' => '',
			'key_len' => '',
			'ref' => '',
			'rows' => '',
			'Extra' => ''
		);

		$provider[] = array(
			'mysql',
			array( (object)$row )
		);

		$provider[] = array(
			'postgres',
			array( array( 'QUERY PLAN' => '' ) )
		);

		$provider[] = array(
			'sqlite',
			''
		);

		$row = [
			'EXPLAIN' => 'Foooooooo'
		];

		$provider[] = array(
			'mysql',
			array( (object)$row )
		);

		return $provider;
	}

}
