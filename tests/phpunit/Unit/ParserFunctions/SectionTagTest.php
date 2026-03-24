<?php

namespace SMW\Tests\Unit\ParserFunctions;

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

	public function testParse_NullInput() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->parser->expects( $this->once() )
			->method( 'recursiveTagParse' )
			->with( $this->identicalTo( '' ) )
			->willReturn( '' );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$instance = new SectionTag(
			$this->parser,
			$this->frame
		);

		$this->assertStringContainsString(
			'<section></section>',
			$instance->parse( null, [] )
		);
	}

	public function testParse_WithClassArg() {
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

		$this->assertStringContainsString(
			'<section class="myclass">Foo</section>',
			$instance->parse( 'Foo', [ 'class' => 'myclass' ] )
		);
	}

	public function testParse_WithIdArg() {
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

		$this->assertStringContainsString(
			'<section id="mysection">Foo</section>',
			$instance->parse( 'Foo', [ 'id' => 'mysection' ] )
		);
	}

	public function testParse_WithClassArgInPropertyNamespace() {
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

		// When a class arg is already present, the property spec class is
		// appended with a leading space to form a valid class list.
		$result = $instance->parse( 'Foo', [ 'class' => 'myclass' ] );

		$this->assertStringContainsString( 'myclass smw-property-specification', $result );
	}

}
