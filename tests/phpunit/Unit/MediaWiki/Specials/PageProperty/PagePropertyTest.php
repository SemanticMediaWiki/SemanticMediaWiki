<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\PageProperty;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\PageProperty\PageBuilder;
use SMW\Options;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\PageProperty\PageBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PagePropertyTest extends TestCase {

	private $testEnvironment;
	private $htmlFormRenderer;
	private $options;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->options = new options();

		$this->htmlFormRenderer = $this->getMockBuilder( HtmlFormRenderer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PageBuilder::class,
			new PageBuilder( $this->htmlFormRenderer, $this->options )
		);
	}

	public function testbuildForm() {
		$methods = [
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
		];

		foreach ( $methods as $method ) {
			$this->htmlFormRenderer->expects( $this->any() )
				->method( $method )
				->willReturnSelf();
		}

		$this->htmlFormRenderer->expects( $this->atLeastOnce() )
			->method( 'renderForm' );

		$instance = new PageBuilder(
			$this->htmlFormRenderer,
			$this->options
		);

		$this->assertIsString(

			$instance->buildForm()
		);
	}

	public function testbuildHtml_Empty() {
		$instance = new PageBuilder(
			$this->htmlFormRenderer,
			$this->options
		);

		$this->assertIsString(

			$instance->buildHtml( [] )
		);
	}

	public function testbuildHtml_WithResult() {
		$this->options->set( 'limit', 20 );
		$this->options->set( 'property', 'Bar' );

		$instance = new PageBuilder(
			$this->htmlFormRenderer,
			$this->options
		);

		$this->assertIsString(

			$instance->buildHtml( [ WikiPage::newFromText( 'Foo' ) ] )
		);
	}

}
