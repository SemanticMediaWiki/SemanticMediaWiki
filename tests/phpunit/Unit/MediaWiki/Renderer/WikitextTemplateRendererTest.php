<?php

namespace SMW\Tests\Unit\MediaWiki\Renderer;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Renderer\WikitextTemplateRenderer;

/**
 * @covers \SMW\MediaWiki\Renderer\WikitextTemplateRenderer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   2.2
 *
 * @author mwjames
 */
class WikitextTemplateRendererTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			WikitextTemplateRenderer::class,
			 new WikitextTemplateRenderer()
		);
	}

	public function testRenderTemplate() {
		$instance = new WikitextTemplateRenderer();

		$this->assertEmpty(
			$instance->render()
		);

		$instance->addField( 'property', 'Foo' );
		$instance->addField( 'value', 42 );

		$instance->packFieldsForTemplate( 'Bar' );

		$instance->addField( 'property', 'Bar' );
		$instance->addField( 'value', 'Foo' );

		$instance->packFieldsForTemplate( 'Foobaz' );

		$this->assertEquals(
			"{{Bar\n|property=Foo\n|value=42}}{{Foobaz\n|property=Bar\n|value=Foo}}",
			$instance->render()
		);

		$this->assertEmpty(
			$instance->render()
		);
	}

}
