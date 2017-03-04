<?php

namespace SMW\Tests\DataValues\ValueParsers;

use SMW\DataValues\ValueParsers\AllowsListValueParser;

/**
 * @covers \SMW\DataValues\ValueParsers\AllowsListValueParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class AllowsListValueParserTest extends \PHPUnit_Framework_TestCase {

	private $mediaWikiNsContentReader;

	protected function setUp() {
		$this->mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueParsers\AllowsListValueParser',
			new AllowsListValueParser( $this->mediaWikiNsContentReader )
		);
	}

	public function testParseAndMatchFromResource() {

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( " \n*Foo\n**Foobar|bar\n" ) );

		$instance = new AllowsListValueParser(
			$this->mediaWikiNsContentReader
		);

		$this->assertEquals(
			array(
				'Foo' => 'Foo',
				'Foobar' => 'bar'
			),
			$instance->parse( 'Bar' )
		);
	}

}
