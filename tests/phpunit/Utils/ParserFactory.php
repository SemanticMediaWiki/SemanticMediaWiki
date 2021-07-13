<?php

namespace SMW\Tests\Utils;

use Parser;
use ParserOptions;
use SMW\DIWikiPage;
use SMW\Tests\Utils\Mock\MockSuperUser;
use Title;
use User;

/**
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 */
class ParserFactory {

	public static function create( $title, User $user = null ) {

		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
		}

		if ( $title instanceof DIWikiPage ) {
			$title = $title->getTitle();
		}

		return self::newFromTitle( $title, $user );
	}

	public static function newFromTitle( Title $title, User $user = null ) {

		if ( $user === null ) {
			$user = new MockSuperUser();
		}

		// $wikiPage = new \WikiPage( $title );
		// $wikiPage->makeParserOptions( $user );

		// https://github.com/wikimedia/mediawiki/commit/a286a59e86d6c0fe4ce31c6137e97c202090402d
		if (
			class_exists( \MediaWiki\MediaWikiServices::class ) &&
			method_exists( \MediaWiki\MediaWikiServices::getInstance(), 'getParserFactory' ) ) {
			$parser = \MediaWiki\MediaWikiServices::getInstance()->getParserFactory()->create();
		} else {
			$parser = new Parser( $GLOBALS['wgParserConf'] );
		}

		$parser->setTitle( $title );
		$parser->setUser( $user );

		// https://github.com/wikimedia/mediawiki/commit/a2cb76937dfa46dd1cbd5b1fcb4e973e39063906#diff-21a73dc63430cc1b180d53f99c0756ee
		if ( method_exists( $parser, 'setOptions' ) ) {
			$parser->setOptions( new ParserOptions( $user ) );
		} else {
			$parser->Options( new ParserOptions( $user ) );
		}

		$parser->clearState();

		return $parser;
	}

}
