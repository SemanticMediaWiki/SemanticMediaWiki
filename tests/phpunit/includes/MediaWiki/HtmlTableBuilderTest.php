<?php

namespace SMW\Test\MediaWiki;

use SMW\Tests\Utils\UtilityFactory;
use SMW\MediaWiki\HtmlTableBuilder;

/**
 * @covers \SMW\MediaWiki\HtmlTableBuilder
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class HtmlTableBuilderTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\HtmlTableBuilder',
			new HtmlTableBuilder()
		);
	}

	public function testAddHeaderItem() {

		$instance = new HtmlTableBuilder();
		$instance->addHeaderItem( 'span', 'lala' );

		$this->stringValidator->assertThatStringContains(
			'<span>lala</span>',
			$instance->getHeaderItems()
		);
	}

	public function testAddTableHeader() {

		$instance = new HtmlTableBuilder();
		$instance->addHeader( 'lala' );

		$this->stringValidator->assertThatStringContains(
			'<th>lala</th>',
			$instance->getHtml()
		);

		$instance = new HtmlTableBuilder( true );
		$instance->addHeader( 'lila' );

		$this->stringValidator->assertThatStringContains(
			'<thead><th>lila</th></thead>',
			$instance->getHtml()
		);
	}

	public function testAddTableRow() {

		$instance = new HtmlTableBuilder();

		$instance
			->addCell( 'lala', array( 'class' => 'foo' ) )
			->addRow()
			->addCell( 'lula' )
			->addRow();

		$this->stringValidator->assertThatStringContains(
			'<tr class="row-odd"><td class="foo">lala</td></tr><tr class="row-even"><td>lula</td></tr>',
			$instance->getHtml()
		);

		$instance = new HtmlTableBuilder();

		$instance
			->setHtmlContext( true )
			->addCell( 'lila' )
			->addRow();

		$this->stringValidator->assertThatStringContains(
			'<tbody><tr class="row-odd"><td>lila</td></tr></tbody>',
			$instance->getHtml()
		);
	}

	public function testStandardTable() {

		$instance = new HtmlTableBuilder();

		$instance
			->addCell( 'lala', array( 'rel' => 'tuuu' ) )
			->addRow( array( 'class' => 'foo' ) );

		$this->stringValidator->assertThatStringContains(
			'<table><tr class="foo row-odd"><td rel="tuuu">lala</td></tr></table>',
			$instance->getHtml()
		);

		$instance = new HtmlTableBuilder();

		$instance
			->addHeader( 'lula' )
			->addCell( 'lala' )
			->addRow();

		$this->stringValidator->assertThatStringContains(
			'<table><th>lula</th><tr class="row-odd"><td>lala</td></tr></table>',
			$instance->getHtml()
		);

		$instance = new HtmlTableBuilder( true );

		$instance
			->addHeader( 'lula' )
			->addCell( 'lala' )
			->addRow();

		$this->stringValidator->assertThatStringContains(
			'<table><thead><th>lula</th></thead><tbody><tr class="row-odd"><td>lala</td></tr></tbody></table>',
			$instance->getHtml()
		);
	}

	public function testTransposedTable() {

		$instance = new HtmlTableBuilder();

		// We need a dedicated header definition to support a table transpose
		$instance
			->transpose( true )
			->addHeader( 'Foo' )->addHeader( 'Bar' )
			->addCell( 'lala', array( 'class' => 'foo' ) )
			->addRow()
			->addCell( 'lula', array( 'rel' => 'tuuu' ) )->addCell( 'lila' )
			->addRow();

		$this->stringValidator->assertThatStringContains(
			'<table><tr class="row-odd"><th>Foo</th><td class="foo">lala</td><td rel="tuuu">lula</td></tr><tr class="row-even"><th>Bar</th><td></td><td>lila</td></tr></table>',
			$instance->getHtml()
		);

		$instance = new HtmlTableBuilder( true );

		$instance
			->transpose( true )
			->addHeader( 'Foo' )->addHeader( 'Bar' )
			->addCell( 'lala', array( 'class' => 'foo' ) )
			->addRow()
			->addCell( 'lula' )->addCell( 'lila' )
			->addRow();

		$this->stringValidator->assertThatStringContains(
			'<table><thead></thead><tbody><tr class="row-odd"><th>Foo</th><td class="foo">lala</td><td>lula</td></tr><tr class="row-even"><th>Bar</th><td></td><td>lila</td></tr></tbody></table>',
			$instance->getHtml()
		);
	}

	public function testEmptyTable() {

		$instance = new HtmlTableBuilder();

		$instance
			->addCell()
			->addRow();

		$this->assertEmpty(
			$instance->getHtml()
		);
	}

}
