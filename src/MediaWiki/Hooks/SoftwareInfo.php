<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\SoftwareInfoHook;
use SMW\Services\ServicesFactory as ApplicationFactory;

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
	public function onSoftwareInfo( &$software ) {
		$store = ApplicationFactory::getInstance()->getStore();
		$info = $store->getConnection( 'elastic' )->getSoftwareInfo();

		if ( !isset( $software[$info['component']] ) && $info['version'] !== null ) {
			$software[$info['component']] = $info['version'];
		}

		return true;
	}

}
