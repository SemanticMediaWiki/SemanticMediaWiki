<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use PHPUnit\Framework\TestCase;
use Skin;
use SMW\Localizer\DeferredLocalizedMessage;
use SMW\MediaWiki\Hooks\ParserOutputPostCacheTransform;

/**
 * @covers \SMW\MediaWiki\Hooks\ParserOutputPostCacheTransform
 */
class ParserOutputPostCacheTransformTest extends TestCase {

	public function testResolvesMarkerWithUserLangOption() {
		$de = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'de' );
		$text = '<a>' . DeferredLocalizedMessage::newMarker( 'further-results' ) . '</a>';
		$options = [ 'userLang' => $de ];

		( new ParserOutputPostCacheTransform() )
			->onParserOutputPostCacheTransform( new ParserOutput(), $text, $options );

		$this->assertStringNotContainsString( 'smw-localized-message', $text );
	}

	public function testNoOpWhenNoMarker() {
		$text = '<p>plain</p>';
		$before = $text;
		$options = [ 'userLang' => MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ) ];

		( new ParserOutputPostCacheTransform() )
			->onParserOutputPostCacheTransform( new ParserOutput(), $text, $options );

		$this->assertSame( $before, $text );
	}

	public function testUsesLanguageFromSkinWhenUserLangAbsent() {
		$de = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'de' );
		$skin = $this->createMock( Skin::class );
		$skin->method( 'getLanguage' )->willReturn( $de );

		$text = '<a>' . DeferredLocalizedMessage::newMarker( 'further-results' ) . '</a>';
		$options = [ 'skin' => $skin ];

		( new ParserOutputPostCacheTransform() )
			->onParserOutputPostCacheTransform( new ParserOutput(), $text, $options );

		$this->assertStringNotContainsString( 'smw-localized-message', $text );
	}
}
