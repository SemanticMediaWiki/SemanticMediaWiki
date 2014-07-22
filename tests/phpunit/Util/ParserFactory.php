<?php

namespace SMW\Tests\Util;

use SMW\Tests\Util\Mock\MockSuperUser;

use Title;
use ParserOptions;
use Parser;
use User;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 */
class ParserFactory {

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
