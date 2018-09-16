<?php

namespace SMW\Tests\ParserFunctions;

use SMW\ParserFunctions\SectionTag;

/**
 * @covers \SMW\ParserFunctions\SectionTag
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  3.0
 *
 * @author mwjames
 */
class SectionTagTest extends \PHPUnit_Framework_TestCase {

	private $frame;
	private $parser;

	protected function setUp() {

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
			->will( $this->returnValue( 'Foo' ) );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

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
			->will( $this->returnValue( SMW_NS_PROPERTY ) );

		$this->parser->expects( $this->any() )
			->method( 'recursiveTagParse' )
			->will( $this->returnValue( 'Foo' ) );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

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
