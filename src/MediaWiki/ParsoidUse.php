<?php

namespace SMW\MediaWiki;

use MediaWiki\Config\Config;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 7.4.1
 */
class ParsoidUse {

	public function __construct(
		private readonly Config $mainConfig
	) {
	}

	public function forRendering(): bool {
		// The settings exist from MediaWiki 1.47
		return $this->mainConfig->has( 'UseParsoidParser' )
			&& $this->mainConfig->get( 'UseParsoidParser' );
	}

	public function forLinksUpdates(): bool {
		return $this->mainConfig->has( 'UseParsoidLinksUpdate' )
			&& ( $this->mainConfig->get( 'UseParsoidLinksUpdate' ) ?? $this->forRendering() );
	}

}
