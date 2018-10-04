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

		$parser = new Parser( $GLOBALS['wgParserConf'] );
		$parser->setTitle( $title );
		$parser->setUser( $user );
		$parser->Options( new ParserOptions( $user ) );
		$parser->clearState();

		return $parser;
	}

}
