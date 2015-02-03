<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\HtmlTemplateRenderer;
use SMW\MediaWiki\WikitextTemplateRenderer;

/**
 * @covers \SMW\MediaWiki\HtmlTemplateRenderer
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class HtmlTemplateRendererTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$wikitextTemplateRenderer = $this->getMockBuilder( '\SMW\MediaWiki\WikitextTemplateRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\HtmlTemplateRenderer',
			 new HtmlTemplateRenderer( $wikitextTemplateRenderer, $parser )
		);
	}

	public function testRenderTemplate() {

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->once() )
			->method( 'recursiveTagParse' )
			->with(
				$this->stringContains( '{{Bar|property=Foo|value=42}}{{Foobaz|property=Bar|value=Foo}}' ) );

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
