<?php

namespace SMW\Test\Utils;

use SMW\Utils\HtmlColumns;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Utils\HtmlColumns
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlColumnListFormatterTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $stringValidator;

	protected function setUp() {
		parent::setUp();
		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			HtmlColumns::class,
			new HtmlColumns()
		);
	}

	public function testUnknownTypeThrowsException() {

		$instance = new HtmlColumns();

		$this->setExpectedException( 'InvalidArgumentException' );

		$instance->addContents(
			[ 'Foo' ],
			'bar'
		);
	}

	public function testDefaultColumnUnorderedList() {

		$instance = new HtmlColumns();

		$instance->addContents(
			[
				'a' => [ 'Foo', 'Bar' ],
				'B' => [ 'Ichi', 'Ni' ]
			],
			HtmlColumns::INDEXED_LIST
		);

		$expected = [
			'<div class="smw-columnlist-container" dir="ltr">',
			'<div class="smw-column" style="width:100%;" dir="ltr">',
			'<div class="smw-column-header">a</div><ul><li>Foo</li><li>Bar</li></ul>',
			'<div class="smw-column-header">B</div><ul><li>Ichi</li><li>Ni</li></ul></div>'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testTwoColumnUnorderedList() {

		$listContinuesAbbrev = '...';
		$instance = new HtmlColumns();

		$instance->addContents(
			[
				'a' => [ 'Foo', 'Bar' ],
				'B' => [ 'Baz', 'Fom', 'Fin', 'Fum' ]
			],
			HtmlColumns::INDEXED_LIST
		);

		$instance->setColumns( 2 );
		$instance->setContinueAbbrev( $listContinuesAbbrev );

		$expected = [
			'<div class="smw-columnlist-container" dir="ltr">',
			'<div class="smw-column" style="width:50%;" dir="ltr">',
			'<div class="smw-column-header">a</div>',
			'<ul><li>Foo</li><li>Bar</li></ul>',
			'<div class="smw-column-header">B</div><ul><li>Baz</li></ul></div> <!-- end column -->',
			'<div class="smw-column" style="width:50%;" dir="ltr">',
			'<div class="smw-column-header">B ' . $listContinuesAbbrev .'</div>',
			'<ul start=4><li>Fom</li><li>Fin</li><li>Fum</li></ul></div> <!-- end column -->',
			'<br style="clear: both;"/></div>'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testThreeColumnUnorderedList() {

		$instance = new HtmlColumns();

		$instance->addContents(
			[
				'a' => [ 'Foo', 'Bar' ],
				'B' => [ 'Ichi', 'Ni' ]
			],
			HtmlColumns::INDEXED_LIST
		);

		$instance->setColumns( 3 );

		$expected = [
			'<div class="smw-columnlist-container" dir="ltr">',
			'<div class="smw-column" style="width:33%;" dir="ltr">',
			'<div class="smw-column-header">a</div><ul><li>Foo</li><li>Bar</li></ul></div>',
			'<div class="smw-column" style="width:33%;" dir="ltr">',
			'<div class="smw-column-header">B</div><ul><li>Ichi</li><li>Ni</li></ul></div>'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testTwoColumnOrderedList() {

		$instance = new HtmlColumns();

		$instance->addContents(
			[
				'a' => [ 'Foo', 'Bar' ],
				'B' => [ 'Ichi', 'Ni' ]
			],
			HtmlColumns::INDEXED_LIST
		);

		$instance->setColumns( 2 );
		$instance->setListType( 'ol' );

		$expected = [
			'<div class="smw-columnlist-container" dir="ltr">',
			'<div class="smw-column" style="width:50%;" dir="ltr">',
			'<div class="smw-column-header">a</div><ol><li>Foo</li><li>Bar</li></ol></div> <!-- end column -->',
			'<div class="smw-column" style="width:50%;" dir="ltr">',
			'<div class="smw-column-header">B</div><ol><li>Ichi</li><li>Ni</li></ol></div> <!-- end column -->'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testTwoColumnOrderedListNoHeader() {

		$instance = new HtmlColumns();

		$instance->addContents(
			[
				'Foo', 'Baz', 'Bar'
			],
			HtmlColumns::PLAIN_LIST
		);

		$instance->setColumns( 2 );
		$instance->setColumnListClass( 'foo-class' );
		$instance->setColumnClass( 'bar-class' );
		$instance->setListType( 'ul' );

		$expected = [
			'<div class="foo-class" dir="ltr">',
			'<div class="bar-class" style="width:50%;" dir="ltr">',
			'<ul start=1><li>Foo</li><li>Baz</li></ul></div> <!-- end column -->',
			'<div class="bar-class" style="width:50%;" dir="ltr">',
			'<ul start=3><li>Bar</li></ul></div> <!-- end column -->'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testResponsiveColumnsToBeDeterminedByBrowser() {

		$instance = new HtmlColumns();

		$instance->addContents(
			[
				'Foo', 'Baz', 'Bar'
			],
			HtmlColumns::PLAIN_LIST
		);

		$instance->setColumns( 2 );
		$instance->setColumnListClass( 'foo-class' );
		$instance->setResponsiveCols();
		$instance->setResponsiveColsThreshold( 1 );
		$instance->setListType( 'ul' );
		$instance->isRTL( true );

		$expected = [
			'<div class="foo-class" dir="rtl"><div class="smw-column-responsive" style="width:100%;columns:2 20em;" dir="rtl">',
			'<ul start=1><li>Foo</li><li>Baz</li><li>Bar</li></ul></div> <!-- end column -->'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testResponsiveColumnsOnResponsiveColsThreshold() {

		$instance = new HtmlColumns();

		$instance->addContents(
			[
				'Foo', 'Baz', 'Bar'
			],
			HtmlColumns::PLAIN_LIST
		);

		$instance->setColumns( 2 );
		$instance->setColumnListClass( 'foo-class' );
		$instance->setResponsiveCols();
		$instance->setResponsiveColsThreshold( 4 );
		$instance->setListType( 'ul' );
		$instance->isRTL( true );

		$expected = [
			'<div class="foo-class" dir="rtl"><div class="smw-column-responsive" style="width:100%;columns:1 20em;" dir="rtl">',
			'<ul start=1><li>Foo</li><li>Baz</li><li>Bar</li></ul></div> <!-- end column -->'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testItemListWithAttributes() {

		$instance = new HtmlColumns();

		$instance->addContents(
			[
				'Foo', 'Baz', 'Bar'
			],
			HtmlColumns::PLAIN_LIST
		);

		$instance->setColumns( 2 );
		$instance->setColumnListClass( 'foo-class' );
		$instance->setResponsiveCols();
		$instance->setResponsiveColsThreshold( 1 );
		$instance->setListType( 'ul' );
		$instance->isRTL( true );

		$instance->setItemAttributes(
			[
				md5( 'Foo' ) => [
					'id' => 123
				],
				md5( 'Bar' ) => 456
			]
		);

		$expected = [
			'<div class="foo-class" dir="rtl"><div class="smw-column-responsive" style="width:100%;columns:2 20em;" dir="rtl">',
			'<ul start=1><li id="123">Foo</li><li>Baz</li><li 0="456">Bar</li></ul></div> <!-- end column -->'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testOListWithAttributes() {

		$instance = new HtmlColumns();

		$instance->addContents(
			[
				'Foo', 'Baz', 'Bar'
			],
			HtmlColumns::PLAIN_LIST
		);

		$instance->setColumns( 2 );
		$instance->setColumnListClass( 'foo-class' );
		$instance->setResponsiveCols();
		$instance->setResponsiveColsThreshold( 1 );
		$instance->setListType( 'ol', 'i' );
		$instance->isRTL( true );

		$instance->setItemAttributes(
			[
				md5( 'Foo' ) => [
					'id' => 123
				],
				md5( 'Bar' ) => 456
			]
		);

		$expected = [
			'<div class="foo-class" dir="rtl"><div class="smw-column-responsive" style="width:100%;columns:2 20em;" dir="rtl">',
			'<ol type=i start=1><li id="123">Foo</li><li>Baz</li><li 0="456">Bar</li></ol></div> <!-- end column -->'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

}
