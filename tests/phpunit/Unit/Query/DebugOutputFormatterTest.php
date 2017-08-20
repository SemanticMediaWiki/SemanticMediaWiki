<?php

namespace SMW\Tests\Query;

use SMW\Query\DebugOutputFormatter;

/**
 * @covers \SMW\Query\DebugOutputFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class DebugOutputFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testFormatDebugOutputWithoutQuery() {

		$instance = new DebugOutputFormatter();

		$this->assertInternalType(
			'string',
			$instance->getStringFrom( 'foo', array(), null )
		);
	}

	public function testExplainFormat() {

		DebugOutputFormatter::setExplainFormat( DebugOutputFormatter::JSON_FORMAT );

		$this->assertEquals(
			'FORMAT=json',
			DebugOutputFormatter::getFormat( 'mysql' )
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

		$instance = new DebugOutputFormatter();

		$this->assertInternalType(
			'string',
			$instance->getStringFrom( 'foo', array(), $query )
		);
	}

	/**
	 * @dataProvider sqlExplainFormatProvider
	 */
	public function testFormatSQLExplainOutput( $type, $res ) {

		$instance = new DebugOutputFormatter();

		$this->assertInternalType(
			'string',
			$instance->doFormatSQLExplainOutput( $type, $res )
		);
	}

	public function testFormatSPARQLStatement() {

		$instance = new DebugOutputFormatter();

		$sparql = '';

		$this->assertInternalType(
			'string',
			$instance->doFormatSPARQLStatement( $sparql )
		);
	}

	public function testFormatSQLStatement() {

		$instance = new DebugOutputFormatter();

		$sql = '';
		$alias = '';

		$this->assertInternalType(
			'string',
			$instance->doFormatSQLStatement( $sql, $alias )
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
