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
			$instance->getStringFrom( 'foo', [], null )
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
			->will( $this->returnValue( [] ) );

		$instance = new DebugOutputFormatter();

		$this->assertInternalType(
			'string',
			$instance->getStringFrom( 'foo', [], $query )
		);
	}

	/**
	 * @dataProvider sqlFormatProvider
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

	public function sqlFormatProvider() {

		$mysqlFormat = [
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
		];

		$provider[] = [
			'mysql',
			[ (object)$mysqlFormat ]
		];

		$provider[] = [
			'postgres',
			[ [ 'QUERY PLAN' => '' ] ]
		];

		$provider[] = [
			'sqlite',
			''
		];

		return $provider;
	}

}
