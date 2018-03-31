<?php

namespace SMW\Tests\MediaWiki\Specials\PageProperty;

use SMW\MediaWiki\Specials\PageProperty\PageBuilder;
use SMW\Options;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\PageProperty\PageBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PagePropertyTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $htmlFormRenderer;
	private $options;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->options = new options();

		$this->htmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PageBuilder::class,
			new PageBuilder( $this->htmlFormRenderer, $this->options )
		);
	}

	public function testGetForm() {

		$methods = array(
			'setName',
			'withFieldset',
			'addHorizontalRule',
			'openElement',
			'closeElement',
			'addNonBreakingSpace',
			'addInputField',
			'addHiddenField',
			'addHeader',
			'addParagraph',
			'addPaging',
			'addSubmitButton'
		);

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->will( $this->returnSelf() );
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'getForm' );

		$instance = new PageBuilder(
			$this->htmlFormRenderer,
			$this->options
		);

		$this->assertInternalType(
			'string',
			$instance->getForm()
		);
	}

	public function testGetHtml_Empty() {

		$instance = new PageBuilder(
			$this->htmlFormRenderer,
			$this->options
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml( [] )
		);
	}

	public function testGetHtml_WithResult() {

		$this->options->set( 'limit', 20 );
		$this->options->set( 'property', 'Bar' );

		$instance = new PageBuilder(
			$this->htmlFormRenderer,
			$this->options
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml( [ DIWikiPage::newFromText( 'Foo' ) ] )
		);
	}

}
