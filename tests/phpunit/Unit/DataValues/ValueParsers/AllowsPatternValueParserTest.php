<?php

namespace SMW\Tests\DataValues\ValueParsers;

use SMW\DataValues\ValueParsers\AllowsPatternValueParser;

/**
 * @covers \SMW\DataValues\ValueParsers\AllowsPatternValueParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsPatternValueParserTest extends \PHPUnit_Framework_TestCase {

	private $mediaWikiNsContentReader;

	protected function setUp() {
		$this->mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueParsers\AllowsPatternValueParser',
			new AllowsPatternValueParser( $this->mediaWikiNsContentReader )
		);
	}

	public function testParseAndMatchFromResource() {

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( " \nFoo|^(Bar|Foo bar)$\n Bar|^(ABC|DEF)$\n" ) );

		$instance = new AllowsPatternValueParser(
			$this->mediaWikiNsContentReader
		);

		$this->assertEquals(
			'^(ABC|DEF)$',
			$instance->parse( 'Bar' )
		);
	}

}
