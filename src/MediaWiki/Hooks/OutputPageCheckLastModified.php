<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Output\Hook\OutputPageCheckLastModifiedHook;

/**
 * Ensures `ViewAction::show` does not short-circuit with HTTP 304 before
 * the `ArticleViewHeader` hook can run, by stamping the modified-times array
 * with a per-request marker.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageCheckLastModified
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class OutputPageCheckLastModified implements OutputPageCheckLastModifiedHook {

	/**
	 * @since 7.0.0
	 */
	public function onOutputPageCheckLastModified( &$modifiedTimes, $out ): void {
		$modifiedTimes['smw'] = wfTimestamp( TS_MW, time() );
	}

}
