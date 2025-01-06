<?php

namespace SMW\Utils;

use MediaWiki\MediaWikiServices;

/**
 * @see https://www.semantic-mediawiki.org/wiki/SMW_logo
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class Logo {

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function get( $key ) {
		if ( $key === 'small' ) {
			return self::small();
		}

		if ( $key === 'footer' ) {
			return self::footer();
		}
	}

	private static function small() {
		$extAssets = MediaWikiServices::getInstance()
			->getMainConfig()
			->get( 'ExtensionAssetsPath' );
		return "$extAssets/SemanticMediaWiki/res/smw/assets/logo_small.svg";
	}

	private static function footer() {
		$extAssets = MediaWikiServices::getInstance()
			->getMainConfig()
			->get( 'ExtensionAssetsPath' );
		return "$extAssets/SemanticMediaWiki/res/smw/assets/logo_footer.svg";
	}

}
