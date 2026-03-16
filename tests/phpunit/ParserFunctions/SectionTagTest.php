<?php

namespace SMW\Tests\ParserFunctions;

use MediaWiki\Parser\Parser;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\ParserFunctions\SectionTag;

/**
 * @covers \SMW\ParserFunctions\SectionTag
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since  3.0
 *
 * @author mwjames
 */
class SectionTagTest extends TestCase {

	private $frame;
	private $parser;

	protected function setUp(): void {
		$this->frame = $this->getMockBuilder( '\PPFrame' )
			->disableOriginalConstructor()
			->getMock();

		$this->parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SectionTag::class,
			new SectionTag( $this->parser, $this->frame )
		);
	}

	public function testRegister_Enabled() {
		$this->parser->expects( $this->once() )
			->method( 'setHook' );

		$this->assertTrue(
			SectionTag::register( $this->parser )
		);
	}

	public function testRegister_Disabled() {
		$this->parser->expects( $this->never() )
			->method( 'setHook' );

		$this->assertFalse(
			SectionTag::register( $this->parser, false )
		);
	}

	public function testParse() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->parser->expects( $this->any() )
			->method( 'recursiveTagParse' )
			->willReturn( 'Foo' );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$instance = new SectionTag(
			$this->parser,
			$this->frame
		);

		$args = [];

		$this->assertStringContainsString(
			'<section>Foo</section>',
			$instance->parse( 'Foo', $args )
		);
	}

	public function testParse_PropertyNamespace() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( SMW_NS_PROPERTY );

		$this->parser->expects( $this->any() )
			->method( 'recursiveTagParse' )
			->willReturn( 'Foo' );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$instance = new SectionTag(
			$this->parser,
			$this->frame
		);

		$args = [];

		$this->assertStringContainsString(
			'<section class="smw-property-specification">Foo</section>',
			$instance->parse( 'Foo', $args )
		);
	}

}
