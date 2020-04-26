<?php

namespace SMW\Exception;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class SiteLanguageChangeException extends RuntimeException {

	/**
	 * @since 3.2
	 *
	 * @param string $old
	 * @param string $new
	 */
	public function __construct( string $old, string $new ) {
		parent::__construct(
			"A change to the `wgLanguageCode` was detected showing a discrepancy ($old, $new) and " .
			"is preventing Semantic MediaWiki from modifying related namespace settings.\n\n" .
			"LocalSettings.php should only contain one `wgLanguageCode` definition and the declaration should " .
			"happen before `enableSemantics`."
		);
	}

}
