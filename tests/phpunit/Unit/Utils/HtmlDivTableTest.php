<?php

namespace SMW\Tests\Utils;

use SMW\Utils\HtmlDivTable;

/**
 * @covers \SMW\Utils\HtmlDivTable
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlDivTableTest extends \PHPUnit_Framework_TestCase {

	public function testOpenClose() {

		$this->assertEquals(
			'<div class="smw-table"></div>',
			HtmlDivTable::open() . HtmlDivTable::close()
		);

		$this->assertEquals(
			HtmlDivTable::table(),
			HtmlDivTable::open() . HtmlDivTable::close()
		);
	}

	public function testHeader() {

		$this->assertEquals(
			'<div class="smw-table-header bar">foo</div>',
			HtmlDivTable::header( 'foo', [ 'class' => 'bar' ] )
		);
	}

	public function testBody() {

		$this->assertEquals(
			'<div class="smw-table-body bar">foo</div>',
			HtmlDivTable::body( 'foo', [ 'class' => 'bar' ] )
		);
	}

	public function testFooter() {

		$this->assertEquals(
			'<div class="smw-table-footer bar">foo</div>',
			HtmlDivTable::footer( 'foo', [ 'class' => 'bar' ] )
		);
	}

	public function testRow() {

		$this->assertEquals(
			'<div class="smw-table-row bar">foo</div>',
			HtmlDivTable::row( 'foo', [ 'class' => 'bar' ] )
		);
	}

	public function testCell() {

		$this->assertEquals(
			'<div class="smw-table-cell bar">foo</div>',
			HtmlDivTable::cell( 'foo', [ 'class' => 'bar' ] )
		);
	}

}
