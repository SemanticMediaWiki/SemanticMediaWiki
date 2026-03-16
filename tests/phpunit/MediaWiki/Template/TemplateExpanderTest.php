<?php

namespace SMW\Tests\MediaWiki\Template;

use MediaWiki\Parser\Parser;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Template\Template;
use SMW\MediaWiki\Template\TemplateExpander;

/**
 * @covers \SMW\MediaWiki\Template\TemplateExpander
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class TemplateExpanderTest extends TestCase {

	private $parser;

	protected function setUp(): void {
		parent::setUp();

		$this->parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TemplateExpander::class,
			 new TemplateExpander( $this->parser )
		);
	}

	public function testExpand() {
		$template = new Template( 'Foo' );

		$this->parser->expects( $this->once() )
			->method( 'preprocess' )
			->with( '{{Foo}}' );

		$instance = new TemplateExpander(
			$this->parser
		);

		$instance->expand( $template );
	}

	public function testExpandOnInvalidParserThrowsException() {
		$instance = new TemplateExpander(
			'Foo'
		);

		$this->expectException( '\RuntimeException' );
		$instance->expand( '' );
	}

}
