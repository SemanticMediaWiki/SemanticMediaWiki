<?php

namespace SMW\Tests\Unit\MediaWiki\Renderer;

use MediaWiki\Parser\Parser;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Renderer\HtmlTemplateRenderer;
use SMW\MediaWiki\Renderer\WikitextTemplateRenderer;

/**
 * @covers \SMW\MediaWiki\Renderer\HtmlTemplateRenderer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   2.2
 *
 * @author mwjames
 */
class HtmlTemplateRendererTest extends TestCase {

	public function testCanConstruct() {
		$wikitextTemplateRenderer = $this->getMockBuilder( WikitextTemplateRenderer::class )
			->disableOriginalConstructor()
			->getMock();

		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			HtmlTemplateRenderer::class,
			 new HtmlTemplateRenderer( $wikitextTemplateRenderer, $parser )
		);
	}

	public function testRenderTemplate() {
		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->once() )
			->method( 'recursiveTagParse' )
			->with(
				$this->stringContains( "{{Bar\n|property=Foo\n|value=42}}{{Foobaz\n|property=Bar\n|value=Foo}}" ) );

		$instance = new HtmlTemplateRenderer(
			new WikitextTemplateRenderer(),
			$parser
		);

		$this->assertEmpty(
			$instance->render()
		);

		$instance->addField( 'property', 'Foo' );
		$instance->addField( 'value', 42 );

		$instance->packFieldsForTemplate( 'Bar' );

		$instance->addField( 'property', 'Bar' );
		$instance->addField( 'value', 'Foo' );

		$instance->packFieldsForTemplate( 'Foobaz' );

		$instance->render();

		$this->assertEmpty(
			$instance->render()
		);
	}

}
