<?php

namespace SMW\Tests\Utils;

use SMW\Tests\TestEnvironment;
use SMW\Utils\HtmlTable;

/**
 * @covers \SMW\Utils\HtmlTable
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlTableTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();
	}

	public function testStandardTable_Cell_Row() {

		$instance = new HtmlTable();

		$instance->cell( 'Foo', [ 'rel' => 'some' ] );
		$instance->row( [ 'class' => 'bar' ] );

		$this->stringValidator->assertThatStringContains(
			'<table><tr class="bar row-odd"><td rel="some">Foo</td></tr></table>',
			$instance->table()
		);
	}

	public function testStandardTable_Header_Cell_Row() {

		$instance = new HtmlTable();

		$instance->header( 'Foo ');
		$instance->cell( 'Bar' );
		$instance->row();

		$this->stringValidator->assertThatStringContains(
			'<table><th>Foo </th><tr class="row-odd"><td>Bar</td></tr></table>',
			$instance->table()
		);
	}

	public function testStandardTable_Header_Cell_Row_IsHtml() {

		$instance = new HtmlTable();

		$instance->header( 'Foo' );
		$instance->cell( 'Bar' );
		$instance->row();

		$this->stringValidator->assertThatStringContains(
			'<table><thead><th>Foo</th></thead><tbody><tr class="row-odd"><td>Bar</td></tr></tbody></table>',
			$instance->table( [], false, true )
		);
	}

	public function testTransposedTable_Cell_Row() {

		$instance = new HtmlTable();

		// We need a dedicated header definition to support a table transpose
		$instance->header( 'Foo' );
		$instance->header( 'Bar' );

		$instance->cell( 'lala', [ 'class' => 'foo' ] );
		$instance->row();

		$instance->cell( 'lula', [ 'rel' => 'tuuu' ] );
		$instance->cell( 'lila' );
		$instance->row();

		$this->stringValidator->assertThatStringContains(
			[
				'<table data-transpose="1"><tr class="row-odd"><th>Foo</th>',
				'<td class="foo">lala</td><td rel="tuuu">lula</td></tr><tr class="row-even"><th>Bar</th>',
				'<td></td><td>lila</td></tr></table>'
			],
			$instance->table( [], true )
		);
	}

	public function testTransposedTable_Cell_Row_IsHtml() {

		$instance = new HtmlTable();

		// We need a dedicated header definition to support a table transpose
		$instance->header( 'Foo' );
		$instance->header( 'Bar' );

		$instance->cell( 'lala', [ 'class' => 'foo' ] );
		$instance->row();

		$instance->cell( 'lula', [ 'rel' => 'tuuu' ] );
		$instance->cell( 'lila' );
		$instance->row();

		$this->stringValidator->assertThatStringContains(
			[
				'<table data-transpose="1"><tbody><tr class="row-odd"><th>Foo</th><td class="foo">lala</td>',
				'<td rel="tuuu">lula</td></tr><tr class="row-even"><th>Bar</th><td></td><td>lila</td></tr>',
				'</tbody></table>'
			],
			$instance->table( [], true, true )
		);
	}

}
