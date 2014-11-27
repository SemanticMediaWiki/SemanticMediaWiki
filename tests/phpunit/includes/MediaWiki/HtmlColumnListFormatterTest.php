<?php

namespace SMW\Test\MediaWiki\Html;

use SMW\Tests\Utils\UtilityFactory;
use SMW\MediaWiki\HtmlColumnListFormatter;

/**
 * @covers \SMW\MediaWiki\HtmlColumnListFormatter
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class ColumnListFormatterTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\HtmlColumnListFormatter',
			new HtmlColumnListFormatter()
		);
	}

	public function testDefaultColumnUnorderedList() {

		$instance = new HtmlColumnListFormatter();

		$instance->addIndexedArrayOfResults( array(
			'a' => array( 'Foo', 'Bar' ),
			'B' => array( 'Ichi', 'Ni' )
		) );

		$expected = array(
			'<div class="smw-columnlist-container">',
			'<div class="smw-column" style="float: left; width:100%; word-wrap: break-word;">',
			'<h3>a</h3><ul><li>Foo</li><li>Bar</li></ul>',
			'<h3>B</h3><ul><li>Ichi</li><li>Ni</li></ul></div>'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testTwoColumnUnorderedList() {

		$instance = new HtmlColumnListFormatter();

		$instance->setNumberOfColumns( 2 );

		$instance->addIndexedArrayOfResults( array(
			'a' => array( 'Foo', 'Bar' ),
			'B' => array( 'Ichi', 'Ni' )
		) );

		$expected = array(
			'<div class="smw-columnlist-container">',
			'<div class="smw-column" style="float: left; width:50%; word-wrap: break-word;">',
			'<h3>a</h3><ul><li>Foo</li><li>Bar</li></ul><h3>B</h3><ul><li>Ichi</li></ul></div>',
			'<div class="smw-column" style="float: left; width:50%; word-wrap: break-word;">',
			'<h3>B cont.</h3><ul start=4><li>Ni</li></ul></div>'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testThreeColumnUnorderedList() {

		$instance = new HtmlColumnListFormatter();

		$instance->setNumberOfColumns( 3 );

		$instance->addIndexedArrayOfResults( array(
			'a' => array( 'Foo', 'Bar' ),
			'B' => array( 'Ichi', 'Ni' )
		) );

		$expected = array(
			'<div class="smw-columnlist-container">',
			'<div class="smw-column" style="float: left; width:33%; word-wrap: break-word;">',
			'<h3>a</h3><ul><li>Foo</li><li>Bar</li></ul></div>',
			'<div class="smw-column" style="float: left; width:33%; word-wrap: break-word;">',
			'<h3>B</h3><ul><li>Ichi</li><li>Ni</li></ul></div>'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testTwoColumnOrderedList() {

		$instance = new HtmlColumnListFormatter();

		$instance
			->setNumberOfColumns( 2 )
			->setListType( 'ol' );

		$instance->addIndexedArrayOfResults( array(
			'a' => array( 'Foo', 'Bar' ),
			'B' => array( 'Ichi', 'Ni' )
		) );

		$expected = array(
			'<div class="smw-columnlist-container">',
			'<div class="smw-column" style="float: left; width:50%; word-wrap: break-word;">',
			'<h3>a</h3><ol><li>Foo</li><li>Bar</li></ol><h3>B</h3><ol><li>Ichi</li></ol></div>',
			'<div class="smw-column" style="float: left; width:50%; word-wrap: break-word;">',
			'<h3>B cont.</h3><ol start=4><li>Ni</li></ol></div>'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

}
