<?php

namespace SMW\Tests\Unit\Localizer;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\DeferredLocalizedMessage;

/**
 * @covers \SMW\Localizer\DeferredLocalizedMessage
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class DeferredLocalizedMessageTest extends TestCase {

	public function testNewMarkerFurtherResults() {
		$marker = DeferredLocalizedMessage::newMarker( 'further-results' );
		$this->assertStringContainsString( 'class="smw-localized-message"', $marker );
		$this->assertStringContainsString( 'data-smw-msg="further-results"', $marker );
		$this->assertStringContainsString( 'further results', $marker );
	}

	public function testNewMarkerCategoryContinues() {
		$marker = DeferredLocalizedMessage::newMarker( 'category-continues' );
		$this->assertStringContainsString( 'data-smw-msg="category-continues"', $marker );
	}

	public function testNewMarkerRejectsUnknownId() {
		$this->expectException( InvalidArgumentException::class );
		DeferredLocalizedMessage::newMarker( 'not-allowed' );
	}

	public function testResolveSwapsToUserLanguage() {
		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'de' );
		$html = '<a href="/x">' . DeferredLocalizedMessage::newMarker( 'further-results' ) . '</a>';
		$out = DeferredLocalizedMessage::resolve( $html, $lang );
		$this->assertStringNotContainsString( 'smw-localized-message', $out );
		$this->assertStringContainsString( '</a>', $out );
	}

	public function testResolveNoOpWhenAbsent() {
		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
		$html = '<p>nothing here</p>';
		$this->assertSame( $html, DeferredLocalizedMessage::resolve( $html, $lang ) );
	}

	public function testResolveLeavesUnknownIdUntouched() {
		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
		$html = '<span class="smw-localized-message" data-smw-msg="bogus">x</span>';
		$out = DeferredLocalizedMessage::resolve( $html, $lang );
		$this->assertStringContainsString( 'data-smw-msg="bogus"', $out );
	}

	public function testResolveIsIdempotent() {
		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'de' );
		$once = DeferredLocalizedMessage::resolve(
			DeferredLocalizedMessage::newMarker( 'further-results' ),
			$lang
		);
		$twice = DeferredLocalizedMessage::resolve( $once, $lang );
		$this->assertSame( $once, $twice );
	}

	public function testResolveHandlesMultipleMarkers() {
		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'de' );
		$html = '<div>'
			. DeferredLocalizedMessage::newMarker( 'further-results' )
			. ' and '
			. DeferredLocalizedMessage::newMarker( 'category-continues' )
			. '</div>';
		$out = DeferredLocalizedMessage::resolve( $html, $lang );
		$this->assertStringNotContainsString( 'smw-localized-message', $out );
	}

}
