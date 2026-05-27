<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\SoftwareInfoHook;
use SMW\Store;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SoftwareInfo
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SoftwareInfo implements SoftwareInfoHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSoftwareInfo( &$software ) {
		$info = $this->store->getConnection( 'elastic' )->getSoftwareInfo();

		if ( !isset( $software[$info['component']] ) && $info['version'] !== null ) {
			$software[$info['component']] = $info['version'];
		}

		return true;
	}

}
