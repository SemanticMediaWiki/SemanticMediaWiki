<?php

namespace SMW\Test\MediaWiki\Renderer;

use SMW\MediaWiki\Renderer\HtmlColumnListRenderer;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\MediaWiki\Renderer\HtmlColumnListRenderer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class HtmlColumnListFormatterTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Renderer\HtmlColumnListRenderer',
			new HtmlColumnListRenderer()
		);
	}

	public function testDefaultColumnUnorderedList() {

		$instance = new HtmlColumnListRenderer();

		$instance->addContentsByIndex( [
			'a' => [ 'Foo', 'Bar' ],
			'B' => [ 'Ichi', 'Ni' ]
		] );

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

		$instance = new HtmlColumnListRenderer();

		$instance->setNumberOfColumns( 2 );

		$instance->addContentsByIndex( [
			'a' => [ 'Foo', 'Bar' ],
			'B' => [ 'Baz', 'Fom', 'Fin', 'Fum' ]
		] );

		$listContinuesAbbrev = wfMessage( 'listingcontinuesabbrev' )->text();

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

		$instance = new HtmlColumnListRenderer();

		$instance->setNumberOfColumns( 3 );

		$instance->addContentsByIndex( [
			'a' => [ 'Foo', 'Bar' ],
			'B' => [ 'Ichi', 'Ni' ]
		] );

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

		$instance = new HtmlColumnListRenderer();

		$instance
			->setNumberOfColumns( 2 )
			->setListType( 'ol' );

		$instance->addContentsByIndex( [
			'a' => [ 'Foo', 'Bar' ],
			'B' => [ 'Ichi', 'Ni' ]
		] );

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

		$instance = new HtmlColumnListRenderer();

		$instance
			->setNumberOfColumns( 2 )
			->setColumnListClass( 'foo-class' )
			->setColumnClass( 'bar-class' )
			->setListType( 'ul' );

		$instance->addContentsByNoIndex(
			[ 'Foo', 'Baz', 'Bar' ]
		);

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

		$instance = new HtmlColumnListRenderer();

		$instance->setColumnListClass( 'foo-class' )
			->setNumberOfColumns( 2 ) // being set to 1 when it is responsive
			->setColumnClass( 'bar-responsive' )
			->setColumnRTLDirectionalityState( true )
			->setListType( 'ul' );

		$instance->addContentsByNoIndex(
			[ 'Foo', 'Baz', 'Bar' ]
		);

		$expected = [
			'<div class="foo-class" dir="rtl"><div class="bar-responsive" style="width:100%;" dir="rtl">',
			'<ul start=1><li>Foo</li><li>Baz</li><li>Bar</li></ul></div> <!-- end column -->'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testItemListWithAttributes() {

		$instance = new HtmlColumnListRenderer();

		$instance->setColumnListClass( 'foo-class' )
			->setNumberOfColumns( 2 ) // being set to 1 when it is responsive
			->setColumnClass( 'bar-responsive' )
			->setColumnRTLDirectionalityState( true )
			->setListType( 'ul' );

		$instance->addContentsByNoIndex(
			[ 'Foo', 'Baz', 'Bar' ]
		);

		$instance->setItemAttributes(
			[
				md5( 'Foo' ) => [
					'id' => 123
				],
				md5( 'Bar' ) => 456
			]
		);

		$expected = [
			'<div class="foo-class" dir="rtl"><div class="bar-responsive" style="width:100%;" dir="rtl">',
			'<ul start=1><li id="123">Foo</li><li>Baz</li><li 0="456">Bar</li></ul></div> <!-- end column -->'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testOListWithAttributes() {

		$instance = new HtmlColumnListRenderer();

		$instance->setColumnListClass( 'foo-class' )
			->setNumberOfColumns( 2 ) // being set to 1 when it is responsive
			->setColumnClass( 'bar-responsive' )
			->setColumnRTLDirectionalityState( true )
			->setListType( 'ol', 'i' );

		$instance->addContentsByNoIndex(
			[ 'Foo', 'Baz', 'Bar' ]
		);

		$instance->setItemAttributes(
			[
				md5( 'Foo' ) => [
					'id' => 123
				],
				md5( 'Bar' ) => 456
			]
		);

		$expected = [
			'<div class="foo-class" dir="rtl"><div class="bar-responsive" style="width:100%;" dir="rtl">',
			'<ol type=i start=1><li id="123">Foo</li><li>Baz</li><li 0="456">Bar</li></ol></div> <!-- end column -->'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

}
