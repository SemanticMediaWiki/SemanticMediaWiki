<?php

namespace SMW\Tests\DataValues\ValueParsers;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\ValueParsers\AllowsListValueParser;
use SMW\MediaWiki\MediaWikiNsContentReader;

/**
 * @covers \SMW\DataValues\ValueParsers\AllowsListValueParser
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class AllowsListValueParserTest extends TestCase {

	private $mediaWikiNsContentReader;

	protected function setUp(): void {
		$this->mediaWikiNsContentReader = $this->getMockBuilder( MediaWikiNsContentReader::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AllowsListValueParser::class,
			new AllowsListValueParser( $this->mediaWikiNsContentReader )
		);
	}

	public function testParseAndMatchFromResource() {
		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->willReturn( " \n*Foo\n**Foobar|bar\n" );

		$instance = new AllowsListValueParser(
			$this->mediaWikiNsContentReader
		);

		$this->assertEquals(
			[
				'Foo' => 'Foo',
				'Foobar' => 'bar'
			],
			$instance->parse( 'Bar' )
		);
	}

	public function testParseAndMatchFromJSON() {
		$contents = json_encode( [ 'Foo' => 'Foo', 'Foobar' => 'fooooo bar' ] );

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->willReturn( $contents );

		$instance = new AllowsListValueParser(
			$this->mediaWikiNsContentReader
		);

		$instance->clear();

		$this->assertEquals(
			[
				'Foo' => 'Foo',
				'Foobar' => 'fooooo bar'
			],
			$instance->parse( 'Bar' )
		);
	}

}
