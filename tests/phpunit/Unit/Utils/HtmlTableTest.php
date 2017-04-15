<?php

namespace SMW\Tests\Utils;

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

	public function testOpenClose() {

		$this->assertEquals(
			'<div class="smw-table"></div>',
			HtmlTable::open() . HtmlTable::close()
		);

		$this->assertEquals(
			HtmlTable::table(),
			HtmlTable::open() . HtmlTable::close()
		);
	}

	public function testHeader() {

		$this->assertEquals(
			'<div class="smw-table-header bar">foo</div>',
			HtmlTable::header( 'foo', array( 'class' => 'bar' ) )
		);
	}

	public function testBody() {

		$this->assertEquals(
			'<div class="smw-table-body bar">foo</div>',
			HtmlTable::body( 'foo', array( 'class' => 'bar' ) )
		);
	}

	public function testFooter() {

		$this->assertEquals(
			'<div class="smw-table-footer bar">foo</div>',
			HtmlTable::footer( 'foo', array( 'class' => 'bar' ) )
		);
	}

	public function testRow() {

		$this->assertEquals(
			'<div class="smw-table-row bar">foo</div>',
			HtmlTable::row( 'foo', array( 'class' => 'bar' ) )
		);
	}

	public function testCell() {

		$this->assertEquals(
			'<div class="smw-table-cell bar">foo</div>',
			HtmlTable::cell( 'foo', array( 'class' => 'bar' ) )
		);
	}

}
