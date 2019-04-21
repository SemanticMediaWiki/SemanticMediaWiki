<?php

namespace SMW\MediaWiki;

/**
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author Morgon Kanter
 */
class Pipetrick {

	/**
 	 * Apply the pipe trick to a page name (not a complete link).
	 *
	 * See MediaWiki includes/parser/Parser.php pstPass2() for details. Removed
	 * the opening/closing of links ([[A|b]]), instead it considers the start
	 * and close of the string. Maybe one day MediaWiki will refactor this into
	 * its own function!
	 *
	 * Doesn't attempt to apply the reverse pipe trick.
	 *
	 * @since 3.0
	 *
	 * @param $title string
	 *
	 * @return string
	 */
	public static function apply( $title ) {
		# Turn it into a link so we can use the MediaWiki regexes un-changed.
		$link = '[[' . $title . '|]]';

		# Regexes taken from MediaWiki parser.php. Maybe some day they'll factor it
		# into its own function!

		$tc = '[' . \Title::legalChars() . ']';
		$nc = '[ _0-9A-Za-z\x80-\xff-]'; # Namespaces can use non-ascii!
		// [[ns:page (context)|]]
		$p1 = "/\[\[(:?$nc+:|:|)($tc+?)( ?\\($tc+\\))\\|]]/";
		// [[ns:page  ^ context  ^ |]] (double-width brackets, added in r40257)
		$p4 = "/\[\[(:?$nc+:|:|)($tc+?)( ?  ^ $tc+  ^ )\\|]]/";
		// [[ns:page (context), context|]] (using either single or double-width comma)
		$p3 = "/\[\[(:?$nc+:|:|)($tc+?)( ?\\($tc+\\)|)((?:, |  ^ )$tc+|)\\|]]/";

		# try $p1 first, to turn "[[A, B (C)|]]" into "[[A, B (C)|A, B]]"
		$link = preg_replace( $p1, '[[\\1\\2\\3|\\2]]', $link );
		$link = preg_replace( $p4, '[[\\1\\2\\3|\\2]]', $link );
		$link = preg_replace( $p3, '[[\\1\\2\\3\\4|\\2]]', $link );

		# Now take the caption text.
		$caption = "/^.*?\|(.*)]]$/";
		$link = preg_replace( $caption, '\\1', $link );

		return $link;
	}

}
