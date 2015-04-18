<?php

namespace SMW\Test\MediaWiki\Renderer;

use SMW\Tests\Utils\UtilityFactory;
use SMW\MediaWiki\Renderer\HtmlColumnListRenderer;

/**
 * @covers \SMW\MediaWiki\Renderer\HtmlColumnListRenderer
 *
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

		$instance->addContentsByIndex( array(
			'a' => array( 'Foo', 'Bar' ),
			'B' => array( 'Ichi', 'Ni' )
		) );

		$expected = array(
			'<div class="smw-columnlist-container">',
			'<div class="smw-column" style="float: left; width:100%; word-wrap: break-word;">',
			'<div class="smw-column-header">a</div><ul><li>Foo</li><li>Bar</li></ul>',
			'<div class="smw-column-header">B</div><ul><li>Ichi</li><li>Ni</li></ul></div>'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testTwoColumnUnorderedList() {

		$instance = new HtmlColumnListRenderer();

		$instance->setNumberOfColumns( 2 );

		$listContinuesAbbrev = wfMessage( 'listingcontinuesabbrev' )->text();

		$instance->addContentsByIndex( array(
			'a' => array( 'Foo', 'Bar' ),
			'B' => array( 'Ichi', 'Ni' )
		) );

		$expected = array(
			'<div class="smw-columnlist-container">',
			'<div class="smw-column" style="float: left; width:50%; word-wrap: break-word;">',
			'<div class="smw-column-header">a</div><ul><li>Foo</li><li>Bar</li></ul></div> <!-- end column -->',
			'<div class="smw-column" style="float: left; width:50%; word-wrap: break-word;">',
			'<div class="smw-column-header">B</div><ul><li>Ichi</li><li>Ni</li></ul></div> <!-- end column -->'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testThreeColumnUnorderedList() {

		$instance = new HtmlColumnListRenderer();

		$instance->setNumberOfColumns( 3 );

		$instance->addContentsByIndex( array(
			'a' => array( 'Foo', 'Bar' ),
			'B' => array( 'Ichi', 'Ni' )
		) );

		$expected = array(
			'<div class="smw-columnlist-container">',
			'<div class="smw-column" style="float: left; width:33%; word-wrap: break-word;">',
			'<div class="smw-column-header">a</div><ul><li>Foo</li><li>Bar</li></ul></div>',
			'<div class="smw-column" style="float: left; width:33%; word-wrap: break-word;">',
			'<div class="smw-column-header">B</div><ul><li>Ichi</li><li>Ni</li></ul></div>'
		);

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

		$listContinuesAbbrev = wfMessage( 'listingcontinuesabbrev' )->text();

		$instance->addContentsByIndex( array(
			'a' => array( 'Foo', 'Bar' ),
			'B' => array( 'Ichi', 'Ni' )
		) );

		$expected = array(
			'<div class="smw-columnlist-container">',
			'<div class="smw-column" style="float: left; width:50%; word-wrap: break-word;">',
			'<div class="smw-column-header">a</div><ol><li>Foo</li><li>Bar</li></ol></div> <!-- end column -->',
			'<div class="smw-column" style="float: left; width:50%; word-wrap: break-word;">',
			'<div class="smw-column-header">B</div><ol><li>Ichi</li><li>Ni</li></ol></div> <!-- end column -->'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

}
