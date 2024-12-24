<?php

namespace SMW\Tests\ParserFunctions;

use SMW\ParserFunctions\SectionTag;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\ParserFunctions\SectionTag
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  3.0
 *
 * @author mwjames
 */
class SectionTagTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $frame;
	private $parser;

	protected function setUp(): void {
		$this->frame = $this->getMockBuilder( '\PPFrame' )
			->disableOriginalConstructor()
			->getMock();

		$this->parser = $this->getMockBuilder( '\Parser' )
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
		$title = $this->getMockBuilder( '\Title' )
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

		$this->assertContains(
			'<section>Foo</section>',
			$instance->parse( 'Foo', $args )
		);
	}

	public function testParse_PropertyNamespace() {
		$title = $this->getMockBuilder( '\Title' )
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

		$this->assertContains(
			'<section class="smw-property-specification">Foo</section>',
			$instance->parse( 'Foo', $args )
		);
	}

}
