<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\WikitextTemplateRenderer;

/**
 * @covers \SMW\MediaWiki\WikitextTemplateRenderer
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class WikitextTemplateRendererTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\WikitextTemplateRenderer',
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
			'{{Bar|property=Foo|value=42}}{{Foobaz|property=Bar|value=Foo}}',
			$instance->render()
		);

		$this->assertEmpty(
			$instance->render()
		);
	}

}
