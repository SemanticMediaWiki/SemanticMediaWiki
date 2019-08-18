<?php

namespace SMW\Tests\Utils\Html;

use SMW\Tests\TestEnvironment;
use SMW\Utils\Html\SummaryTable;

/**
 * @covers \SMW\Utils\Html\SummaryTable
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SummaryTableTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();
	}

	public function testBuildHTML() {

		$instance = new SummaryTable(
			[ 'Foo' => 'Bar' ]
		);

		$this->stringValidator->assertThatStringContains(
			[
				'<div class="smw-summarytable"><div class="smw-table smwfacttable">',
				'<div class="smw-table-row"><div class="smw-table-cell smwpropname">Foo</div>',
				'<div class="smw-table-cell smwprops">Bar</div></div></div></div>'
			],
			$instance->buildHTML()
		);
	}

	public function testBuildHTML_SetAttributes() {

		$instance = new SummaryTable(
			[ 'Foo' => 'Bar' ]
		);

		$instance->setAttributes(
			[
				'Foo' => [ 'style' => 'display:none;' ]
			]
		);

		$this->stringValidator->assertThatStringContains(
			[
				'<div class="smw-summarytable"><div class="smw-table smwfacttable">',
				'<div class="smw-table-row" style="display:none;"><div class="smw-table-cell smwpropname">Foo</div>',
				'<div class="smw-table-cell smwprops">Bar</div></div></div></div>'
			],
			$instance->buildHTML()
		);
	}

	public function testBuildHTML_ColumnThreshold() {

		$instance = new SummaryTable(
			[ 'Foo' => 'Bar', 'Foobar' => 'Bar' ]
		);

		$instance->setColumnThreshold( 1 );

		$this->stringValidator->assertThatStringContains(
			[
				'<div class="smw-summarytable-columns"><div class="smw-summarytable-columns-2">',
				'<div class="smw-summarytable"><div class="smw-table smwfacttable">',
				'<div class="smw-table-row"><div class="smw-table-cell smwpropname">Foo</div>',
				'<div class="smw-table-cell smwprops">Bar</div></div></div></div></div>',
				'<div class="smw-summarytable-columns-2"><div class="smw-summarytable"><div class="smw-table smwfacttable">',
				'<div class="smw-table-row"><div class="smw-table-cell smwpropname">Foobar</div>',
				'<div class="smw-table-cell smwprops">Bar</div></div></div></div></div></div>'
			],
			$instance->buildHTML( ['columns' => 2 ] )
		);
	}

	public function testBuildHTML_ColumnThreshold_NoImage() {

		$instance = new SummaryTable(
			[ 'Foo' => 'Bar', 'Foobar' => 'Bar' ]
		);

		$instance->noImage();
		$instance->setColumnThreshold( 1 );

		$this->stringValidator->assertThatStringContains(
			[
				'<div class="smw-summarytable-imagecolumn"><div class="smw-summarytable-facts">',
				'<div class="smw-summarytable"><div class="smw-table smwfacttable">',
				'<div class="smw-table-row"><div class="smw-table-cell smwpropname">Foo</div><div class="smw-table-cell smwprops">Bar</div></div>',
				'<div class="smw-table-row"><div class="smw-table-cell smwpropname">Foobar</div><div class="smw-table-cell smwprops">Bar</div></div></div></div></div>',
				'<div class="smw-summarytable-image"><div class="smw-summarytable-item-center"><div class="smw-summarytable-noimage"></div></div></div></div>'
			],
			$instance->buildHTML( ['columns' => 2 ] )
		);
	}

}
