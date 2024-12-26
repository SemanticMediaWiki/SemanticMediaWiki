<?php

namespace SMW\Tests\Query;

use SMW\Query\DebugFormatter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\DebugFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class DebugFormatterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testFormatDebugOutputWithoutQuery() {
		$instance = new DebugFormatter();

		$this->assertIsString(

			$instance->buildHTML( [], null )
		);
	}

	public function testExplainFormat() {
		$instance = new DebugFormatter( 'mysql', DebugFormatter::JSON_FORMAT );

		$this->assertEquals(
			'FORMAT=json',
			$instance->getFormat()
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
			->willReturn( $description );

		$query->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new DebugFormatter();

		$this->assertIsString(

			$instance->buildHTML( [], $query )
		);
	}

	/**
	 * @dataProvider sqlExplainFormatProvider
	 */
	public function testFormatSQLExplainOutput( $type, $res ) {
		$instance = new DebugFormatter();

		$this->assertIsString(

			$instance->prettifyExplain( $res )
		);
	}

	public function testFormatSPARQLStatement() {
		$instance = new DebugFormatter();

		$sparql = '';

		$this->assertIsString(

			$instance->prettifySPARQL( $sparql )
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

		$this->assertIsString(

			$instance->prettifySQL( $sql, $alias )
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
			[]
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
