<?php

namespace SMW\Tests\DataValues\ValueParsers;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\ValueParsers\AllowsPatternValueParser;
use SMW\MediaWiki\MediaWikiNsContentReader;

/**
 * @covers \SMW\DataValues\ValueParsers\AllowsPatternValueParser
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsPatternValueParserTest extends TestCase {

	private $mediaWikiNsContentReader;

	protected function setUp(): void {
		$this->mediaWikiNsContentReader = $this->getMockBuilder( MediaWikiNsContentReader::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AllowsPatternValueParser::class,
			new AllowsPatternValueParser( $this->mediaWikiNsContentReader )
		);
	}

	public function testParseAndMatchFromResource() {
		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->willReturn( " \nFoo|^(Bar|Foo bar)$\n Bar|^(ABC|DEF)$\n" );

		$instance = new AllowsPatternValueParser(
			$this->mediaWikiNsContentReader
		);

		$this->assertEquals(
			'^(ABC|DEF)$',
			$instance->parse( 'Bar' )
		);
	}

}
