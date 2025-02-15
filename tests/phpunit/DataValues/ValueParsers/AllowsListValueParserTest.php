<?php

namespace SMW\Tests\DataValues\ValueParsers;

use SMW\DataValues\ValueParsers\AllowsListValueParser;

/**
 * @covers \SMW\DataValues\ValueParsers\AllowsListValueParser
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class AllowsListValueParserTest extends \PHPUnit\Framework\TestCase {

	private $mediaWikiNsContentReader;

	protected function setUp(): void {
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
