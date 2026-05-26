<?php

namespace SMW\MediaWiki;

use MediaWiki\Parser\ParserOutput;
use Onoi\Cache\Cache;
use SMW\PostProcHandler;
use SMW\Settings;

/**
 * Produces a {@link PostProcHandler} for the `OutputPageParserOutput` hook.
 * The handler is per-`ParserOutput`, so construction must be deferred until
 * the hook fires; the factory captures the long-lived cache and settings
 * dependencies.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class PostProcHandlerFactory {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Cache $cache,
		private readonly Settings $settings,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function newFor( ParserOutput $parserOutput ): PostProcHandler {
		$postProcHandler = new PostProcHandler( $parserOutput, $this->cache );

		$postProcHandler->setOptions(
			$this->settings->get( 'smwgPostEditUpdate' ) +
			[ 'smwgEnabledQueryDependencyLinksStore' => $this->settings->get( 'smwgEnabledQueryDependencyLinksStore' ) ] +
			[ 'smwgEnabledFulltextSearch' => $this->settings->get( 'smwgEnabledFulltextSearch' ) ]
		);

		return $postProcHandler;
	}

}
