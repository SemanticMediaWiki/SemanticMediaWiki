<?php

namespace SMW\Tests\Unit\Query;

use PHPUnit\Framework\TestCase;
use SMW\Query\DebugFormatter;
use SMW\Query\Language\Description;
use SMW\Query\Query;

/**
 * @covers \SMW\Query\DebugFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class DebugFormatterTest extends TestCase {

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
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = $this->getMockBuilder( Query::class )
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

	public function testBuildHTMLEscapesQueryStringMarkup() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$description->expects( $this->any() )
			->method( 'getQueryString' )
			->willReturn( '[[Has text::<script>alert(1)</script>]]' );

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->willReturn( $description );

		$query->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new DebugFormatter();
		$html = $instance->buildHTML( [], $query );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	public function testBuildHTMLEscapesRawErrorMarkup() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getDescription' )
			->willReturn( $description );

		$query->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [ '<script>alert(1)</script>' ] );

		$instance = new DebugFormatter();
		$html = $instance->buildHTML( [], $query );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	public function testPrettifySQLEscapesMarkup() {
		$instance = new DebugFormatter();

		$sql = "SELECT * FROM t0 WHERE t0.smw_title = '<script>alert(1)</script>'";
		$html = $instance->prettifySQL( $sql, 't0' );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	public function testPrettifyExplainEscapesPostgresValueMarkup() {
		$instance = new DebugFormatter( 'postgres' );

		$res = [ [ 'QUERY PLAN' => "Filter: (smw_title = '<script>alert(1)</script>'::text)" ] ];
		$html = $instance->prettifyExplain( $res );

		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringContainsString( '&lt;script', $html );
	}

	public function testPrettifyExplainEscapesMysqlColumnMarkup() {
		$instance = new DebugFormatter( 'mysql' );

		$row = [
			'id' => '1', 'select_type' => 'SIMPLE', 'table' => 't0', 'type' => 'ref',
			'possible_keys' => '', 'key' => '', 'key_len' => '', 'ref' => '',
			'rows' => '1', 'filtered' => '100', 'Extra' => '<script>alert(1)</script>'
		];

		$html = $instance->prettifyExplain( [ (object)$row ] );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	public function testPrettifyExplainEscapesMysqlExplainColumnMarkup() {
		$instance = new DebugFormatter( 'mysql' );

		$html = $instance->prettifyExplain( [ (object)[ 'EXPLAIN' => '<script>alert(1)</script>' ] ] );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	public function testPrettifyExplainEscapesSqliteDetailMarkup() {
		$instance = new DebugFormatter( 'sqlite' );

		$html = $instance->prettifyExplain( [ (object)[ 'id' => 0, 'detail' => '<script>alert(1)</script>' ] ] );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
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
