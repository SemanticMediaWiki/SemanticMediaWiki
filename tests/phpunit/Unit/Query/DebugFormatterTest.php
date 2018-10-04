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
			$instance->getStringFrom( 'foo', [], null )
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
			->will( $this->returnValue( [] ) );

		$instance = new DebugFormatter();

		$this->assertInternalType(
			'string',
			$instance->getStringFrom( 'foo', [], $query )
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

		$this->assertEquals(
			'<div class="smwpre">&#91;&#x003A;&#x0020;&#x3C;Foo&#x3E;&#x0020;]</div>',
			$instance->prettifySparql( '[: <Foo> ]' )
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

		$row = [
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
			[ (object)$row ]
		];

		$provider[] = [
			'postgres',
			[ [ 'QUERY PLAN' => '' ] ]
		];

		$provider[] = [
			'sqlite',
			''
		];

		$row = [
			'EXPLAIN' => 'Foooooooo'
		];

		$provider[] = [
			'mysql',
			[ (object)$row ]
		];

		return $provider;
	}

}
