<?php

namespace SMW\Tests\MediaWiki;

use MediaWiki\Language\Language;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\MessageBuilder;

/**
 * @covers \SMW\MediaWiki\MessageBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class MessageBuilderTest extends TestCase {

	public function testCanConstruct() {
		$language = $this->getMockBuilder( Language::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			MessageBuilder::class,
			new MessageBuilder( $language )
		);
	}

	public function testFormatNumberToText() {
		$language = $this->getMockBuilder( Language::class )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->once() )
			->method( 'formatNum' );

		$instance = new MessageBuilder();

		$instance
			->setLanguage( $language )
			->formatNumberToText( 42 );
	}

	public function testListToCommaSeparatedText() {
		$language = $this->getMockBuilder( Language::class )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->once() )
			->method( 'listToText' );

		$context = $this->getMockBuilder( '\IContextSource' )
			->disableOriginalConstructor()
			->getMock();

		$context->expects( $this->once() )
			->method( 'getLanguage' )
			->willReturn( $language );

		$instance = new MessageBuilder();

		$instance
			->setLanguageFromContext( $context )
			->listToCommaSeparatedText( [ 'a', 'b' ] );
	}

	public function testPrevNextToText() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$language = $this->getMockBuilder( Language::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MessageBuilder( $language );
		$html = $instance->prevNextToText( $title, 20, 0, [], false );

		$this->assertStringStartsWith( 'View (previous 20', strip_tags( $html ) );

		preg_match_all( '!<a.*?</a>!', $html, $m, PREG_PATTERN_ORDER );
		$links = $m[0];

		$this->assertStringContainsString( 'class="mw-nextlink"', $links[0] );
		$this->assertStringContainsString( '>next 20<', $links[0] );

		$nums = [ 50, 100, 250, 500 ];
		for ( $i = 1; $i < count( $links ); $i++ ) {
			$a = $links[$i];
			$this->assertStringContainsString( 'class="mw-numlink"', $a );
			$this->assertStringContainsString( 'title="Show ' . $nums[$i - 1] . ' results per page"', $a );
			$this->assertStringContainsString( ">{$nums[$i - 1]}<", $a );
		}
	}

	public function testGetForm() {
		$language = $this->getMockBuilder( Language::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MessageBuilder( $language );

		$this->assertInstanceOf(
			'\Message',
			$instance->getMessage( 'properties' )
		);
	}

	public function testNullLanguageThrowsException() {
		$instance = new MessageBuilder();

		$this->expectException( 'RuntimeException' );
		$instance->getMessage( 'properties' );
	}

}
