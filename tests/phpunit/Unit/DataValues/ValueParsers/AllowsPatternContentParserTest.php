<?php

namespace SMW\Tests\DataValues\ValueParsers;

use SMW\DataValues\ValueParsers\AllowsPatternContentParser;

/**
 * @covers \SMW\DataValues\ValueParsers\AllowsPatternContentParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsPatternContentParserTest extends \PHPUnit_Framework_TestCase {

	private $mediaWikiNsContentReader;

	protected function setUp() {
		$this->mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueParsers\AllowsPatternContentParser',
			new AllowsPatternContentParser( $this->mediaWikiNsContentReader )
		);
	}

	public function testParseAndMatchFromResource() {

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( " \nFoo|^(Bar|Foo bar)$\n Bar|^(ABC|DEF)$\n" ) );

		$instance = new AllowsPatternContentParser(
			$this->mediaWikiNsContentReader
		);

		$this->assertEquals(
			'^(ABC|DEF)$',
			$instance->parse( 'Bar' )
		);
	}

}
